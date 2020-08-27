<?php
/**
 * Plugin Name: WooCommerce Bitcoin Gateway
 * Plugin URI: 
 * Description: Clones the "Cheque" gateway to create another manual / Bitcoin payment method. Open Source and Free,
 *              using self-custodied wallets and an emphasis on privacy & sovereighnty.
 * Author: NullCorps
 * Author URI: https://github.com/Nullcorps
 * Version: 0.001
 * Text Domain: wc-gateway-bitcoin
 * Domain Path: /i18n/languages/
 *
*  BASED HEAVILY ON THE OFFLINE PAYMENT GATEWAY EXAMPLE FROM:
 * Copyright: (c) 2015-2016 SkyVerge, Inc. (info@skyverge.com) and WooCommerce
 *
 *
 * I think this stuff below is correct, but idk
 * It's free, go nuts. I'm just sticking things together to make stuff.
 * 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-Bitcoin
 * @author    NullCorps
 * @category  Admin
 * @copyright Copyright (c) 2020, NullCorps
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This Bitcoin gateway forks the WooCommerce core "Cheque" payment gateway to create another Bitcoin payment method.
 *
 * This (Bitcoin) checkout relies on the Bitwasp php library for the maths/heavy lifting
 * and is available from here (the new one not the old one):
 * https://github.com/Bit-Wasp/bitcoin-php
 * 
 *
 * DISCLAIMER:
 * PLEASE DON'T JUDGE MY SPAGHETTI CODE TOO HARSHLY. I'M A DINASAUR
 * THE INLINE CSS WILL (PROBABLY) GET MOVED OUT TOO ONCE IT WORKS
 *
 */
 
defined( 'ABSPATH' ) or exit;


use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
   
//require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/vendor/autoload.php';


$woobtc_filespath = "woobtc"; // this could be user-settable so as more secure perhaps? might fool plugin scanners/scrapers
$woobtc_files_full_path = "";
$nl = "<BR>\n";
$checksum = "";
$woobtc_hashsecret = "fm4f90f390d8e3dusowll2uccvhjkjaslit890u"; //  << this needs to b user settable
$woobtc_dbg = false;


session_start();

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Bitcoin gateway
 */
function wc_bitcoin_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Gateway_Bitcoin';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_bitcoin_add_to_gateways' );


/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_bitcoin_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bitcoin_gateway' ) . '">' . __( 'Configure', 'wc-gateway-bitcoin' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_bitcoin_gateway_plugin_links' );


/**
 * Bitcoin Payment Gateway
 *
 * Provides a Bitcoin Payment Gateway; 
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Gateway_Bitcoin
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		NULLCORPS
 */
add_action( 'plugins_loaded', 'wc_bitcoin_gateway_init', 11 );

function wc_bitcoin_gateway_init() {

	class WC_Gateway_Bitcoin extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'bitcoin_gateway';
			$this->icon               = apply_filters('woocommerce_offline_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __( 'Bitcoin', 'wc-gateway-bitcoin' );
			$this->method_description = __( 'Allows Bitcoin payments. Orders are marked as "on-hold" when received, and marked as "completed" once the specified number of confirmations are met', 'wc-gateway-bitcoin' );
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
        
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}
	
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_bitcoin_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wc-gateway-bitcoin' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Bitcoin Payment', 'wc-gateway-bitcoin' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-bitcoin' ),
					'default'     => __( 'Offline Payment', 'wc-gateway-bitcoin' ),
					'desc_tip'    => false,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'wc-gateway-bitcoin' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-bitcoin' ),
					'default'     => __( 'Pay easily and quickly with Bitcoin', 'wc-gateway-bitcoin' ),
					'desc_tip'    => false,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-gateway-bitcoin' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions to appear above the Bitcoin checkout on the payment page"', 'wc-gateway-bitcoin' ),
					'default'     => 'Follow the instructions below to pay from your Bitcoin wallet. If you are using a mobile device you should be able to just scan the QR code, or you can copy and paste the address and amount manually.',
					'desc_tip'    => false,
				),
				
				'xpub' => array(
					'title'       => __( 'xpub', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'The xpub (master public key) for your HD wallet, which we use to locally generate the addresses to pay to (no API calls). Find it in Electrum under menu:wallet/information. It looks like xbpub2394234924loadsofnumbers', 'wc-gateway-bitcoin' ),
					'default'     => '',
					'desc_tip'    => false,
				),
				
				//'xpub-source' => array(
				//	'title'       => __( 'xpub-source', 'wc-gateway-bitcoin' ),
				//	'type'        => 'text',
				//	'description' => __( 'The api source to get fresh addresses from', 'wc-gateway-bitcoin' ),
				//	'default'     => '',
				//	'desc_tip'    => false,
				//),
				
				'exchange-rate-source' => array(
					'title'       => __( 'exchange-rate-source', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'The api source to get the exchange rate from', 'wc-gateway-bitcoin' ),
					'default'     => 'https://api-pub.bitfinex.com/v2/tickers?symbols=tBTC',
					'desc_tip'    => false,
				),
            
            
            'api-preference' => array(
					'title'       => __( 'api-preference', 'wc-gateway-bitcoin' ),
					'type'        => 'select',
					'description' => __( 'Which public API to use for getting address balances. The other will be used as a backup in case you get rate-limited.', 'wc-gateway-bitcoin' ),
					'default'     => 'Blockstream.info',
					'desc_tip'    => false,
               'options'     => array( 'Blockstream.info' => 'Blockstream.info',
  				                        'Blockchain.info' => 'Blockchain.info'),               
				),


            
            'fiat-currency' => array(
					'title'       => __( 'fiat-currency', 'wc-gateway-bitcoin' ),
					'type'        => 'select',
					'description' => __( 'The fiat equivalent currency to use - USD OR GBP', 'wc-gateway-bitcoin' ),
					'default'     => 'USD',
					'desc_tip'    => false,
               'options'     => array( 'USD' => 'USD',
  				                        'GBP' => 'GBP'),               
				),

            
            'pricing-priority' => array(
					'title'       => __( 'pricing-priority', 'wc-gateway-bitcoin' ),
					'type'        => 'select',
					'description' => __( 'Pricing primarily in "BTC" or "fiat" (e.g. Fiat-first would always show the BTC equivalent to $10, btc first would always show pricing as 0.0001 BTC and give the fiat equivalent price from the exchange rate.', 'wc-gateway-bitcoin' ),
               'default'     => 'BTC',
					'desc_tip'    => false,
               'options'     => array( 'BTC' => 'BTC',
  				                        'fiat' => 'fiat'),
				),

        
            'btc-rounding-decimals' => array(
					'title'       => __( 'btc-rounding-decimals', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'Integer, somewhere around 6 or 7 is probably ideal currently.', 'wc-gateway-bitcoin' ),
               'default'     => '7',
					'desc_tip'    => false,
				),

            'price-margin' => array(
					'title'       => __( 'price-margin', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'A percentage amount of shortfall from the shown price which will be accepted to allow for rounding errors. Recommend value between 0 and 3', 'wc-gateway-bitcoin' ),
               'default'     => '2',
					'desc_tip'    => false,
				),            

            '0-conf-threshold' => array(
					'title'       => __( '0-conf-threshold', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'Price threshold up to which 0 confirmations are acceptable. This would usually be a low value, no more than $10-$20. Price in whatever you\'ve selected as priority format e.g. 0.00001 (for btc) or 15 (for fiat) would both be acceptable entries. This should process as soon as the transaction is seen on the Bitcoin network (usually within seconds) Set to 0 to disable (i.e. ALWAYS require 1+ confs)', 'wc-gateway-bitcoin' ),
               'default'     => '0.0001',
					'desc_tip'    => false,
				),            
                    
            
        
            'addresses-cache-max' => array(
					'title'       => __( 'address-cache-max', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'Ideally how many addresses to have on standby ready to use. The busier your shop is this higher this number will need to be', 'wc-gateway-bitcoin' ),
               'default'     => '20',
					'desc_tip'    => false,
				),
            
        
            'addresses-cache-min' => array(
					'title'       => __( 'address-cache-min', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'Once we\re down to this many addresses, force generating some new ones. The busier your shop is this higher this number will probably need to be, roughly in keeping with above', 'wc-gateway-bitcoin' ),
               'default'     => '5',
					'desc_tip'    => false,
				),
            
			) );
		}
	
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
	
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting Bitcoin payment', 'wc-gateway-bitcoin' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();
			
			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
	
  } // end \WC_Gateway_Bitcoin class
}










add_shortcode('woobtc_addresses','woobtc_addresses');
function woobtc_addresses($atts,$content = null)
   {
   global $nl;
   global $woobtc_files_full_path;
   $out = "
<style>


</style>\n\n";
   
   $folder = woobtc_get_files_folder();
   
   $out .= "files folder: " . $folder . $nl;
   $woobtc_files_full_path = $folder;
   
   //$out .= $nl;

   $math = Bitcoin::getMath();
   $network = Bitcoin::getNetwork();
   $random = new Random();
   
   // By default, this example produces random keys.
   $hdFactory = new HierarchicalKeyFactory();
   $master = $hdFactory->generateMasterKey($random);
   
   // To restore from an existing xprv/xpub:
   //$master = $hdFactory->fromExtended("yourxpuborxprivhere");
   $xpub = "";
   
   $payment_gateway = WC()->payment_gateways->payment_gateways()['bitcoin_gateway'];
   $out .= '<p>Title: ' . $payment_gateway->title . '</p>';
   $out .= '<p>Description: ' . $payment_gateway->description . '</p>';
   $out .= '<p>Instructions: ' . $payment_gateway->instructions . '</p>';
   //echo "Xpub: " . $payment_gateway->get_option( 'xpub' ) . $nl;
   $out .= $nl . "<pre>" . print_r($payment_gateway->settings, true) . "</pre>" . $nl;
   
   $xpub = $payment_gateway->settings['xpub'];
   $out .= "xpub from woo settings: " . $xpub . $nl;
   
   
   
   $out .=  "Restoring from xpub ending " . substr($xpub, -4, 4) . $nl . $nl;
   $master = $hdFactory->fromExtended($xpub);
   $childKey = $master->derivePath('0/0');
   $pubKey = $childKey->getPublicKey();
   //echo "Pubkey: <pre>" . print_r($pubKey,true) . "</pre>" . $nl;
   
   //$pubkeyhash = $pubkey->getPubKeyHash();
   //$pubKey->getAddress();
   
   //echo "Master key (m)\n";
   //echo "   " . $master->toExtendedPrivateKey($network) . $nl;
   ;
   $masterAddr = new PayToPubKeyHashAddress($master->getPublicKey()->getPubKeyHash());
   
   //echo "   Address: " . $masterAddr->getAddress() . $nl . $nl;
   
   
   //echo "UNHARDENED PATH\n" . $nl;
   //echo "Derive sequential keys:\n" . $nl;
   //$key1 = $master->deriveChild(0);
   //echo " - m/0 " . $key1->toExtendedPrivateKey($network) . $nl;
   
   //$child1 = new PayToPubKeyHashAddress($key1->getPublicKey()->getPubKeyHash());
   //echo "   Address: " . $child1->getAddress() . $nl . $nl;
   
   //$key2 = $key1->deriveChild(999999);
   //echo " - m/0/999999 " . $key2->toExtendedPublicKey($network) . $nl;
   
   
   //$child2 = new PayToPubKeyHashAddress($key2->getPublicKey()->getPubKeyHash());
   //echo "   Address: " . $child2->getAddress() . $nl . $nl;
   
   
   
   $out .= "Directly derive path m/0/n stylee:" . $nl;
   // maybe make the address derivation path user configurable? would perhaps improve
   // compatibility with other wallets which might use different derivation paths?
   
   //$sameKey2 = $master->derivePath("0/1");
   //echo " - m/0/1 " . $sameKey2->toExtendedPublicKey() . $nl;
   //$child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
   //echo "   Address: " . $child3->getAddress() . $nl . $nl;
   //
   //
   //$sameKey2 = $master->derivePath("0/2");
   //echo " - m/0/1 " . $sameKey2->toExtendedPublicKey() . $nl;
   //$child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
   //echo "   Address: " . $child3->getAddress() . $nl . $nl;
   //
   //
   //$sameKey2 = $master->derivePath("0/3");
   //echo " - m/0/1 " . $sameKey2->toExtendedPublicKey() . $nl;
   //$child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
   //echo "   Address: " . $child3->getAddress() . $nl . $nl;
   //
   
   
   $addresses = "";
   
   for ($n=0;$n<50;$n++)
      {
      $sameKey2 = $master->derivePath("0/".$n);
      //echo " - m/0/" . $n . ": " . $sameKey2->toExtendedPublicKey() . $nl;
      $child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
      $out .= "   Address m/0/" . $n . ": " . $child3->getAddress() . $nl;
      $addresses .= $child3->getAddress() . "\n";
      }
   
   
   $out .= "Saving addresses to text file.." . $nl;
   
   file_put_contents( $woobtc_files_full_path . "/addresses_fresh.txt", $addresses );
   //echo "HARDENED PATH (disabled bc no privkeys)\n";
   //$hardened2 = $master->derivePath("0/999999'");
   
   //$child4 = new PayToPubKeyHashAddress($hardened2->getPublicKey()->getPubKeyHash());
   //echo " - m/0/999999' " . $hardened2->toExtendedPublicKey() . $nl;
   //echo "   Address: " . $child4->getAddress() . $nl . $nl;
   

   return do_shortcode($out);
   }











function woobtc_get_files_folder()
   {
   global $woobtc_filespath;
   global $nl;
   
   $upload = wp_upload_dir(null, true);
   //print_r($upload);   
   $upload_base = $upload['basedir'];
   $out = "";
   if ( file_exists($upload_base . "/" . $woobtc_filespath) )
      {
      $out .= "Folder exists" . $nl;
      // CHECK FOR .HTACCESS
      }
   else
      {
      mkdir ($upload_base . "/" . $woobtc_filespath);
      $out .= "Folder " . $upload_base . "/" . $woobtc_filespath . " created" . $nl;
      }

   if ( file_exists($upload_base . "/" . $woobtc_filespath . "/.htaccess") ) // SWITCHED OFF THIS LOOP FOR NOW - TESTING DOWNLOADABLE PDFS (now that we've got longer random keys)
      {
      // all is fine, htaccess exists
      $out .= "All is fine, .htaccess exists" . $nl;
      }
   else
      {
      $out .= "Need to make .htaccess" . $nl;
      $httmp = "Order Allow,Deny
Deny from All
";
      file_put_contents($upload_base . "/" . $woobtc_filespath . "/.htaccess", $httmp);
      $out .= ".htaccess created" . $nl;
      }    
   
   $out .= "Return: " . $upload_base . "/" . $woobtc_filespath . $nl;
   //return $out;
   return $upload_base . "/" . $woobtc_filespath;    
   }










add_action( 'woocommerce_thankyou', 'woobtc_redirect_custom');
  
function woobtc_redirect_custom( $order_id )
	{
	global $nl;
   global $woobtc_dbg;
   
	$order = wc_get_order( $order_id );
  
   $url = '?page_id=139&view-order=' . $order_id;
   
   //if ( $order->status != 'failed' ) {
	if ( $order->status == 'completed' )
		{
		wp_safe_redirect( $url );
      exit;
		}
	else
		{

      $site_url = get_site_url();
      echo "<div style=\"background-color: #ffcccc; padding: 12px; line-height: 160%; \">";
		echo "<center><strong>Pay now with Bitcoin</strong><a name=woobtc></a>\n" . $nl;
      echo "<img src=\"" . $site_url . "/wp-content/plugins/woocommerce-gateway-bitcoin/bitcoin.png\" style=\"width: 200px;\">" . $nl;
      echo "<div style=\"font-size: 18px; font-weight: bold; \">ORDER STATUS: " . $order->status . "</div>\n";
		echo "<div style=\"font-size: 18px; font-weight: normal; \">Once payment is completed below<br>you will be taken to your downloads</div></center>" . $nl;
		
      //echo "- check exchange rate to get price in btc" . $nl;
      $payment_gateway = WC()->payment_gateways->payment_gateways()['bitcoin_gateway'];
      //echo '<p>Title: ' . $payment_gateway->title . '</p>';
      //echo '<p>Description: ' . $payment_gateway->description . '</p>';
      //echo '<p>Instructions: ' . $payment_gateway->instructions . '</p>';
      //echo "Xpub: " . $payment_gateway->get_option( 'xpub' ) . $nl;
      $xpub = $payment_gateway->settings['xpub'];
      $fiat = $payment_gateway->settings['fiat-currency'];
      $roundbtc = $payment_gateway->settings['btc-rounding-decimals'];

      $conf_threshold_0 = $payment_gateway->settings['0-conf-threshold'];
      $pricing_priority = $payment_gateway->settings['pricing-priority'];
      $api_preference = $payment_gateway->settings['api-preference'];
      
      $fiat_symbol = "";
      $btc_symbol = "฿";
      
      if ($fiat == "USD")
         { $fiat_symbol = "$"; }
      elseif ($fiat == "GBP")
         { $fiat_symbol = "&pound;"; }
      
      
      $dowaiting = "";
      if ( isset($_POST['amount']) )
         { $amount = $_POST['amount']; }
      if ( isset($_POST['address']) )
         { $address = $_POST['address']; }
      if ( isset($_POST['checksum']) )
         { $checksum = $_POST['checksum']; }

      if ($amount <> "" && $address <> "" && $checksum <> "")
         {
         $dowaiting = true;
         //echo "Dowaiting: " . $dowaiting . $nl;
         }
      else
         {
         //echo "No dowaiting" . $nl;
         }
         
         
      if(!$dowaiting)
         {
         if(!isset($_SESSION['exr']) || $_POST['refresh'] == "1" )
            {
            $exr = woobtc_get_exchange_rate();
            }
         else
            {
            $exr = $_SESSION['exr'];
            }

         $order = wc_get_order( $order_id );
         //echo "<pre>" . print_r($order->total, true) . "</pre>" . $nl;
         
         $price = $order->total;
         $btcprice = $price / $exr;

         //echo "- Get Confirmations required for the price level" . $nl;
         //echo "<pre>" . print_r($payment_gateway->settings, true) . "</pre>" . $nl;
         
         //echo "Pricing priority: " . $pricing_priority . $nl;
         
         $confs_req = 1;
         
         if ( $pricing_priority == "fiat" )
            {
            //echo "in: Pricing priority fiat" . $nl;
            //echo "0-conf threshold: " . $conf_threshold_0 . $nl;
            
            if ($price <= $conf_threshold_0)
               { $confs_req = 0; }                
            }
         elseif ( $pricing_priority == "BTC" )
            {
            //echo "in: Pricing priority: BTC" . $nl;
            //echo "0-conf threshold: " . $conf_threshold_0 . $nl;
                             
            if ($btcprice <= $conf_threshold_0)
               { $confs_req = 0; }        
            }
         else
            {
            echo "This shouldn't happen" . $nl;
            }
         echo "<center><span title=\"0 confirmations should normally process almost instantly, 1 confirmation could take 10-20 mins. It all depends on how busy the Bitcoin network is and your fee\">Confirmations required for this transaction: " . $confs_req . "</span>" .$nl;
         echo "We support segwit and RBF transactions :)" . $nl;
         //echo "0 confirmations should normally process almost instantly, 1 confirmation could take 10-20 mins. 2 and above can take longer depending on how busy the Bitcoin network is" . $nl;
         //echo "<marquee id=waitingmessage ascrolldelay=500 scrollamount=3>0 confirmations should normally process almost instantly, 1 confirmation could take 10-20 mins. 2 and above can take longer depending on how busy the Bitcoin network is</marquee>";
         echo "</center>";
         echo $nl;
         
         
         $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
         $current_url = str_replace("&refresh=", "", $current_url);
         //echo "Current url: " . $current_url . $nl;
         //$exr = woobtc_get_exchange_rate();
         //echo $nl;
         
         echo "<div style=\"display: none;\"><form name=woobtc_refreshprice action=\"" . $current_url . "#woobtc\" method=post></div>\n";
         echo "<input type=hidden name=refresh value=1>\n";
         
         echo "<center><div style=\"font-size: 16px; font-weight: normal; padding-bottom: 0px;\">Order total: " . $fiat_symbol . $price .  $nl;
         echo "Price in BTC: " . $btc_symbol . round($btcprice, $roundbtc) . " (" . number_format(round($btcprice, $roundbtc) * 100000000) . " sats)</div>";
         echo "Exchange rate: " . $fiat_symbol . number_format($exr,2) . $nl;
         echo "<a href=\"#\" onclick=\"document.forms.woobtc_refreshprice.submit(); return false;\">Refresh exchange rate</a>" . $nl;
         echo "<input type=submit value=\"Refresh exchange rate\" style=\"padding: 4px; display: none;\">\n";
         echo "<div style=\"display: none;\"></form></div></center>\n";
         echo $nl;
         

         //echo $nl . "<pre>" . print_r($payment_gateway->settings, true) . "</pre>" . $nl;      
         
         // echo "- get a fresh address" . $nl;
         //echo "- - woo order id: " . $order_id . $nl;
         
         $btcaddress = get_post_meta($order_id, "woobtc_address");
         $btcaddress = $btcaddress[0];
         // CLEAR POST META
         //update_post_meta( $order_id, 'woobtc_address', "" );
         //echo "Btcaddress from post meta: " . print_r($btcaddress,true) . $nl;
         
         if ($btcaddress <> "")
            {
            if ($woobtc_dbg) { echo "<center>DEBUG: Found address in postmeta</center>" . $nl; }
            //echo $btcaddress . "</center>" . $nl;
            }
         else
            {
            if ($woobtc_dbg) { echo "<center>Fetching new address & storing to the order</center>" . $nl; }
            $add = woobtc_get_fresh_address($order_id);
            $out .= "CHECK BALANCES OF NEW ADDRESS ARE 0 BEFORE SAVING TO ORDER" . $nl;
            update_post_meta( $order_id, 'woobtc_address', $add );
            $btcaddress = $add;
            }
         echo "
<script language=javascript>
function woobtc_clearfield(f)
   {
   //alert('clear field with name: ' + f);
   var t = document.getElementById(f); 
   setTimeout(\"document.getElementById('\"+f+\"').innerHTML = '&nbsp;'\",1000);
   }

</script>";
         
         echo "<center><div style=\"padding-bottom: 8px; \"><span title=\"Please only send BITCOIN, which always has the ticker BTC, not any of the many clones. If you send coins other than bitcoin (e.g. Bitcoin Cash, BSV (lol) then those coins will be lost and your order will still not be paid.)\">Please send BITCOIN/BTC ONLY to this address:</span>" . $nl;
         echo "<input type=text value=\"" . $btcaddress . "\" style=\"width: 440px; padding: 4px; font-size: large; text-align: center; \"  onclick=\"this.setSelectionRange(0, 99999); document.execCommand('copy'); document.getElementById('woobtc_label_address').innerHTML = 'Address copied'; woobtc_clearfield('woobtc_label_address'); \" onmouseover=\"document.getElementById('woobtc_label_address').innerHTML = 'Click to copy';\"  onmouseout=\"document.getElementById('woobtc_label_address').innerHTML = '&nbsp;';\">" . $nl;
         echo "<span id=woobtc_label_address style=\"font-size: 12px;\">&nbsp;</span></div></center>";
         
         echo "<center>BTC to send:<br><input type=text value=\"" . round($btcprice, $roundbtc) . "\" style=\"width: 300px; padding: 4px; font-size: large; text-align: center; \" onclick=\"this.setSelectionRange(0, 99999); document.execCommand('copy'); document.getElementById('woobtc_label_amount').innerHTML = 'Amount copied'; woobtc_clearfield('woobtc_label_amount'); \"  onmouseover=\"document.getElementById('woobtc_label_amount').innerHTML = 'Click to copy';\"  onmouseout=\"document.getElementById('woobtc_label_amount').innerHTML = '&nbsp;';\">" . $nl;
         echo "<span id=woobtc_label_amount style=\"font-size: 12px;\">&nbsp;</span></center>" . $nl;
         
         //echo $nl;
             
         if ($btcaddress <> "")
            {
            
            //echo "- show QR code of address, amount etc" . $nl;
            //echo "<center><img src=\"https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=" . $btcaddress . "\"></center>" . $nl;
            
            echo "<script src=\"" . $site_url . "/wp-content/plugins/woocommerce-gateway-bitcoin/kjua-0.1.1.min.js\"></script>\n";
            echo "<center><div style=\"width: 420px; text-align: center;\"><div id=oink style=\"border: 1px solid black; background-color: #ffffff; width; 420px; height: 420px; padding-top: 0px; text-align: center;\"></div></div></center>\n";
            echo "<script language-javascript>
   var url = '" . $btcaddress . "';
   //var opts = \"\";
   //opts = opts + \"render: 'image', crisp: true, minVersion: 1, ecLevel: 'L', size: 400, ratio: null, fill: '#333', back: '#fff', text: 'Pay with Bitcoin', rounded: 0, \";
   //opts = opts + \"quiet: 0, mode: 'plain', mSize: 30, mPosX: 50, mPosY: 50, label: 'label test', fontname: 'sans', fontcolor: '#333', image: null\";
   
   //var el = kjua({text: 'hello!'});
   var el = kjua({text: url, label: 'Pay with Bitcoin', size: 400, crisp: true, back: '#fff' });
   //document.querySelector('body').appendChild(el);
   //document.getElementById('oink').appendChild(el);
   document.getElementById('oink').appendChild(el);
   </script>";
         
            //echo "- button to say paid" . $nl;
            $checksum = woobtc_create_checksum($btcaddress, round($btcprice, $roundbtc) );
            //echo "- - checksum: " . $checksum . $nl;
            //echo "Exchange rate: " . $fiat . " " . number_format($exr,2) . $nl . $nl;
            echo $nl;
            echo "<center><form name=woobtc_paid action=\"" . $current_url . "\" method=post>\n";
            echo "<input type=hidden name=amount value=\"" . round($btcprice, $roundbtc)  . "\">\n";
            echo "<input type=hidden name=address value=\"" . $btcaddress . "\">\n";
            echo "<input type=hidden name=checksum value=\"" . $checksum . "\">\n";
            echo "<input type=submit value=\"Click here once paid\" style=\"padding: 8px; background-color: #ccffcc; colour: white;\">\n";
            echo "</form></center>\n";
            echo $nl;
            
            //echo "- - pass amount with the 'I have paid' click, along with checksum of address+amount and attach those (btc price, checksum of add+amount) to the order id postmeta" . $nl;
            }
         else
            {
            echo "Missing payment address - this shouldn't happen - please email support with your order number and perhaps a screenshot of this page." . $nl;
            }
         echo "</div>";
         echo $nl;
         }
      else
         {
         //echo "DOING DOWAITING" . $nl;
         echo "<center><img src=\"" . $site_url . "/wp-content/plugins/woocommerce-gateway-bitcoin/waiting.gif\" style=\"width: 200px;\"></center>";
         //echo "- check balance of the address, refresh based on expected wait time/confs" . $nl;
         echo $nl;
         
         
         echo "Check the checksum is valid: ";
         
         $checksum_verify = woobtc_create_checksum($address, $amount);
         if ($checksum == $checksum_verify)
            { echo "Checksum verified OK" . $nl; }
         else
            { echo "Checksum verification FAILED" . $nl; }

         echo "Address: " . $address . $nl;
         echo "Amount: " . $amount . $nl;
         
         $confs_req = 1;
         
         $order = wc_get_order( $order_id );
         //echo "<pre>" . print_r($order->total, true) . "</pre>" . $nl;
         
         $price = $order->total;
         echo "Order total: " . $fiat_symbol . $price . $nl;
         
         if ( $pricing_priority == "fiat" )
            {
            //echo "in: Pricing priority fiat" . $nl;
            //echo "0-conf threshold: " . $conf_threshold_0 . $nl;
            if ($price <= $conf_threshold_0)
               { $confs_req = 0; }                
            }
         elseif ( $pricing_priority == "BTC" )
            {
            //echo "in: Pricing priority: BTC" . $nl;
            //echo "0-conf threshold: " . $conf_threshold_0 . $nl;
            if ($btcprice <= $conf_threshold_0)
               { $confs_req = 0; }        
            }
         else
            {
            echo "This shouldn't happen" . $nl;
            }
         echo "Confirmations required: " . $confs_req . $nl;
         //echo "0 confirmations should normally process almost instantly, 1 confirmation could take 10-20 mins. 2 and above can take longer depending on how busy the Bitcoin network is" . $nl;
         //echo "<marquee id=waitingmessage ascrolldelay=500 scrollamount=3>0 confirmations should normally process almost instantly, 1 confirmation could take 10-20 mins. 2 and above can take longer depending on how busy the Bitcoin network is</marquee>";

         echo "Required amount: " . $amount . $nl;
         //echo $nl;
         
         $getinfo_failed = false;
         echo "API-preference: " . $api_preference . $nl;
         $confirmed = "";
         $unconfirmed = "";
                  
         
         if ($api_preference == "Blockchain.info")
            {
            // BLOCKCHAIN.INFO STUFF
            if ($confs_req === 0)
               {
               $unconfirmed = woobtc_get_address_balance_bc($address, true);
               }
            else
               {
               $confirmed = woobtc_get_address_balance_bc($address, false);
               $unconfirmed = woobtc_get_address_balance_bc($address, true);
               }
            }
         else
            {
            // BLOCKSTREAM.INFO STUFF
            if ($confs_req === 0)
               {
               $unconfirmed = woobtc_get_address_balance_bs($address, true);
               }
            else
               {
               $confirmed = woobtc_get_address_balance_bs($address, false);
               $unconfirmed = woobtc_get_address_balance_bs($address, true);
               }
            }
            

         if ($unconfirmed == "")
               {
               echo "It's possible our server might have been rate-limited from the public API we use to check balances. Attempting to use the other API as a backup. This shouldn't really happen." . $nl;
               $getinfo_failed = true;
               }
         

// FAILOVER SECTION

         if ($api_preference == "Blockchain.info")
            {
            // BLOCKSTREAM.INFO STUFF
            if ($confs_req === 0)
               {
               $unconfirmed = woobtc_get_address_balance_bs($address, true);
               }
            else
               {
               $confirmed = woobtc_get_address_balance_bs($address, false);
               $unconfirmed = woobtc_get_address_balance_bs($address, true);
               }
            }
         else
            {
            // BLOCKCHAIN.INFO STUFF
            if ($confs_req === 0)
               {
               $unconfirmed = woobtc_get_address_balance_bc($address, true);
               }
            else
               {
               $confirmed = woobtc_get_address_balance_bc($address, false);
               $unconfirmed = woobtc_get_address_balance_bc($address, true);
               }
            }

         
         //echo "Required confirmations: " . $confs_req . $nl;
         echo "Unconfirmed balance: " . ($unconfirmed) . $nl;
         echo "Confirmed balance: " . number_format($confirmed,8) . $nl;
         //echo $nl;
         
         if ($getinfo_failed)
            { echo "<strong>LOOKS LIKE WE MIGHT HAVE BEEN RATE LIMITED ON YOUR CHOICE OF API (" . $api_preference . "), USE THE OTHER ONE" . $nl; }

               

        
         $paid = false;
         
         if ($confs_req == 0)
            {
            if ( ($unconfirmed) >= $amount )
               {
               $paid = true;
               }
            }
         
         if ($confs_req == 1)
            {
            if ( ($confirmed) >= $amount )
               { $paid = true; }
            }
                   
         if ($paid == 1)
            {
            echo $nl;
            echo "<div style=\"font-size: 24px; font-weight: bold;\">PAID YO!</div>";      
            echo "<div style=\"display: none\"><audio controls autoplay><source src=\"" . $site_url . "/wp-content/plugins/woocommerce-gateway-bitcoin/kerching.mp3\" type=\"audio/mpeg\">Your browser does not support the audio element.</audio></div>";
            echo $nl;
            echo "Updating order status to PAID" . $nl;
            $order = wc_get_order( $order_id );
            
            // Mark as on-hold (we're awaiting the payment)
            $order->update_status( 'completed', __( 'Awaiting Bitcoin payment', 'wc-gateway-bitcoin' ) );
            echo "Reloading the page and taking you to your order... :)";
            echo "<script language=javascript>setTimeout('location.href=\"" . $url . "\"',1000);</script>";
            }
         else
            {
            echo "This page will auto-refresh every 2mins and should make a noise when the payment is received." . $nl;
            //echo "<script language=javascript>setTimeout('location.reload()', 30000);</script>";
            echo $nl;
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $current_url = str_replace("&refresh=", "", $current_url);
            //echo "current url: " . $current_url . $nl;
            echo "<center><form name=woobtc_paid action=\"" . $current_url . "#woobtc\" method=post>\n";
            echo "<input type=hidden name=amount value=\"" . $amount  . "\">\n";
            echo "<input type=hidden name=address value=\"" . $address . "\">\n";
            echo "<input type=hidden name=checksum value=\"" . $checksum . "\">\n";
            echo "<input type=button value=\"Back to payment details\" onclick=\"location.href='" . $current_url . "';\" style=\"padding: 8px;\" title=\"Dont worry, you won't break anything by going bacl. The payment address wont change and your existing payment wont get lost by going back. The payment address is now linked to this order number and each order gets issues its own address\">\n";
            echo "<input type=submit value=\"Click to refresh\" style=\"padding: 8px; background-color: #ccffcc; colour: white;\">\n";
            echo "</form></center>\n";
            echo "<script language=javascript>setTimeout('document.forms.woobtc_paid.submit()',120000)</script>";
            echo $nl;            
            }
         echo "</div>";
         echo $nl;
         }
      }
	}






function woobtc_get_address_balance_bc($address, $confirmed)
   {
   // confirmed true/false, false for unconfirmed balance
   if ($confirmed)
      {
      $confirmed_url_bc = "https://blockchain.info/q/addressbalance/" . $address . "?confirmations=1";
      $balance_bc = file_get_contents($confirmed_url_bc);
      if ($balance_bc > 0)
         { $balance_bc = $balance_bc / 100000000; }
      return $balance_bc;
      }
   else
      {
      $unconfirmed_url_bc = "https://blockchain.info/q/addressbalance/" . $address . "?confirmations=0";
      $unconfirmed_bc = file_get_contents($unconfirmed_url_bc);
      if ($unconfirmed_bc > 0)
         { $unconfirmed_bc = $unconfirmed_bc / 100000000; }
      return $unconfirmed_bc;
      }
   }


function woobtc_get_address_balance_bs($address, $confirmed)
   {
   global $nl;
   // confirmed true/false, false for unconfirmed balance
   $address_info_url_bs = "https://blockstream.info/api/address/" . $address;
   //echo "URL: " . $address_info_url_bs . $nl;
   $address_infoj = file_get_contents($address_info_url_bs);
   $address_info = json_decode($address_infoj, true);
         
   //echo "<pre>" . print_r($address_info, true) . "</pre>" . $nl;
   if ($confirmed)
      {
      $confirmed_balance_bs = ($address_info['chain_stats']['funded_txo_sum'] - $address_info['chain_stats']['spent_txo_sum'])/100000000;
      //echo "Confirmed balance: " . number_format($confirmed_balance_bs,8) . $nl;
      return number_format($confirmed_balance_bs,8);
      }
   else
      {
      $unconfirmed_balance_bs = ($address_info['mempool_stats']['funded_txo_sum'] - $address_info['mempool_stats']['spent_txo_sum'])/100000000;
      //echo "Unconfirmed balance: " . number_format($unconfirmed_balance_bs,8) . $nl;
      return number_format($unconfirmed_balance_bs,8);
      }
   }







function woobtc_get_fresh_address($order_id)
   {
   global $nl;
   $folder = woobtc_get_files_folder();
   
   $addpath = $folder . "/addresses_fresh.txt";
   $usedpath = $folder . "/addresses_used.txt";
   $out .= "Get fresh address for order " . $order_id . $nl;
   $out .= "Files path for addresses: " . $folder . $nl;
   
   $adds = "";
   $aradds = [];
   $nextadd = "";
   
   if(file_exists($addpath))
      {
      $adds = file_get_contents($addpath);
      $aradds = explode("\n",$adds);
      $nextadd = $aradds[0];
      $out .= "Next address: " . $nextadd . $nl;
      $addsleft = str_replace($aradds[0] . "\n","", $adds);
      $out .= "Addresses left: " . $nl . $addsleft . $nl;
      $out .= "Writing remaining fresh addresses back to file" . $nl;
      file_put_contents($addpath, $addsleft);
      
      $out .= "Add the current address to the used addresses list" . $nl;
      file_put_contents($usedpath, $nextadd . "-WOO_ORDER: " . $order_id . "\n", FILE_APPEND | LOCK_EX);
      
      return $nextadd;
      }
   else
      {
      return "ERROR: missing addresses file";
      }
   return $out;
   }






function woobtc_get_exchange_rate()
   {
   //$url = "https://www.bitstamp.net/api/ticker/";
   //$fgc = file_get_contents($url);
   //$json = json_decode($fgc, TRUE);
   //$price = (int)$json["last"];

   $payment_gateway = WC()->payment_gateways->payment_gateways()['bitcoin_gateway'];
   //echo '<p>Title: ' . $payment_gateway->title . '</p>';
   //echo '<p>Description: ' . $payment_gateway->description . '</p>';
   //echo '<p>Instructions: ' . $payment_gateway->instructions . '</p>';
   //echo "Xpub: " . $payment_gateway->get_option( 'xpub' ) . $nl;
   //echo $nl . "<pre>";
   //print_r($payment_gateway->settings);
   //echo "</pre>" . $nl;
   
   $exr_src = $payment_gateway->settings['exchange-rate-source'];
   $exr_cur = $payment_gateway->settings['fiat-currency'];
   $url2 = $exr_src . $exr_cur;
   //echo "URL2: " . $url2 . "<BR>";
   //$url = "https://api-pub.bitfinex.com/v2/tickers?symbols=tBTCUSD";
   $url = $url2;
   $fgc = file_get_contents($url);
   $json = json_decode($fgc, TRUE);
   $xprice = $json[0][7];
   
   //echo $price;

   $_SESSION['exr'] = $xprice; 
   return $xprice;    
   }

   
 

	
function woobtc_create_checksum($address, $amt)
	{
	global $nl;
	global $woobtc_hashsecret;
	$str = trim($address) . trim($amt) . trim($hashsecret);
	//echo "#" . $address . "#" . $nl;
	//echo "#" . $amt . "#" . $nl;
	$out .= hash("ripemd160", $str);
	//echo trim($out);
	return $out;
	}
	 


