#!/bin/bash

set -e

DOMAINS=("signalscan.ru" "www.signalscan.ru")
CERTBOT_EMAIL="admin@signalscan.ru"
RSA_KEY_SIZE=4096
DATA_PATH="./certbot"
WEBROOT_PATH="/var/www/certbot"

if [ -d "$DATA_PATH" ]; then
  read -p "Existing data found for $DOMAINS. Continue and replace existing certificate? (y/N) " decision
  if [ "$decision" != "Y" ] && [ "$decision" != "y" ]; then
    exit
  fi
fi

if [ ! -e "$DATA_PATH/conf/options-ssl-nginx.conf" ] || [ ! -e "$DATA_PATH/conf/ssl-dhparams.pem" ]; then
  echo "### Downloading recommended TLS parameters ..."
  mkdir -p "$DATA_PATH/conf"
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > "$DATA_PATH/conf/options-ssl-nginx.conf"
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > "$DATA_PATH/conf/ssl-dhparams.pem"
  echo
fi

echo "### Creating dummy certificate for $DOMAINS ..."
PATH="/etc/letsencrypt/live/$DOMAINS"
mkdir -p "$DATA_PATH/conf/live/$DOMAINS"
docker run --rm -v "$PWD/$DATA_PATH/conf:/etc/letsencrypt" certbot/certbot certonly --standalone -d "$DOMAINS" --non-interactive --email "$CERTBOT_EMAIL" --agree-tos --force-renewal
echo

echo "### Starting nginx ..."
docker compose up --force-recreate -d news_ai_nginx
echo

echo "### Deleting dummy certificate for $DOMAINS ..."
docker run --rm -v "$PWD/$DATA_PATH/conf:/etc/letsencrypt" certbot/certbot delete --cert-name "$DOMAINS"
echo

echo "### Requesting Let's Encrypt certificate for $DOMAINS ..."
#Join $DOMAINS to -d args
domain_args=""
for domain in "${DOMAINS[@]}"; do
  domain_args="$domain_args -d $domain"
done

docker run --rm -v "$PWD/$DATA_PATH/conf:/etc/letsencrypt" -v "$PWD/$DATA_PATH/www:/var/www/certbot" certbot/certbot certonly --webroot -w $WEBROOT_PATH $domain_args --email $CERTBOT_EMAIL --non-interactive --agree-tos --force-renewal
echo

echo "### Reloading nginx ..."
docker compose exec news_ai_nginx nginx -s reload