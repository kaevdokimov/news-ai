#!/bin/bash

set -e

domains=(signalscan.ru www.signalscan.ru)
rsa_key_size=4096
data_path="./certbot"
email="admin@signalscan.ru" # Adding a valid address is strongly recommended
staging=0 # Set to 1 if you're testing your setup to avoid hitting request limits

if [ -d "$data_path" ]; then
  read -p "Existing data found for $domains. Continue and replace existing certificate? (y/N) " decision
  if [ "$decision" != "Y" ] && [ "$decision" != "y" ]; then
    exit
  fi
fi

if [ ! -e "$data_path/conf/options-ssl-nginx.conf" ] || [ ! -e "$data_path/conf/ssl-dhparams.pem" ]; then
  echo "### Downloading recommended TLS parameters ..."
  mkdir -p "$data_path/conf"
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > "$data_path/conf/options-ssl-nginx.conf"
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > "$data_path/conf/ssl-dhparams.pem"
  echo
fi

echo "### Creating dummy certificate for $domains ..."
path="/etc/letsencrypt/live"
mkdir -p "$data_path/conf/live"
for domain in "${domains[@]}"; do
  mkdir -p "$data_path/conf/live/$domain"
  # Create dummy certificates
  openssl req -x509 -nodes -newkey rsa:1024 -days 1 \
    -keyout "$data_path/conf/live/$domain/privkey.pem" \
    -out "$data_path/conf/live/$domain/fullchain.pem" \
    -subj "/CN=localhost"
done

echo "### Starting nginx ..."
docker compose up --force-recreate -d news_ai_nginx
echo

# Wait for nginx to start
sleep 5

# shellcheck disable=SC2128
echo "### Deleting dummy certificate for $domains ..."
for domain in "${domains[@]}"; do
  rm -f "$data_path/conf/live/$domain/privkey.pem"
  rm -f "$data_path/conf/live/$domain/fullchain.pem"
done
echo

# shellcheck disable=SC2128
echo "### Requesting Let's Encrypt certificate for $domains ..."
#Join $domains to -d args
domain_args=""
for domain in "${domains[@]}"; do
  domain_args="$domain_args -d $domain"
done

# Select appropriate email arg
case "$email" in
  "") email_arg="--register-unsafely-without-email" ;;
  *) email_arg="--email $email" ;;
esac

# Enable staging mode if needed
if [ $staging != "0" ]; then staging_arg="--staging"; fi

docker compose run --rm --entrypoint "\
  certbot certonly --webroot -w /var/www/certbot \
    $staging_arg \
    $email_arg \
    $domain_args \
    --rsa-key-size $rsa_key_size \
    --agree-tos \
    --force-renewal" certbot
echo

echo "### Reloading nginx ..."
docker compose exec news_ai_nginx nginx -s reload
