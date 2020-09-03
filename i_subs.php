<?php








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
      if ($order_id <> "")
         {
         file_put_contents($usedpath, $nextadd . "-WOO_ORDER: " . $order_id . "\n", FILE_APPEND | LOCK_EX);
         }
      else
         {
         file_put_contents($usedpath, $nextadd . "\n", FILE_APPEND | LOCK_EX);
         }
      
      return $nextadd;
      }
   else
      {
      return "ERROR: missing addresses file";
      }
   return $out;
   }







function woobtc_get_fresh_address2($order_id, $api_preference)
   {
   global $nl;
   global $woobtc_dbg;
   //$woobtc_dbg = true;
   $folder = woobtc_get_files_folder();
   echo "IN WOOBTC FRESH ADDRESS 2" . $nl;
   
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
      $cnt = 0;
      
      
      do {
         $addused = "";
         $nextadd = $aradds[$cnt];
         if ($woobtc_dbg) { echo "Next address2: " . $nextadd . $nl; }
         
         if ($nextadd <> "")
            {
            $isfresh = "";
            
            
            $tmpused = file_get_contents($usedpath);
            $artmpused = explode("\n",$tmpused);
            
   
            //if ( in_array($nextadd, $artmpused) )
            if ( woobtc_is_in_used_arr($nextadd, $artmpused) )
               {
               if ($woobtc_dbg) { echo "It's in the used list - remove this one" . $nl; }
               $addused = "USED";
               }
            else
               {
               if ($woobtc_dbg) { echo "Looks like it's not been used by us" . $nl; }
               if ($woobtc_dbg) { echo "check address history.." . $nl; }
               if ($api_preference == "blockstream.info")
                  {
                  if ($woobtc_dbg) { echo "Doing Blockstream.info - NOT IMPLEMENTED YET - USING BLOCKCHAIN.INFO" . $nl; }
                  $tmp = woobtc_is_address_fresh_bc($nextadd, false);
                  if ($woobtc_dbg) { echo "tmp: " . $tmp . $nl; }
                  $addused = $tmp;
                  }
               else
                  {
                  if ($woobtc_dbg) { echo "Doing Blockchain.info" . $nl; }
                  $tmp = woobtc_is_address_fresh_bc($nextadd, false);
                  if ($woobtc_dbg) { echo "tmp: " . $tmp . $nl; }
                  $addused = $tmp;
                  }
               }
            
            if ($addused == "USED")
               {
               if ($woobtc_dbg) { echo "WRITE TO USED STACK" . $nl; }
               if ($woobtc_dbg) { echo "Add the current address to the used addresses list" . $nl; }
               //file_put_contents($usedpath, $nextadd . "\n", FILE_APPEND | LOCK_EX);
               //file_put_contents($usedpath, $nextadd . "-WOO_ORDER: " . $order_id . "\n", FILE_APPEND | LOCK_EX);
               woobtc_mark_address_used($nextadd, $order_id);
               
         
               $addsleft = str_replace($nextadd . "\n","", $adds);
               //echo "Addresses left: " . $nl . $addsleft . $nl;
               if ($woobtc_dbg) { echo "Writing remaining fresh addresses back to file" . $nl; }
               file_put_contents($addpath, $addsleft);
               
               }
                        }
         else
            {
            if ($woobtc_dbg) {  echo "Blank address, ignoring" . $nl; }
            }
         if ($woobtc_dbg) { echo $nl; }
         $cnt++;

         } while ($addused <> "FRESH" && $cnt < 50);
      
      if ($addused == "FRESH")
         {
         if ($woobtc_dbg) { echo "Next fresh address: " . $nextadd . $nl; }
         return $nextadd;
         }
      if ($woobtc_dbg) { echo "done" . $nl; }
      
      return "SHOW ME WHAT YOU GOT - NO NEW ADDRESSES AVAILABLE"; // This shouldn't happen      
      }
   else
      {
      return "ERROR: missing addresses file";
      }
   return;
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









function woobtc_is_in_used($add)
   {
   global $nl;
   $found = "";
   $folder = woobtc_get_files_folder();
   $usedpath = $folder . "/addresses_used.txt";
   $used = file_get_contents($usedpath);
   $arused = explode("\n", $used);
   //echo "<b>" . $used . "</b>" . $nl;
   $tmp = woobtc_is_in_used_arr($add, $arused);
   return $tmp;

   }







function woobtc_is_in_used_arr($nextadd, $artmpused)
   {
   global $nl;
   //global $woobtc_dbg;
   $woobtc_dbg = false;
   
   $found = "";
   //echo "Nextadd: " . $nextadd . $nl;
   //echo print_r($artmpused, true) . $nl;
   
   if ($woobtc_dbg) { echo "is_array: " . is_array($artmpused) . $nl; }
   foreach ($artmpused as $tmp)
      {
      if ($woobtc_dbg) {  echo "comparing address: " . $nextadd . " position: " . strpos($tmp, $nextadd) . $nl; }
      if (strpos($tmp, $nextadd) === 0)
         {
         if ($woobtc_dbg) { echo "this one" . $nl; }
         $found = true;
         return true;
         }
      }
   }





function woobtc_mark_address_used($btcaddress, $order_id)
   {
   global $nl;
   global $woobtc_dbg;
   $woobtc_dbg = true;
   if ($btcaddress)
      {}
   else
      {
      return "ERROR: Missing address";
      }
   $folder = woobtc_get_files_folder();
   if ($woobtc_dbg) { echo "IN WOOBTC MARK ADDRESS USED" . $nl; }
   
   //$addpath = $folder . "/addresses_fresh.txt";
   $usedpath = $folder . "/addresses_used.txt";
   if ($woobtc_dbg) { echo "Files path for addresses: " . $folder . $nl; }
   if ($order_id <> "")
      {
      file_put_contents($usedpath, $btcaddress . "-WOO_ORDER: " . $order_id . "\n", FILE_APPEND | LOCK_EX);
      }
   else
      {
      file_put_contents($usedpath, $btcaddress . "\n", FILE_APPEND | LOCK_EX);
      }
   
   $freshpath = $folder . "/addresses_fresh.txt";
   $fresh = "";
   if ( file_exists($freshpath) )
      { $fresh = file_get_contents($freshpath); }
   
   $s = strpos($fresh, $btcaddress);
   echo "DOES ADDRESS STILL EXIST IN FRESH LIST?: " . $s .  $nl;
   if (!$s)
      {
      echo "YES IT DOES - FIXING!" . $nl;
      $fresh_updated = str_replace($btcaddress . "\n", "", $fresh);
      $fresh_updated = str_replace($btcaddress, "", $fresh);
      $fresh_updated = str_replace("\n\n", "\n", $fresh_updated);
      //echo "Old text:<br><pre>" . $fresh . "</pre><br>" . $nl;
      //echo "New text:<br><pre>" . $fresh_updated . "</pre><br>" . $nl;
      file_put_contents($freshpath, trim($fresh_updated) ); 
      }
   
   
   
   //$arfresh = explode("\n",$fresh);
   //$fresh_new = [];
   //
   //foreach ($arfresh as $tmp)
   //   {
   //   if (trim($tmp) <> "")
   //      {
   //      if ($btcaddress <> $tmp)
   //         {
   //         $fresh_new .= $tmp . "\n";
   //         }
   //      }
   //   }
   //
   
   //$tmp = str_replace($btcaddress . "\n", "", $fresh);
   
   //file_put_contents($freshpath, $fresh_new, LOCK_EX); 
   
   return "OK";
   }





function woobtc_is_address_fresh_bc($address)
   { 
   global $nl;
   global $woobtc_dbg;
   $totalbalance_url_uc = "https://blockchain.info/q/getreceivedbyaddress/" . $address . "?confirmations=1";
   $totalbalance_url_cf = "https://blockchain.info/q/getreceivedbyaddress/" . $address . "?confirmations=0";
      

   if ($woobtc_dbg) { echo "Total balance url (uc): " . $totalbalance_url_uc . $nl; }
   if ($woobtc_dbg) { echo "Total balance url (cf): " . $totalbalance_url_cf . $nl; }
   
   $totalbalance_uc = file_get_contents($totalbalance_url_uc);
   $totalbalance_cf = file_get_contents($totalbalance_url_cf);
   
   if ($woobtc_dbg) { echo "totalbalance_uc: " . $totalbalance_uc . $nl; }
   if ($woobtc_dbg) { echo "totalbalance_cf: " . $totalbalance_cf . $nl; }
   
   $tmp = $totalbalance_uc + $totalbalance_cf;
   if ($tmp)
      {
      return "USED";
      }
   else
      {
      return "FRESH";
      }
   }

   
   
   

function woobtc_is_address_fresh_bs($address)
   {
   
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











