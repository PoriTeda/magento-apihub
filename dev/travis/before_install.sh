#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

# disable xdebug and adjust memory limit
echo > ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini
echo 'memory_limit = -1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
phpenv rehash;

# Creating phpcs report folder
echo "Creating ./phpcs_report folder"
mkdir ./phpcs_report && chmod 755 ./phpcs_report # Creating phpcs reporting folder

echo "{\"http-basic\":{\"repo.magento.com\":{\"username\":\"${COMPOSER_USERNAME}\",\"password\":\"${COMPOSER_PASSWORD}\"}}}" > ~/auth.json

echo "Inputing AWS S3 credentials"
mkdir -p ~/.aws
touch ~/.aws/credentials && cat > ~/.aws/credentials << EOL
[default]
aws_access_key_id = ${AWS_ACCESS_KEY_ID}
aws_secret_access_key = ${AWS_SECRET_ACCESS_KEY}
EOL