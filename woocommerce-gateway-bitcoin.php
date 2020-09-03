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
 * PLEASE DON'T JUDGE MY SPAGHETTI CODE TOO HARSHLY. I'M A DINOSAUR
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
$woobtc_dbg = true;


require_once 'i_subs.php';

require_once 'i_checkout.php';

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
                    

        
            'addresses-cache-min' => array(
					'title'       => __( 'address-cache-min', 'wc-gateway-bitcoin' ),
					'type'        => 'text',
					'description' => __( 'Once we\re down to this many addresses, force generating some new ones. The busier your shop is this higher this number will probably need to be, roughly in keeping with above. Default to 50', 'wc-gateway-bitcoin' ),
               'default'     => '50',
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
   //$out .= $nl . "<pre>" . print_r($payment_gateway->settings, true) . "</pre>" . $nl;
   
   $xpub = $payment_gateway->settings['xpub'];
   $out .= "xpub from woo settings ending: " . substr($xpub, -4, 4)  . $nl;
   
   
   
   $api_preference = $payment_gateway->settings['api-preference'];
   $out .= "API preference: " . $api_preference . $nl;
   
   $address_cache_min = $payment_gateway->settings['addresses-cache-min'];
   $out .= "Address cache min: " . $address_cache_min . $nl;
   
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
   
   
   $out .= "Figure out the starting point m/0/?" . $nl;
   $freshpath = $woobtc_files_full_path . "/addresses_fresh.txt";
   $usedpath = $woobtc_files_full_path . "/addresses_used.txt";
   $lastfresh = "";
   $fresh_cleaned = "";
   $startat = 0;
   $rollback = 20;
   
   $out .= "- CLEAN THE FRESH ADDRESSES (i.e. make sure no used appoear in fresh)" . $nl;
   
   if ( file_exists($freshpath) && file_exists($usedpath) )
      {   
      $fresh = file_get_contents( $freshpath );
      $arfresh = explode("\n", $fresh);
      $out .= "Fresh addresses found: " . count($arfresh) . $nl;
      $used = file_get_contents( $woobtc_files_full_path . "/addresses_used.txt" );
      $arused = explode("\n", $used);
      $out .= "Used addresses found: " . count($arused) . $nl;
      $out .= "Comparing: " . $nl;
      $cnt = 0;
      
      $out .= "- sanity check: " . strpos($used,"1FAL5UtkibjspAzj819onPvrb8xvU9Zcvr") . $nl . $nl;
      
      foreach($arfresh as $freshadd)
         {
         $out .= "checking: " . $freshadd . $nl;   
         $tmp = strpos($used,$freshadd);
         $out .= "- " . $tmp . $nl;
         if ( $tmp || $tmp === 0 )
            { $out .= "THIS IS USED, REMOVE" . $nl;}
         else
            {
            if (trim($freshadd) <> "")
               {
               //array_push($arfresh_cleaned, trim($freshadd));
               $out .= "UNUSED, OK TO KEEP" . $nl;
               $fresh_cleaned .= trim($freshadd) . "\n";
               $cnt++;
               }
            else
               {
               $out .= "it's blank" . $nl;
               }
            }
         $out .= $nl;
         }
      $out .= $nl;
      
      $out .= "<pre>" . print_r($fresh_cleaned, true) . "</pre>" . $nl;
      $out .= "Fresh addresses cleaned, saving.." . $nl;
      file_put_contents($freshpath, $fresh_cleaned, LOCK_EX);
      $tmp2 = explode("\n", $fresh_cleaned);
      $out .= "Cleaned addresses: " . count($tmp2) . $nl;
      }
   else
      {
      $out .= "No fresh or used addresses file, starting from scratch I guess" . $nl;
      }
   
   
   $out .= "- Maybe dedupe the used address pile? idk, might be better raw" . $nl;
   
   
   
   $out .= "- get the last address in the fresh addresses (if present)" . $nl;
   
   if ( file_exists($freshpath) )
      {   
      $fresh = file_get_contents( $freshpath );
      $fresh = file_get_contents( $freshpath );
      
      $arfresh = explode("\n", $fresh);
      $out .= "Fresh addresses found: " . count($arfresh) . $nl;
      $lastfresh = $arfresh[(count($arfresh)-2)];
      $out .= "Last fresh address: " . $lastfresh . $nl;
      }
   else
      {
      $out .= "No fresh addresses file, starting from scratch I guess" . $nl;
      }
   
   $out .= $nl;
   $out .= "- get an idea where to start looking for that address, and failing that, get the approx starting point from the used addresses (if it exists)" . $nl;
   
   if ( file_exists($usedpath) )
      {
      $used = file_get_contents( $woobtc_files_full_path . "/addresses_used.txt" );
      $arused = explode("\n", $used);
      $usedcount = count($arused);
      $out .= "Used addresses found: " . $usedcount . $nl;
      }
   else
      {
      $out .= "No used addresses file, starting from scratch I guess" . $nl;
      }
   
   $out .= "- somehow decide where to start deriving...(roll back a bit to be sure?)" . $nl;
   
   
   $new_list = "";
   
   if ($lastfresh <> "" && $usedcount)
      {
      $out .= "Ok so probably wanna start looking for lastfresh about " . $usedcount . " but roll back " . $rollback . " to be safe. " . $nl;
      $startat = $usedcount - $rollback;
      if($startat < 0)
         { $startat = 0; }
      }
   else
      {
      $out .= "Missing lastfresh or usedcount, likely missing files. Perhaps start over from 0." . $nl;
      $new_list = true;
      $startat = 0;
      }
   
   $out .= $nl; 
   
   $out .= "Start deriving at: " . $startat . $nl;
   $out .= "Address cache min: " . $address_cache_min . $nl;
   
   $addresses = "";
   
   if ($new_list)
      {
      for ($n=$startat;$n<($startat+$address_cache_min);$n++)
         {
         $sameKey2 = $master->derivePath("0/".$n);
         //echo " - m/0/" . $n . ": " . $sameKey2->toExtendedPublicKey() . $nl;
         $child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
         $add = $child3->getAddress();
         $out .= "   Address m/0/" . $n . ": " . $add . $nl;
         $addresses .= $add . "\n";
         }
      $out .= "Check before writing:<br><pre>" . $addresses . "</pre>" . $nl;
      $out .= "Saving addresses to text file.." . $nl;
      
      $out .= "- new addresses file" . $nl;
      file_put_contents( $woobtc_files_full_path . "/addresses_fresh.txt", $addresses, LOCK_EX );
      }
   else
      {
      $out .= "Start at: " . $startat . $nl;
      for ($n=$startat;$n<($startat+50);$n++)
         {
         $sameKey2 = $master->derivePath("0/".$n);
         //echo " - m/0/" . $n . ": " . $sameKey2->toExtendedPublicKey() . $nl;
         $child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
         $add = $child3->getAddress();
         $out .= "   Address m/0/" . $n . ": " . $add;
         if ($add == $lastfresh)
            {
            $out .= " &lt;= THIS ONE" . $nl;
            $startat = $n+1;
            break;
            }
         else
            {
            $out .= $nl;
            }
         
         $addresses .= $add . "\n";
         }
       
      $out .= "Really start at: " . $startat . $nl;     
      
      $out .= "Address cache min: " . $address_cache_min . $nl;
      
      
      $fresh = file_get_contents( $freshpath );
      $arfresh = explode("\n", $fresh);
      $freshcount = count($arfresh)-1;
      $out .= "Freshcount: " . $freshcount . $nl;
      
      
      $adds_needed = 0;
      
      if ($freshcount > 0 && $freshcount < $address_cache_min)
         { $adds_needed = $address_cache_min - $freshcount; }
      
      
      $out .= "Addresses to generate: " . $adds_needed . $nl;
      
      if ($adds_needed > 0)
         {
         $addresses = "";
         for ($n=$startat; $n<($startat + $adds_needed); $n++)
            {
            $sameKey2 = $master->derivePath("0/".$n);
            //echo " - m/0/" . $n . ": " . $sameKey2->toExtendedPublicKey() . $nl;
            $child3 = new PayToPubKeyHashAddress($sameKey2->getPublicKey()->getPubKeyHash());
            $add = $child3->getAddress();
            $out .= "   Address m/0/" . $n . ": " . $add . $nl;
            if ($add == $lastfresh)
               {
               $out .= " ====== THIS ONE =====" . $nl;
               $startat = $n+1;
               break;
               }
            $addresses .= $add . "\n";
            }         
         
         $out .= "<pre>" . $addresses . "</pre>" . $nl;
         
         file_put_contents( $freshpath, $addresses, FILE_APPEND | LOCK_EX );
         }
      else
         {
         $out .= "<b>No more addresses needed, you're good!</b>" . $nl;
         }
      }
   //$out .= "LOTS MORE CHECKING BEFORE SAVING";
   
   
   
   $out .= $nl . $nl . $nl . "<hr>" . $nl;
   
   $out .= "Checking fresh addresses (get next fresh): " . woobtc_get_fresh_address2("", $api_preference) . $nl;
   
   
   //echo "HARDENED PATH (disabled bc no privkeys)\n";
   //$hardened2 = $master->derivePath("0/999999'");
   
   //$child4 = new PayToPubKeyHashAddress($hardened2->getPublicKey()->getPubKeyHash());
   //echo " - m/0/999999' " . $hardened2->toExtendedPublicKey() . $nl;
   //echo "   Address: " . $child4->getAddress() . $nl . $nl;
   

   return do_shortcode($out);
   }











