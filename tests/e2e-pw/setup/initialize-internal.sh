#!/bin/bash

# Print the script name.
echo $(basename "$0")

echo "wp plugin activate --all"
wp plugin activate --all

wp plugin deactivate give-next-gen --skip-plugins;
wp plugin deactivate give --skip-plugins;
wp plugin deactivate givewp-example-gateway --skip-plugins;

wp plugin deactivate decentralized-bitcoin-cryptodec-payment-gateway-for-woocommerce --skip-plugins;

wp plugin deactivate woo-nimiq-gateway --skip-plugins;


echo "Set up pretty permalinks for REST API."
wp rewrite structure /%year%/%monthnum%/%postname%/ --hard;


wp theme activate storefront;

# Install GMP in WordPress container
# RUN apt-get install -y libgmp-dev
# RUN docker-php-ext-install gmp

# Install GMP in CLI container.
# sudo mkdir /conf.d;
# sudo apk add gmp-dev;
# sudo docker-php-ext-install gmp;
# cd /usr/src/php/ext/gmp
# sudo make test;



#    "/usr/local/etc/php/conf.d/docker-php-ext-gmp.ini": "./tests/e2e-pw/setup/docker-php-ext-gmp.ini",

# /usr/local/etc/php/conf.d/docker-php-ext-gmp.ini
# extension=gmp.so

# php -r "echo function_exists( 'gmp_init' ) ? 'gmp IS installed' . PHP_EOL : 'gmp is not installed' . PHP_EOL;"