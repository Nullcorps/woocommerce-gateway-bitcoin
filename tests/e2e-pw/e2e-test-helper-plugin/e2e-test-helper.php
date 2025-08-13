<?php
/**
 * @package           brianhenryie/bh-wp-bitcoin-gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Bitcoin Gateway E2E Test Helper
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/
 * Description:       Actions and fitlers to help with E2E tests.
 */


/**
 * @see \Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingSetupWizard::do_admin_redirects()
 */
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );

add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );