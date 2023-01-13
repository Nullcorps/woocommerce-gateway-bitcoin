<?php
/**
 * @package    brianhenryie/bh-wc-bitcoin-gateway
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Bitcoin_Gateway;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Bitcoin_Gateway\Action_Scheduler\Background_Jobs;
use BrianHenryIE\WC_Bitcoin_Gateway\Admin\Plugins_Page;
use BrianHenryIE\WC_Bitcoin_Gateway\Frontend\Frontend_Assets;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Admin_Order_UI;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Email;
use BrianHenryIE\WC_Bitcoin_Gateway\WooCommerce\Payment_Gateways;
use BrianHenryIE\WC_Bitcoin_Gateway\WP_Includes\I18n;
use WP_Mock\Matcher\AnyInstance;

/**
 * Class BH_WC_Bitcoin_Gateway_Unit_Test
 *
 * @coversDefaultClass \BrianHenryIE\WC_Bitcoin_Gateway\BH_WC_Bitcoin_Gateway
 */
class BH_WC_Bitcoin_Gateway_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::set_locale
	 */
	public function test_set_locale_hooked(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_admin_hooks
	 */
	public function test_admin_hooks(): void {

		$this->markTestSkipped( 'Not using Admin class right now' );

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin::class ), 'enqueue_styles' )
		);

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin::class ), 'enqueue_scripts' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_plugins_page_hooks
	 */
	public function test_plugins_page_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'plugin_action_links_bh-wc-bitcoin-gateway/bh-wc-bitcoin-gateway.php',
			array( new AnyInstance( Plugins_Page::class ), 'add_settings_action_link' )
		);

		\WP_Mock::expectFilterAdded(
			'plugin_action_links_bh-wc-bitcoin-gateway/bh-wc-bitcoin-gateway.php',
			array( new AnyInstance( Plugins_Page::class ), 'add_orders_action_link' )
		);

		\WP_Mock::expectFilterAdded(
			'plugin_row_meta',
			array( new AnyInstance( Plugins_Page::class ), 'split_author_link_into_two_links' ),
			10,
			2
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => 'bh-wc-bitcoin-gateway/bh-wc-bitcoin-gateway.php',
			)
		);
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_frontend_hooks
	 */
	public function test_frontend_hooks(): void {

		\WP_Mock::expectActionAdded(
			'wp_enqueue_scripts',
			array( new AnyInstance( Frontend_Assets::class ), 'enqueue_styles' )
		);

		\WP_Mock::expectActionAdded(
			'wp_enqueue_scripts',
			array( new AnyInstance( Frontend_Assets::class ), 'enqueue_scripts' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_email_hooks
	 */
	public function test_email_hooks(): void {

		\WP_Mock::expectActionAdded(
			'woocommerce_email_before_order_table',
			array( new AnyInstance( Email::class ), 'print_instructions' ),
			10,
			3
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_payment_gateway_hooks
	 */
	public function test_payment_gateway_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'woocommerce_payment_gateways',
			array( new AnyInstance( Payment_Gateways::class ), 'add_to_woocommerce' )
		);

		\WP_Mock::expectFilterAdded(
			'woocommerce_payment_gateways',
			array( new AnyInstance( Payment_Gateways::class ), 'filter_to_only_bitcoin_gateways' ),
			100
		);

		\WP_Mock::expectFilterAdded(
			'woocommerce_available_payment_gateways',
			array( new AnyInstance( Payment_Gateways::class ), 'add_logger_to_gateways' ),
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_action_scheduler_hooks
	 */
	public function test_define_action_scheduler_hooks(): void {

		\WP_Mock::expectActionAdded(
			Background_Jobs::CHECK_UNPAID_ORDER_HOOK,
			array( new AnyInstance( Background_Jobs::class ), 'check_unpaid_order' )
		);

		\WP_Mock::expectActionAdded(
			Background_Jobs::GENERATE_NEW_ADDRESSES_HOOK,
			array( new AnyInstance( Background_Jobs::class ), 'generate_new_addresses' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_admin_order_ui_hooks
	 */
	public function test_define_admin_order_ui_hooks(): void {

		\WP_Mock::expectActionAdded(
			'add_meta_boxes',
			array( new AnyInstance( Admin_Order_UI::class ), 'register_address_transactions_meta_box' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new BH_WC_Bitcoin_Gateway( $api, $settings, $logger );
	}

}
