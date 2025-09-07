<?php
/**
 * @package           brianhenryie/bh-wp-bitcoin-gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Bitcoin Gateway E2E Test Helper
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/
 * Description:       Actions and filters to help with E2E tests.
 */


/**
 * Expose settings through the REST API.
 *
 * `woocommerce_checkout_page_id`
 *
 * @see WP_REST_Settings_Controller
 * @see get_registered_settings
 * /wp-json/wp/v2/settings
 */
add_filter(
	'rest_pre_dispatch',
	function ( $val ) {
		global $wp_registered_settings;

		if ( ! in_array( 'woocommerce_checkout_page_id', $wp_registered_settings ) ) {
			$wp_registered_settings['woocommerce_checkout_page_id'] = array(
				'show_in_rest'      => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			);
		}

		return $val;
	}
);

/**
 * @see WP_REST_Server::check_authentication()
 * @hooked rest_authentication_errors
 */
function set_rest_user_admin( $val ) {

	wp_set_current_user( 1 );

	return $val;
}
add_filter( 'rest_authentication_errors', 'set_rest_user_admin' );


/**
 * @see \Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingSetupWizard::do_admin_redirects()
 */
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );

add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );

add_action(
	'init',
	function () {
		if ( isset( $_GET['login_as_user'] ) ) {
			$wp_user = get_user_by( 'slug', $_GET['login_as_user'] );
			wp_set_current_user( $wp_user->ID );
			wp_set_current_user( $wp_user->ID, $wp_user->user_login );
			wp_set_auth_cookie( $wp_user->ID );
		}
	}
);


function activate_custom_theme_callback( WP_REST_Request $request ) {

	$json = json_decode( $request->get_body(), true );

	$theme_slug = $json['theme_slug'] ?? null;

	if ( ! $theme_slug ) {
		return new WP_Error( 'rest_missing_param', 'Missing theme_slug parameter: ' . $request->get_body(), array( 'status' => 400 ) );
	}

	// Check if the theme exists
	if ( ! wp_get_theme( $theme_slug )->exists() ) {
		return new WP_Error( 'rest_theme_not_found', 'Theme not found.', array( 'status' => 404 ) );
	}

	// Activate the theme
	switch_theme( $theme_slug );

	return new WP_REST_Response(
		array(
			'message'    => 'Theme activated successfully.',
			'theme_slug' => $theme_slug,
		),
		200
	);
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'e2e-test-helper/v1',
			'/activate',
			array(
				'methods'             => 'POST',
				'callback'            => 'activate_custom_theme_callback',
				'permission_callback' => '__return_true',
			)
		);
	}
);


add_action(
	'rest_api_init',
	function () {
		// Path to rest endpoint
		register_rest_route(
			'e2e-test-helper/v1',
			'/get_theme_list',
			array(
				'methods'             => 'GET',
				'callback'            => 'theme_list_function',
				'permission_callback' => '__return_true',
			)
		);
	}
);
// Our function to get the themes
function theme_list_function() {
	// Get a list of themes
	$list = wp_get_themes();

	// Return the value
	return array_keys( $list );
}

add_action(
	'rest_api_init',
	function () {
		// Path to rest endpoint
		register_rest_route(
			'e2e-test-helper/v1',
			'/active_theme',
			array(
				'methods'             => 'GET',
				'callback'            => 'active_theme',
				'permission_callback' => '__return_true',
			)
		);
	}
);
// Our function to get the themes
function active_theme() {
	return array( 'slug' => get_template() );
}
