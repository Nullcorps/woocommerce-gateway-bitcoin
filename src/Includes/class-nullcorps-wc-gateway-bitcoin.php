<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\Includes;

use Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs;
use Nullcorps\WC_Gateway_Bitcoin\Admin\Admin;
use Nullcorps\WC_Gateway_Bitcoin\Admin\Plugins_Page;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use Nullcorps\WC_Gateway_Bitcoin\API\Settings_Interface;
use Nullcorps\WC_Gateway_Bitcoin\Frontend\AJAX;
use Nullcorps\WC_Gateway_Bitcoin\Frontend\Frontend;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Email;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\My_Account_View_Order;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Payment_Gateways;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Templates;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Thank_You;
use Psr\Log\LoggerInterface;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * frontend-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
class Nullcorps_WC_Gateway_Bitcoin {

	protected LoggerInterface $logger;

	protected Settings_Interface $settings;

	protected API_Interface $api;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @since    1.0.0
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;

		$this->set_locale();

		$this->define_plugins_page_hooks();

		$this->define_frontend_hooks();
		$this->define_template_hooks();

		$this->define_payment_gateway_hooks();

		$this->define_thank_you_hooks();
		$this->define_email_hooks();
		$this->define_my_account_hooks();

		$this->define_action_scheduler_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Hooks to add a "Settings" link on plugins.php.
	 * And a link to an orders filter (where possible).
	 */
	protected function define_plugins_page_hooks(): void {

		$plugins_page = new Plugins_Page( $this->api );

		$plugin_basename = $this->settings->get_plugin_basename();

		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'add_settings_action_link' ) );
		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'add_orders_action_link' ) );
	}

	/**
	 * Enqueue styles, scripts and AJAX to style and handle the templates.
	 *
	 * @since    1.0.0
	 */
	protected function define_frontend_hooks(): void {

		$plugin_frontend = new Frontend( $this->api, $this->settings, $this->logger );

		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_scripts' ) );

		$ajax = new AJAX( $this->api, $this->logger );

		add_action( 'wp_ajax_nullcorps_bitcoin_refresh_order_details', array( $ajax, 'get_order_details' ) );
	}

	/**
	 * Hooks into WooCommerce templating system to provide the templates used to display the payment details
	 * after checkout, on the my-account order view, and in email.
	 */
	protected function define_template_hooks(): void {

		$templates = new Templates();

		add_filter( 'wc_get_template', array( $templates, 'load_bitcoin_templates' ), 10, 5 );
	}

	/**
	 * Register the gateway class with WooCommerce.
	 * Add a filter for the WooCommerce Settings payment gateways view to filter to only Bitcoin gateways.
	 */
	protected function define_payment_gateway_hooks(): void {

		$payment_gateways = new Payment_Gateways();

		// Register the payment gateway with WooCommerce.
		add_filter( 'woocommerce_payment_gateways', array( $payment_gateways, 'add_to_woocommerce' ) );

		// When clicking the link from plugins.php filter to only Bitcoin gateways.
		add_filter( 'woocommerce_payment_gateways', array( $payment_gateways, 'filter_to_only_bitcoin_gateways' ), 100 );

	}

	/**
	 * Hook into the Thank You page to display payment instructions / status.
	 */
	protected function define_thank_you_hooks(): void {

		$thank_you = new Thank_You( $this->api );

		add_action( 'woocommerce_thankyou', array( $thank_you, 'print_instructions' ), 5 );
	}

	/**
	 * Hook into emails and send payment instructions / status for related orders.
	 */
	protected function define_email_hooks(): void {

		$email = new Email( $this->api, $this->logger );

		// TODO: Before table? best place?
		add_action( 'woocommerce_email_before_order_table', array( $email, 'print_instructions' ), 10, 3 );
	}

	/**
	 * Add hooks to display the Bitcoin payment details on the single order view in my-account.
	 */
	protected function define_my_account_hooks(): void {

		$my_account_order = new My_Account_View_Order( $this->api );

		add_action( 'woocommerce_view_order', array( $my_account_order, 'print_status_instructions' ), 9 );
	}

	/**
	 * Handle Action Scheduler invoked actions to generate new addresses and check unpaid orders.
	 */
	protected function define_action_scheduler_hooks(): void {

		$background_jobs = new Background_Jobs( $this->api, $this->logger );

		add_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK, array( $background_jobs, 'generate_new_addresses' ) );
		add_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK, array( $background_jobs, 'check_unpaid_order' ) );
	}
}
