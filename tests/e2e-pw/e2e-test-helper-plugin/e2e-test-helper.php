<?php
/**
 * @package           brianhenryie/bh-wp-bitcoin-gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Bitcoin Gateway E2E Test Helper
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/
 * Description:       Actions and filters to help with E2E tests.
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway;

use ActionScheduler;
use ActionScheduler_Abstract_RecurringSchedule;
use ActionScheduler_Action;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

( new E2E_Test_Helper_Plugin() )->register_hooks();

class E2E_Test_Helper_Plugin {

	public function register_hooks() {
		add_filter( 'rest_pre_dispatch', array( $this, 'show_settings_in_rest' ) );
		/**
		 * @see \Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute::check_nonce()
		 */
		add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		add_filter( 'rest_authentication_errors', array( $this, 'set_rest_user_admin' ) );

		add_action( 'init', array( $this, 'login_as_any_user' ) );

		/**
		 * @see \Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingSetupWizard::do_admin_redirects()
		 */
		add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );

		add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );
		add_action( 'rest_api_init', array( $this, 'bh_activate_theme' ) );
		add_action( 'rest_api_init', array( $this, 'register_get_theme_list_route' ) );
		add_action( 'rest_api_init', array( $this, 'register_test_helper_rest_active_theme_route' ) );

		add_action( 'rest_api_init', array( $this, 'register_action_scheduler_search' ) );
		add_action( 'rest_api_init', array( $this, 'register_action_scheduler_delete' ) );
	}

	/**
	 * Expose settings through the REST API.
	 *
	 * `woocommerce_checkout_page_id`
	 *
	 * @hooked rest_pre_dispatch
	 *
	 * @param null|mixed $short_circuit The value to return.
	 *
	 * @see get_registered_settings
	 * /wp-json/wp/v2/settings
	 *
	 * @see WP_REST_Settings_Controller
	 */
	public function show_settings_in_rest( $short_circuit ) {
		global $wp_registered_settings;

		if ( ! in_array( 'woocommerce_checkout_page_id', $wp_registered_settings, true ) ) {
			$wp_registered_settings['woocommerce_checkout_page_id'] = array(
				'show_in_rest'      => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			);
		}

		return $short_circuit;
	}

	/**
	 * @param WP_Error|null|true $errors WP_Error if authentication error, null if authentication method wasn't used, true if authentication succeeded.
	 *
	 * @see WP_REST_Server::check_authentication()
	 * @hooked rest_authentication_errors
	 */
	public function set_rest_user_admin( $errors ) {

		wp_set_current_user( 1 );

		return $errors;
	}

	/**
	 * @hooked init
	 */
	public function login_as_any_user(): void {
		if ( isset( $_GET['login_as_user'] ) ) {
			$wp_user = get_user_by( 'slug', $_GET['login_as_user'] );
			wp_set_current_user( $wp_user->ID );
			wp_set_current_user( $wp_user->ID, $wp_user->user_login );
			wp_set_auth_cookie( $wp_user->ID );
		}
	}


	public function activate_custom_theme_callback( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$json = json_decode( $request->get_body(), true );

		$theme_slug = $json['theme_slug'] ?? null;

		if ( ! $theme_slug ) {
			return new WP_Error( 'rest_missing_param', 'Missing theme_slug parameter: ' . $request->get_body(), array( 'status' => 400 ) );
		}

		// Check if the theme exists.
		if ( ! wp_get_theme( $theme_slug )->exists() ) {
			return new WP_Error( 'rest_theme_not_found', 'Theme not found.', array( 'status' => 404 ) );
		}

		// Activate the theme.
		switch_theme( $theme_slug );

		return new WP_REST_Response(
			array(
				'message'    => 'Theme activated successfully.',
				'theme_slug' => $theme_slug,
			),
			200
		);
	}

	/**
	 * Register `e2e-test-helper/v1/activate` route.
	 *
	 * @hooked rest_api_init
	 */
	public function bh_activate_theme() {
		register_rest_route(
			'e2e-test-helper/v1',
			'/activate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'activate_custom_theme_callback' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Register `e2e-test-helper/v1/get-theme-list` route.
	 *
	 * @hooked rest_api_init
	 */
	public function register_get_theme_list_route(): void {
		register_rest_route(
			'e2e-test-helper/v1',
			'/get-theme-list',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'theme_list_function' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get a list of themes
	 *
	 * @return string[] The theme slugs.
	 */
	public function theme_list_function(): array {
		$list = wp_get_themes();

		return array_keys( $list );
	}

	/**
	 * Path to rest endpoint.
	 *
	 * @hooked rest_api_init
	 */
	public function register_test_helper_rest_active_theme_route(): void {
		register_rest_route(
			'e2e-test-helper/v1',
			'/active_theme',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'active_theme' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get the theme.
	 *
	 * @return array{slug: string} The currently active theme.
	 */
	public function active_theme(): array {
		return array( 'slug' => get_template() );
	}

	/**
	 * Add a REST endpoint for searching Action Scheduler actions.
	 *
	 * GET /wp-json/e2e-test-helper/v1/action_scheduler/search?hook={$hook}
	 *
	 * @hooked rest_api_init
	 */
	public function register_action_scheduler_search(): void {
		register_rest_route(
			'e2e-test-helper/v1',
			'/action_scheduler/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'action_scheduler_search' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function action_scheduler_search( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		if ( ! function_exists( 'as_supports' ) ) {
			return new WP_Error( '', 'Action scheduler is not loaded.', array( 'status' => 500 ) );
		}

		$search = $request->get_params();

		// if ( ! $search ) {
		// return new WP_Error( 'rest_missing_param', 'Missing "s" search parameter.', array( 'status' => 400 ) );
		// }

		/**
		 * @see ActionScheduler_DBStore::get_query_actions_sql()
		 */
		$search['per_page'] = $search['per_page'] ?? 200;
		$search['orderby']  = $search['orderby'] ?? 'date';
		$search['order']    = $search['order'] ?? 'ASC';
		$results            = as_get_scheduled_actions( $search );

		$store = ActionScheduler::store();

		/**
		 * @see \ActionScheduler_ListTable::prepare_items()
		 */
		$action_scheduler_action_to_array = function ( ActionScheduler_Action $action, int $index ) use ( $store ) {
			$schedule   = $action->get_schedule();
			$recurrence = $schedule instanceof ActionScheduler_Abstract_RecurringSchedule
				? $schedule->get_recurrence()
				: null;

			return array(
				'id'                           => $index,
				'hook'                         => $action->get_hook(),
				'status'                       => $store->get_status( $index ),
				'args'                         => $action->get_args(),
				'group'                        => $action->get_group(),
				/**
				 * Might be nice to use @see ActionScheduler_ListTable::human_interval()
				 */
				'recurrence'                   => $recurrence,
				'scheduled_date'               => $action->get_schedule()?->next(),
				// 'log'
									'schedule' => $action->get_schedule(),
				'hook_priority'                => $action->get_priority(),
			);
		};

		foreach ( $results as $index => $result ) {
			$results[ $index ] = $action_scheduler_action_to_array( $result, $index );
		}

		return new WP_REST_Response(
			array(
				'message' => 'Action Scheduler search results for: ' . str_replace( array( "\r", "\n", "\t" ), '', print_r( $search, true ) ),
				'count'   => count( $results ),
				'data'    => $results,
			),
			200
		);
	}

	/**
	 * Add a REST endpoint for deleting Action Scheduler actions.
	 *
	 * DELETE /wp-json/e2e-test-helper/v1/action_scheduler/{$id}
	 *
	 * @hooked rest_api_init
	 */
	public function register_action_scheduler_delete(): void {
		register_rest_route(
			'e2e-test-helper/v1',
			'/action_scheduler/(?P<id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'action_scheduler_delete' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function action_scheduler_delete( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		if ( ! function_exists( 'as_supports' ) ) {
			return new WP_Error( '', 'Action scheduler is not loaded.', array( 'status' => 500 ) );
		}

		$id = $request->get_param( 'id' );

		if ( ! $id ) {
			return new WP_Error( 'rest_missing_param', 'Missing id parameter.', array( 'status' => 400 ) );
		}

		$store = ActionScheduler::store();

		$claim_id = $store->get_claim_id( $id );

		$as = $store->fetch_action( $id );

		if ( ! ( $as instanceof ActionScheduler_Action ) ) {
			return new WP_Error( 'rest_invalid_param', 'Invalid id: ' . $id, array( 'status' => 400 ) );
		}

		try {
			$store->delete_action( $id );
		} catch ( Exception $exception ) {
			return new WP_Error( 'rest_error', 'Invalid id: ' . $id . ' – ' . $exception->getMessage(), array( 'status' => 500 ) );
		}
		$claim_id_after = $store->get_claim_id( $id );

		return new WP_REST_Response(
			array(
				'message' => 'Action Scheduler delete ' . $id,
				'result'  => $claim_id !== $claim_id_after ? 'deleted' : 'not found',
				'success' => ! $claim_id_after,
			),
			200
		);
	}
}
