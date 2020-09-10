<?php





add_action( 'woocommerce_thankyou', 'woobtc_redirect_custom');
  
function woobtc_redirect_custom( $order_id )
	{
	global $nl;
   //global $woobtc_dbg;
   $woobtc_dbg = false;
   
	$order = wc_get_order( $order_id );
  
   //$url = '?page_id=139&view-order=' . $order_id;
   $url = '/my-account/view-order/' . $order_id;
   
   //if ( $order->status != 'failed' ) {
	if ( $order->status == 'completed' )
		{
		wp_safe_redirect( $url );
      exit;
		}
	else
		{

      $site_url = get_site_url();
      echo "<center><div style=\"background-color:rgba(255,255,255,0.8); padding: 12px; line-height: 160%; border: 2px dashed #cccccc; max-width: 600px; text-align: left;\">";
		echo "<center><strong>Pay now with Bitcoin</strong><a name=woobtc></a>\n" . $nl;
      echo "<img src=\"" . $site_url . "/wp-content/plugins/woocommerce-gateway-bitcoin/bitcoin.png\" style=\"width: 200px;\">" . $nl;
      echo "<div style=\"font-size: 18px; font-weight: bold; \">ORDER STATUS: " . $order->status . "</div>\n";
		echo "<div style=\"font-size: 18px; font-weight: normal; \">Once payment is completed below<br>you will be taken to your downloads</div></center>" . $nl;
		
      
      
      $folder = woobtc_get_files_folder();
   
      $do_checkout = true;
      $do_checkout_reason = "";
      
      if ($woobtc_dbg) { echo "files folder: " . $folder . $nl; }
      $woobtc_files_full_path = $folder;
      $freshpath = $folder . "/addresses_fresh.txt";
      
      if (file_exists($freshpath))
         {
         // all fine, crack on
         $fresh = file_get_contents($freshpath);
         if (trim($fresh) <> "")
            {
            // all good homie
            }
         else
            {
            // click clack empty chamber
            $do_checkout = false;
            $do_checkout_reason = "Empty fresh addresses file - admin needs to run setup/generate addresses";
            }
         }
      else
         {
         //slow your roll Cadalack cos I only got your back in your mind
         $do_checkout = false;
         $do_checkout_reason = "Missing fresh addresses file - admin needs to run setup/generate addresses";
         }
      
      if ($do_checkout)
         {
         if ($woobtc_dbg) { echo "<center>DO CHECKOUT</center>" . $nl; }
         }
      else
         {
         // DON'T DO CHECKOUT
         echo "<center>ERROR: " . $do_checkout_reason . "</center>" . $nl . $nl;;
         echo "</div></center>" . $nl;
         return;
         }
      
      
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
         echo "Price in BTC: " . $btc_symbol . number_format($btcprice, $roundbtc) . " (" . number_format(round($btcprice, $roundbtc) * 100000000) . " sats)</div>";
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
         
         //echo "substr: " . substr($btcaddress,0,5) . $nl;
         
         if ( $btcaddress <> "" && substr($btcaddress,0,5) <> "ERROR" )
            {
            if ($woobtc_dbg) { echo "<center>DEBUG: Found address in postmeta<br>" . $btcaddress . "</center>" . $nl; }
            
            $is_in_used = woobtc_is_in_used($btcaddress);
            if ($woobtc_dbg) { echo "Is_in_used: " . $is_in_used . $nl; }
            if ($is_in_used)
               {
               // do nothng, we all good;
               if ($woobtc_dbg) { echo "Exists in used list already, nothing to do" . $nl; }
               }
            else
               {
               $tmp = woobtc_mark_address_used($btcaddress, $order_id);
               if ($woobtc_dbg) { echo "Marked used: " . $tmp . $nl; }
               }
            //update_post_meta( $order_id, 'woobtc_address', "" );
            
            //echo $btcaddress . "</center>" . $nl;
            }
         else
            {
            if ($woobtc_dbg) { echo "<center>Fetching new address & storing to the order</center>" . $nl; }
            //$add = woobtc_get_fresh_address($order_id);
            $add = woobtc_get_fresh_address2($order_id, $api_preference);
            //echo "CHECK BALANCES OF NEW ADDRESS ARE 0 BEFORE SAVING TO ORDER" . $nl; << DONE IN UPDATED FRESH ADDRESS FUNCTION
            // echo "is_address fresh (balances/history): " . $is_fresh . $nl;         << SAME
            
            update_post_meta( $order_id, 'woobtc_address', $add );
            //update_post_meta( $order_id, 'woobtc_address', "" );
            $tmp = woobtc_mark_address_used($add, $order_id);
            
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
         
         echo "<center>BTC to send:<br><input type=text value=\"" . number_format($btcprice, $roundbtc) . "\" style=\"width: 300px; padding: 4px; font-size: large; text-align: center; \" onclick=\"this.setSelectionRange(0, 99999); document.execCommand('copy'); document.getElementById('woobtc_label_amount').innerHTML = 'Amount copied'; woobtc_clearfield('woobtc_label_amount'); \"  onmouseover=\"document.getElementById('woobtc_label_amount').innerHTML = 'Click to copy';\"  onmouseout=\"document.getElementById('woobtc_label_amount').innerHTML = '&nbsp;';\">" . $nl;
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
            echo "<input type=submit value=\"Click here once paid\" style=\"padding: 8px; background-color: #88cc88; colour: white;\">\n";
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
         
         echo "<div style=\"border: 1px dotted #cccccc; padding: 12px; font-size: 14px; line-height: 150%;\">";
         echo "Order id: " . $order_id . $nl;
         echo "Check the checksum is valid: ";
         
         $checksum_verify = woobtc_create_checksum($address, $amount);
         if ($checksum == $checksum_verify)
            { echo "Checksum verified OK" . $nl; }
         else
            { echo "Checksum verification FAILED" . $nl; }

         echo "Address: " . $address . $nl;
         echo "Amount: " . number_format($amount,$roundbtc) . $nl;
         
         $confs_req = 1;
         
         $order = wc_get_order( $order_id );
         //echo "<pre>" . print_r($order->total, true) . "</pre>" . $nl;
         
         $price = $order->total;
         echo "Order total: " . $fiat_symbol . $price . $nl;
         
         if ( $pricing_priority == "fiat" )
            {
            echo "in: Pricing priority fiat" . $nl;
            //echo "0-conf threshold: " . $conf_threshold_0 . $nl;
            if ((float)$price <= (float)$conf_threshold_0)
               { $confs_req = 0; }                
            }
         elseif ( $pricing_priority == "BTC" )
            {
            echo "in: Pricing priority: BTC" . $nl;
            //echo "0-conf threshold: " . $conf_threshold_0 . $nl;
            if ((float)$amount <= (float)$conf_threshold_0)
               { $confs_req = 0; }        
            }
         else
            {
            echo "This shouldn't happen" . $nl;
            }
         echo "Confirmations required: " . $confs_req . $nl;
         //echo "0 confirmations should normally process almost instantly, 1 confirmation could take 10-20 mins. 2 and above can take longer depending on how busy the Bitcoin network is" . $nl;
         //echo "<marquee id=waitingmessage ascrolldelay=500 scrollamount=3>0 confirmations should normally process almost instantly, 1 confirmation could take 10-20 mins. 2 and above can take longer depending on how busy the Bitcoin network is</marquee>";

         echo "Required amount: " . number_format($amount, $roundbtc) . $nl;
         //echo $nl;
         
         $getinfo_failed = false;
         echo "API-preference: " . $api_preference . $nl;
         $confirmed = "";
         $unconfirmed = "";
                  
         
         if ($api_preference == "Blockchain.info")
            {
            // BLOCKCHAIN.INFO STUFF
            if ($woobtc_dbg) { echo "Doing blockchain.info" . $nl; }
            if ($confs_req === 0)
               {
               $unconfirmed = woobtc_get_address_balance_bc($address, false);
               }
            else
               {
               $unconfirmed = woobtc_get_address_balance_bc($address, false);
               $confirmed = woobtc_get_address_balance_bc($address, true);
               }
            }
         else
            {
            // BLOCKSTREAM.INFO STUFF
            if ($woobtc_dbg) { echo "Doing blockstream.info" . $nl; }
            if ($confs_req === 0)
               {
               $unconfirmed = woobtc_get_address_balance_bs($address, false);
               echo "ZERO BRANCH" . $nl;
               }
            else
               {
               $unconfirmed = woobtc_get_address_balance_bs($address, false);
               $confirmed = woobtc_get_address_balance_bs($address, true);
               }
            }
            
         if ($woobtc_dbg) { echo "Unconfirmed balance: " . number_format($unconfirmed,8) . $nl; }
         if ($woobtc_dbg) { echo "Confirmed balance: " . number_format($confirmed,8) . $nl; }
         
         if ($unconfirmed == "")
               {
               echo "It's possible our server might have been rate-limited from the public API we use to check balances. Attempting to use the other API as a backup. This shouldn't really happen." . $nl;
               $getinfo_failed = true;
               }
         

// FAILOVER SECTION
         if ($unconfirmed == "" || $confirmed == "")
            {
            if ($api_preference == "Blockchain.info")
               {
               // BLOCKSTREAM.INFO STUFF
               if ($woobtc_dbg) { echo "Doing Blockstream.info as failover" . $nl; }
               if ($confs_req === 0)
                  {
                  $unconfirmed = woobtc_get_address_balance_bs($address, false);
                  }
               else
                  {
                  $confirmed = woobtc_get_address_balance_bs($address, true);
                  $unconfirmed = woobtc_get_address_balance_bs($address, false);
                  }
               }
            else
               {
               // BLOCKCHAIN.INFO STUFF
               if ($woobtc_dbg) { echo "Doing Blockchain.info as failover" . $nl; }
               if ($confs_req === 0)
                  {
                  $unconfirmed = woobtc_get_address_balance_bc($address, false);
                  }
               else
                  {
                  $confirmed = woobtc_get_address_balance_bc($address, true);
                  $unconfirmed = woobtc_get_address_balance_bc($address, false);
                  }
               }
            }
         
         //echo "Required confirmations: " . $confs_req . $nl;
         echo "Unconfirmed balance: " . number_format($unconfirmed,8) . $nl; 
         echo "Confirmed balance: " . number_format($confirmed,8) . $nl; 
         //echo $nl;
         
         
         
         if ($getinfo_failed)
            { echo "<strong>LOOKS LIKE WE MIGHT HAVE BEEN RATE LIMITED ON YOUR CHOICE OF API (" . $api_preference . "), USE THE OTHER ONE" . $nl; }

               

        
         $paid = false;
         if ($woobtc_dbg) { echo "confs req: " . $confs_req . $nl; }
         
         if ($confs_req === 0)
            {
            echo "0-conf branch" . $nl;
            if ( (float)$unconfirmed >= (float)$amount )
               {
               $paid = true;
               }
            }
         
         if ($confs_req == 1)
            {
            echo "1+conf branch" . $nl;
            if ( (float)$confirmed >= (float)$amount )
               { $paid = true; }
            }
           
         //echo "PAID: " . $paid . $nl;
         
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
            echo "Reloading the page and taking you to your order... :)  [ TEMPORARILY DISABLED ]";
            echo "<script language=javascript>setTimeout('location.href=\"" . $url . "\"',1000);</script>";
            echo $nl . $nl . "<center><a href=\"" . $url . "\">Go there manually</a></center>" . $nl;
            echo "</div>";
            }
         else 
            {
            echo "This page will auto-refresh every 2 mins and should make a noise when the payment is received." . $nl;
            //echo "<script language=javascript>setTimeout('location.reload()', 30000);</script>";
            echo "</div>";
            echo $nl;
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $current_url = str_replace("&refresh=", "", $current_url);
            //echo "current url: " . $current_url . $nl;

            echo "<center><form name=woobtc_paid action=\"" . $current_url . "#woobtc\" method=post>\n";
            echo "<input type=hidden name=amount value=\"" . $amount  . "\">\n";
            echo "<input type=hidden name=address value=\"" . $address . "\">\n";
            echo "<input type=hidden name=checksum value=\"" . $checksum . "\">\n";
            echo "<input type=button value=\"Back to payment details\" onclick=\"location.href='" . $current_url . "';\" style=\"padding: 8px;\" title=\"Dont worry, you won't break anything by going bacl. The payment address wont change and your existing payment wont get lost by going back. The payment address is now linked to this order number and each order gets issues its own address\">\n";
            echo "<input type=submit value=\"Click to refresh\" style=\"padding: 8px; background-color: #88cc88; colour: white;\">\n";
            echo "</form></center>\n";
            echo "<script language=javascript>setTimeout('document.forms.woobtc_paid.submit()',120000)</script>";
            echo $nl;            
            }
         echo "</div></center>";
         echo $nl;
         }
      }
	}


