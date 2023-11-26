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
