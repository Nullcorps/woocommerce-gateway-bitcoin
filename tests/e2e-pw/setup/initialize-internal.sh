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

# Prevent WooCommerce setup wizard from running.
# It checks for the existence of the option 'woocommerce_version'.
# WC_Install::is_new_install()
wp option set woocommerce_version "10.0.4"
wp transient delete _wc_activation_redirect

wp theme activate storefront;
