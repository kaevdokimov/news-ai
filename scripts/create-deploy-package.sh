#!/bin/bash

# Create a deployment package excluding .git files
echo "Creating deployment package..."

# Create deployment directory
DEPLOY_DIR="deploy"
mkdir -p $DEPLOY_DIR

# Copy necessary files and directories
cp .docker/nginx/default-production.conf $DEPLOY_DIR/
cp compose-production.yaml $DEPLOY_DIR/compose.yaml
cp .env.prod $DEPLOY_DIR/.env

# Create certbot directories
mkdir -p $DEPLOY_DIR/certbot/conf
mkdir -p $DEPLOY_DIR/certbot/www

# Copy docker files
mkdir -p $DEPLOY_DIR/.docker/app
cp .docker/app/DockerfileProduction $DEPLOY_DIR/.docker/app/Dockerfile

# Copy required directories
mkdir -p $DEPLOY_DIR/{assets,bin,config,migrations,public,src,templates,translations}

# Copy files from required directories
cp -r assets/* $DEPLOY_DIR/assets/ 2>/dev/null || true
cp -r bin/* $DEPLOY_DIR/bin/ 2>/dev/null || true
cp -r config/* $DEPLOY_DIR/config/ 2>/dev/null || true
cp -r migrations/* $DEPLOY_DIR/migrations/ 2>/dev/null || true
cp -r public/* $DEPLOY_DIR/public/ 2>/dev/null || true
cp -r src/* $DEPLOY_DIR/src/ 2>/dev/null || true
cp -r templates/* $DEPLOY_DIR/templates/ 2>/dev/null || true
cp -r translations/* $DEPLOY_DIR/translations/ 2>/dev/null || true

# Copy root level files
cp composer.json $DEPLOY_DIR/
cp composer.lock $DEPLOY_DIR/
cp symfony.lock $DEPLOY_DIR/
cp importmap.php $DEPLOY_DIR/

echo "Deployment package created in $DEPLOY_DIR"
echo "You can now deploy this package to your server"