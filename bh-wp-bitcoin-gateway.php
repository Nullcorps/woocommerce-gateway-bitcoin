<?php
/**
 * This Bitcoin gateway relies on the BitWasp bitwasp/bitcoin-php PHP library for the maths/heavy lifting.
 *
 * @see https://github.com/Bit-Wasp/bitcoin-php
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           brianhenryie/bh-wp-bitcoin-gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Bitcoin Gateway
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wp-bitcoin-gateway/
 * Description:       Accept Bitcoin payments using self-custodied wallets, and no external account. Calculates wallet addresses locally and uses open APIs to verify payments. For an emphasis on privacy & sovereignty.
 * Version:           2.0.0-beta-4
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Nullcorps, BrianHenryIE
 * Author URI:        https://github.com/Nullcorps/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       bh-wp-bitcoin-gateway
 * Domain Path:       /languages
 * WC tested up to:   7.3.0
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Wallet_Factory;
use BrianHenryIE\WP_Bitcoin_Gateway\API\API;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Settings;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes\Activator;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Includes\Deactivator;
use BrianHenryIE\WP_Bitcoin_Gateway\WP_Logger\Logger;
use Exception;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	throw new Exception( 'WPINC not defined' );
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BH_WP_BITCOIN_GATEWAY_VERSION', '2.0.0' );

define( 'BH_WP_BITCOIN_GATEWAY_BASENAME', plugin_basename( __FILE__ ) );
define( 'BH_WP_BITCOIN_GATEWAY_PATH', trailingslashit( __DIR__ ) );
define( 'BH_WP_BITCOIN_GATEWAY_URL', trailingslashit( plugins_url( plugin_basename( __DIR__ ) ) ) );

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

	$crypto_wallet_factory  = new Bitcoin_Wallet_Factory();
	$crypto_address_factory = new Bitcoin_Address_Factory();

	$api = new API( $settings, $logger, $crypto_wallet_factory, $crypto_address_factory );

	new BH_WP_Bitcoin_Gateway( $api, $settings, $logger );

	return $api;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and frontend-facing site hooks.
 */
$GLOBALS['bh_wp_bitcoin_gateway'] = instantiate_woocommerce_gateway_bitcoin();
