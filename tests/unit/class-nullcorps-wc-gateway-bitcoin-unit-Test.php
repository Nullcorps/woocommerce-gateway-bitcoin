<?php
/**
 * @package    nullcorps/woocommerce-gateway-bitcoin
 * @author  BrianHenryIE <BrianHenryIE@gmail.com>
 */

namespace Nullcorps\WC_Gateway_Bitcoin;

use BrianHenryIE\ColorLogger\ColorLogger;
use Nullcorps\WC_Gateway_Bitcoin\Action_Scheduler\Background_Jobs;
use Nullcorps\WC_Gateway_Bitcoin\Admin\Plugins_Page;
use Nullcorps\WC_Gateway_Bitcoin\Frontend\Frontend;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Admin_Order_UI;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Email;
use Nullcorps\WC_Gateway_Bitcoin\WooCommerce\Payment_Gateways;
use Nullcorps\WC_Gateway_Bitcoin\WP_Includes\I18n;
use WP_Mock\Matcher\AnyInstance;

/**
 * Class Nullcorps_WC_Gateway_Bitcoin_Unit_Test
 *
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\Nullcorps_WC_Gateway_Bitcoin
 */
class Nullcorps_WC_Gateway_Bitcoin_Unit_Test extends \Codeception\Test\Unit {

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

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
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

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_plugins_page_hooks
	 */
	public function test_plugins_page_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'plugin_action_links_nullcorps-wc-gateway-bitcoin/nullcorps-wc-gateway-bitcoin.php',
			array( new AnyInstance( Plugins_Page::class ), 'add_settings_action_link' )
		);

		\WP_Mock::expectFilterAdded(
			'plugin_action_links_nullcorps-wc-gateway-bitcoin/nullcorps-wc-gateway-bitcoin.php',
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
				'get_plugin_basename' => 'nullcorps-wc-gateway-bitcoin/nullcorps-wc-gateway-bitcoin.php',
			)
		);
		$logger   = new ColorLogger();

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_frontend_hooks
	 */
	public function test_frontend_hooks(): void {

		\WP_Mock::expectActionAdded(
			'wp_enqueue_scripts',
			array( new AnyInstance( Frontend::class ), 'enqueue_styles' )
		);

		\WP_Mock::expectActionAdded(
			'wp_enqueue_scripts',
			array( new AnyInstance( Frontend::class ), 'enqueue_scripts' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
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

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
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

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
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

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
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

		new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );
	}

}
