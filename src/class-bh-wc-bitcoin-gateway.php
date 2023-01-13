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
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway;

use Exception;
use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Addresses_List_Table;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Plugins_Page;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Wallets_List_Table;
use BrianHenryIE\WC_Bitcoin_Gateway\Frontend\AJAX;
use BrianHenryIE\WC_Bitcoin_Gateway\Frontend\Frontend_Assets;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Admin_Order_UI;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Email;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\My_Account_View_Order;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Payment_Gateways;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Templates;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Thank_You;
use BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes\CLI;
use BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes\I18n;
use BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes\Post;
use Psr\Log\LoggerInterface;
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
class BH_WC_Bitcoin_Gateway {

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

		$this->define_custom_post_type_hooks();

		$this->define_frontend_hooks();
		$this->define_template_hooks();

		$this->define_payment_gateway_hooks();

		$this->define_thank_you_hooks();
		$this->define_email_hooks();
		$this->define_my_account_hooks();

		$this->define_admin_order_ui_hooks();
		$this->define_wallets_list_page_ui_hooks();
		$this->define_addresses_list_page_ui_hooks();

		$this->define_action_scheduler_hooks();

		$this->define_cli_commands();
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

		$plugins_page = new Plugins_Page( $this->settings );

		$plugin_basename = $this->settings->get_plugin_basename();

		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'add_settings_action_link' ) );
		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'add_orders_action_link' ) );

		add_filter( 'plugin_row_meta', array( $plugins_page, 'split_author_link_into_two_links' ), 10, 2 );
	}

	/**
	 * Add hooks for defining post types for the wallets and destination addresses.
	 */
	protected function define_custom_post_type_hooks():void {

		$post = new Post();
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

		add_action( 'wp_ajax_bh_wc_bitcoin_gateway_refresh_order_details', array( $ajax, 'get_order_details' ) );
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

		$payment_gateways = new Payment_Gateways( $this->logger );

		// Register the payment gateway with WooCommerce.
		add_filter( 'woocommerce_payment_gateways', array( $payment_gateways, 'add_to_woocommerce' ) );

		// When clicking the link from plugins.php filter to only Bitcoin gateways.
		add_filter( 'woocommerce_payment_gateways', array( $payment_gateways, 'filter_to_only_bitcoin_gateways' ), 100 );

		add_filter( 'woocommerce_available_payment_gateways', array( $payment_gateways, 'add_logger_to_gateways' ) );
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
	 * Customize the columns and data shown in the WP_List_Table for crypto wallets.
	 */
	protected function define_wallets_list_page_ui_hooks(): void {

		$wallets_list_page = new Wallets_List_Table();

		add_filter( 'manage_edit-bh-crypto-wallet_columns', array( $wallets_list_page, 'define_columns' ) );

		add_action( 'manage_bh-crypto-wallet_posts_custom_column', array( $wallets_list_page, 'print_columns' ), 10, 2 );

		add_action(
			'admin_menu',
			function() use ( $wallets_list_page ) {
				add_filter( 'post_row_actions', array( $wallets_list_page, 'edit_row_actions' ), 10, 2 );
			}
		);
	}

	/**
	 * Customize the columns and data shown in the WP_List_Table for crypto addresses.
	 */
	protected function define_addresses_list_page_ui_hooks(): void {

		$addresses_list_page = new Addresses_List_Table();

		add_filter( 'manage_edit-bh-crypto-address_columns', array( $addresses_list_page, 'define_columns' ) );

		add_action( 'manage_bh-crypto-address_posts_custom_column', array( $addresses_list_page, 'print_columns' ), 10, 2 );

		add_action(
			'admin_menu',
			function() use ( $addresses_list_page ) {
				add_filter( 'post_row_actions', array( $addresses_list_page, 'edit_row_actions' ), 10, 2 );
			}
		);
	}

	/**
	 * Register WP CLI commands.
	 *
	 * `wp bh-crypto generate-new-addresses`
	 */
	protected function define_cli_commands(): void {

		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}

		$cli = new CLI( $this->api, $this->settings, $this->logger );

		try {
			WP_CLI::add_command( 'bh-crypto generate-new-addresses', array( $cli, 'generate_new_addresses' ) );
			WP_CLI::add_command( 'bh-crypto update-address', array( $cli, 'update_address' ) );
		} catch ( Exception $e ) {
			$this->logger->error( 'Failed to register WP CLI commands: ' . $e->getMessage(), array( 'exception' => $e ) );
		}
	}

}
