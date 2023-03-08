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
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway;

use BrianHenryIE\WP_Bitcoin_Gateway\Admin\Dependencies_Notice;
use BrianHenryIE\WP_Bitcoin_Gateway\Admin\Register_List_Tables;
use BrianHenryIE\WP_Bitcoin_Gateway\GiveWP\GiveWP_Gateway;
use BrianHenryIE\WP_Bitcoin_Gateway\GiveWP\GiveWP_NextGen_Bitcoin_Gateway;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\Woo_Cancel_Abandoned_Order;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\HPOS;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Order;
use Exception;
use BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WP_Bitcoin_Gateway\Admin\Plugins_Page;
use BrianHenryIE\WP_Bitcoin_Gateway\Admin\Wallets_List_Table;
use BrianHenryIE\WP_Bitcoin_Gateway\Frontend\AJAX;
use BrianHenryIE\WP_Bitcoin_Gateway\Frontend\Frontend_Assets;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Admin_Order_UI;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Email;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\My_Account_View_Order;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Payment_Gateways;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Templates;
use BrianHenryIE\WP_Bitcoin_Gateway\WooCommerce\Thank_You;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes\CLI;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes\I18n;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes\Post;
use Give\Framework\PaymentGateways\PaymentGatewayRegister;
use BrianHenryIE\WP_Bitcoin_Gateway\Psr\Log\LoggerInterface;
use WP_CLI;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * frontend-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
class BH_WP_Bitcoin_Gateway {

	/**
	 * A PSR logger for logging errors, events etc.
	 */
	protected LoggerInterface $logger;

	/**
	 * The plugin settings.
	 */
	protected Settings_Interface $settings;

	/**
	 * The main plugin functions.
	 */
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
		$this->define_dependencies_admin_notice_hooks();

		$this->define_custom_post_type_hooks();

		$this->define_frontend_hooks();
		$this->define_template_hooks();

		$this->define_payment_gateway_hooks();
		$this->define_order_hooks();
		$this->define_action_scheduler_hooks();

		$this->define_thank_you_hooks();
		$this->define_email_hooks();
		$this->define_my_account_hooks();

		$this->define_admin_order_ui_hooks();
		$this->define_wp_list_page_ui_hooks();

		$this->define_woocommerce_features_hooks();

		$this->define_cli_commands();

		$this->define_integration_woo_cancel_abandoned_order_hooks();
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

		$plugins_page = new Plugins_Page( $this->api, $this->settings );

		$plugin_basename = $this->settings->get_plugin_basename();

		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'add_settings_action_link' ) );
		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'add_orders_action_link' ) );

		add_filter( 'plugin_row_meta', array( $plugins_page, 'split_author_link_into_two_links' ), 10, 2 );
	}

	/**
	 * Add a hook to display an admin notice when the required PHP extensions are not present.
	 */
	protected function define_dependencies_admin_notice_hooks(): void {

		$dependencies_notices = new Dependencies_Notice( $this->api, $this->settings );

		add_action( 'admin_notices', array( $dependencies_notices, 'print_dependencies_notice' ) );
	}

	/**
	 * Add hooks for defining post types for the wallets and destination addresses.
	 */
	protected function define_custom_post_type_hooks():void {

		$post = new Post( $this->api );
		add_action( 'init', array( $post, 'register_wallet_post_type' ) );
		add_action( 'init', array( $post, 'register_address_post_type' ) );
	}

	/**
	 * Enqueue styles, scripts and AJAX to style and handle the templates.
	 *
	 * @since    1.0.0
	 */
	protected function define_frontend_hooks(): void {

		$plugin_frontend = new Frontend_Assets( $this->api, $this->settings, $this->logger );

		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_scripts' ) );

		$ajax = new AJAX( $this->api, $this->logger );

		add_action( 'wp_ajax_bh_wp_bitcoin_gateway_refresh_order_details', array( $ajax, 'get_order_details' ) );
		add_action( 'wp_ajax_nopriv_bh_wp_bitcoin_gateway_refresh_order_details', array( $ajax, 'get_order_details' ) );
	}

	/**
	 * Hooks into WooCommerce templating system to provide the templates used to display the payment details
	 * after checkout, on the my-account order view, and in email.
	 */
	protected function define_template_hooks(): void {

		$templates = new Templates( $this->settings );

		add_filter( 'wc_get_template', array( $templates, 'load_bitcoin_templates' ), 10, 5 );
	}

	/**
	 * Register the gateway class with WooCommerce.
	 * Add a filter for the WooCommerce Settings payment gateways view to filter to only Bitcoin gateways.
	 */
	protected function define_payment_gateway_hooks(): void {

		$payment_gateways = new Payment_Gateways( $this->api, $this->settings, $this->logger );

		// Register the payment gateway with WooCommerce.
		add_filter( 'woocommerce_payment_gateways', array( $payment_gateways, 'add_to_woocommerce' ) );

		// Register the payment gateway with WooCommerce Blocks checkout.
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $payment_gateways, 'register_woocommerce_block_checkout_support' ) );

		add_filter( 'woocommerce_available_payment_gateways', array( $payment_gateways, 'add_logger_to_gateways' ) );
	}

	/**
	 * Handle order status changes.
	 */
	protected function define_order_hooks(): void {

		$order = new Order( $this->api, $this->logger );

		add_action( 'woocommerce_order_status_changed', array( $order, 'schedule_check_for_transactions' ), 10, 3 );
		add_action( 'woocommerce_order_status_changed', array( $order, 'unschedule_check_for_transactions' ), 10, 3 );
	}

	/**
	 * Hook into the Thank You page to display payment instructions / status.
	 */
	protected function define_thank_you_hooks(): void {

		$thank_you = new Thank_You( $this->api, $this->logger );

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

		$my_account_order = new My_Account_View_Order( $this->api, $this->logger );

		add_action( 'woocommerce_view_order', array( $my_account_order, 'print_status_instructions' ), 9 );
	}

	/**
	 * Handle Action Scheduler invoked actions to generate new addresses and check unpaid orders.
	 */
	protected function define_action_scheduler_hooks(): void {

		$background_jobs = new Background_Jobs( $this->api, $this->logger );

		add_action( Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK, array( $background_jobs, 'generate_new_addresses' ) );
		add_action( Background_Jobs::CHECK_UNPAID_ORDER_HOOK, array( $background_jobs, 'check_unpaid_order' ) );
		add_action( Background_Jobs::CHECK_NEW_ADDRESSES_TRANSACTIONS_HOOK, array( $background_jobs, 'check_new_addresses_for_transactions' ) );
	}

	/**
	 * Add a meta box to the admin order view showing the Bitcoin total, address and transactions.
	 */
	protected function define_admin_order_ui_hooks(): void {

		$admin_order_ui = new Admin_Order_UI( $this->api, $this->logger );

		add_action( 'add_meta_boxes', array( $admin_order_ui, 'register_address_transactions_meta_box' ) );
	}

	/**
	 * Customize the columns and data shown in the WP_List_Table for bitcoin wallets and bitcoin addresses.
	 */
	protected function define_wp_list_page_ui_hooks(): void {

		$register_list_tables = new Register_List_Tables();

		add_filter( 'wp_list_table_class_name', array( $register_list_tables, 'register_bitcoin_address_table' ), 10, 2 );
		add_filter( 'wp_list_table_class_name', array( $register_list_tables, 'register_bitcoin_wallet_table' ), 10, 2 );
	}

	/**
	 * Declare compatibility with WooCommerce High Performace Order Storage.
	 */
	protected function define_woocommerce_features_hooks(): void {

		$hpos = new HPOS( $this->settings );

		add_action( 'before_woocommerce_init', array( $hpos, 'declare_compatibility' ) );
	}

	/**
	 * Register WP CLI commands.
	 *
	 * `wp bh-bitcoin generate-new-addresses`
	 */
	protected function define_cli_commands(): void {

		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}

		$cli = new CLI( $this->api, $this->settings, $this->logger );

		try {
			WP_CLI::add_command( 'bh-bitcoin generate-new-addresses', array( $cli, 'generate_new_addresses' ) );
			WP_CLI::add_command( 'bh-bitcoin check-transactions', array( $cli, 'check_transactions' ) );
		} catch ( Exception $e ) {
			$this->logger->error( 'Failed to register WP CLI commands: ' . $e->getMessage(), array( 'exception' => $e ) );
		}
	}

	/**
	 * Add filters to enable support for WooCommerce Cancel Abandoned Order plugin.
	 *
	 * @see https://wordpress.org/plugins/woo-cancel-abandoned-order/
	 */
	protected function define_integration_woo_cancel_abandoned_order_hooks(): void {

		$woo_cancel_abandoned_order = new Woo_Cancel_Abandoned_Order( $this->api );

		add_filter( 'woo_cao_gateways', array( $woo_cancel_abandoned_order, 'enable_cao_for_bitcoin' ) );
		add_filter( 'woo_cao_before_cancel_order', array( $woo_cancel_abandoned_order, 'abort_canceling_partially_paid_order' ), 10, 3 );
	}

}
