<?php
/**
 * I think this stuff below is correct, but idk.
 * It's free, go nuts. I'm just sticking things together to make stuff.
 * - Nullcorps
 *
 * This Bitcoin gateway relies on the BitWasp bitwasp/bitcoin PHP library for the maths/heavy lifting.
 *
 * @see https://github.com/Bit-Wasp/bitcoin-php
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           nullcorps/woocommerce-gateway-bitcoin
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Gateway Bitcoin
 * Plugin URI:        http://github.com/BrianHenryIE/woocommerce-gateway-bitcoin/
 * Description:       Accept Bitcoin payments using self-custodied wallets, and no external account. Calculates wallet addresses locally and uses open APIs to verify payments. For an emphasis on privacy & sovereignty.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Author:            Nullcorps, BrianHenryIE
 * Author URI:        https://github.com/Nullcorps/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       nullcorps-wc-gateway-bitcoin
 * Domain Path:       /languages
 */

namespace Nullcorps\WC_Gateway_Bitcoin;

use Nullcorps\WC_Gateway_Bitcoin\API\API;
use Nullcorps\WC_Gateway_Bitcoin\API\API_Interface;
use Nullcorps\WC_Gateway_Bitcoin\API\Settings;
use Nullcorps\WC_Gateway_Bitcoin\BrianHenryIE\WP_Private_Uploads\Private_Uploads;
use Nullcorps\WC_Gateway_Bitcoin\Includes\Activator;
use Nullcorps\WC_Gateway_Bitcoin\Includes\Deactivator;
use Nullcorps\WC_Gateway_Bitcoin\Includes\Nullcorps_WC_Gateway_Bitcoin;
use Nullcorps\WC_Gateway_Bitcoin\BrianHenryIE\WP_Logger\Logger;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	throw new \Exception( 'WPINC not defined' );
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_VERSION', '1.0.0' );

define( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_BASENAME', plugin_basename( __FILE__ ) );

define( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_PATH', dirname( __FILE__ ) );
define( 'NULLCORPS_WOOCOMMERCE_GATEWAY_BITCOIN_URL', plugins_url( dirname( __FILE__ ) ) );

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function instantiate_woocommerce_gateway_bitcoin(): API_Interface {

	$settings = new Settings();
	$logger   = Logger::instance( $settings );
	$api      = new API( $settings, $logger );

	Private_Uploads::instance( $settings );

	$plugin = new Nullcorps_WC_Gateway_Bitcoin( $api, $settings, $logger );

	return $api;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and frontend-facing site hooks.
 */
$GLOBALS['nullcorps_wc_gateway_bitcoin'] = instantiate_woocommerce_gateway_bitcoin();
