# woocommerce-gateway-bitcoin
Self-custody Bitcoin checkout for woocommerce. No middle man, privacy oriented, minimal maintenance, simple.

Get paid directly into your self-custody wallet without any middleman or any KYC'd APIs.
No signups, no Terms of Service, nobody taking a cut. Make a sale on your site and it drops straight into 
your Electrum (or whatever) wallet. Payments are between you and the Bitcoin network (well and possibly
the public API providers somewhat but you have a choice which to use).

NO FULL BITCOIN NODE REQUIRED \o/

THIS IS VERY MUCH WIP - MAKE OF IT WHAT YOU WILL. IF YOU MANAGE TO BREAK IT SOMEHOW PLEASE DO LET ME KNOW OR BETTER STILL FIX IT AND THEN LET ME KNOW :)

- Built with using Electrum in mind. Love it or hate it it's the on-ramp for many people. Should work with other wallets which also use the m/0/1 derivation path (most of them?), though not currently tested as such

- This is not trying to be fancy and compete with BTCpayserver. BTCpayserver is an awesome product but the technical
threshold is still a bit high for some people if you want to run your own, or you need to use a 3rd party service like BTCPayJungle. If you're wanting to do Lightning payments then just go and figure out btcpayserver. But if you just want nobody else taking a slice and don't have your own node then this might be an easy-to-set up and lightweight option. No "accounts" needed with anyone. Just you and your self-custody wallet.

- This plugin uses public APIs which require no KYC, and self-custody wallets (e.g. Electrum). You just need your local wallet and your xpub
and you can start taking payments which land right in your electrum wallet. Boosh.

- Basic principle is that you drop your xpub (master public key, you can find this in electrum/wallet/information, starts
"xpubabunchanumbers") into the plugin settings, from that we use the bitwasp library to *locally* (on the server) derive Bticoin addresses
using m/0/n derivation path (like electrum) which means the addresses generated will line up 100% with the addresses
which show up in your electrum wallet, BUT unlike the nomiddleman plugin (which was the only other/closest plugin like this I could find), you don't have to "load" addresses in manually one by one, which is a pain in the short term and only gets more annoying with time, especially in a busy shop. So this plugin refills addrsses semi-automatically..just has a "check addresses" shortcode [woobtc_addresses] which you drop into a page. You hit that page periodically and it scans all the addresses and tops up your address list. 

- Each address only gets issued once, and once issued is "tied" to the woocommerce order via postmeta, so that the two are linked

- Addresses are pre-generated in batches in the background  (either manually or via a cron/wget) so there's a list of 50 (or whatever)
sat there in a textfile ready to use, so we don't have to do any heavy maths on the fly, just pop the next address off the "address stack".
Once issued, that address is removed from the "fresh addresses" stack and pushed onto the "used addresses" stack, along with the
corresponding woocommerce order number. That way hopefully even if somehow you lose the postmeta info with the address for the order, you would still have the address use log for reconciling addresses with orders.

- Currently this plugin uses blockchain.info's public api to check address balances but will soon also be compatible with blockstream.info's public api which I believe can also be accessed over tor. User will be able to set a preference for which to use, but the other will be available as a failover in case the site gets rate-limited by either API.


-----------------------------

I'm not a php ninja, I'm just persistent af and am copy & pasting and trial & error-ing my way thru this as I learn the maths/theory behind it, so before you judge my spaghetti code too harshly, please ask yourself with all the amazing coders out there, why it's fallen to little old me to actually write this. And if you can do better, join in ;)

I hope I got the licensing bit right, I have no idea really. It should be free and open source. That's the idea anyway.

Want it to work better in a particular way? congrats you just joined the team. Till then, IIWII :D


NullCorps




Installation instructions:
------------------------------------------------
either:

- ssh into your server
- cd to your wordpress plugins folder ( /wp-content/plugins/ )
- git clone https://github.com/Nullcorps/woocommerce-gateway-bitcoin.git

or:

- download the plugin from the link at the top, unzip it and FTP/SFTP it into your plugins folder ( /wp-content/plugins/ ) 

then:
- log into your wordpress and enable the plugin
- in the admin dashboard under woocommerce/settings/payments you should now see the payment option listed
- copy and paste in the xpub from your wallet. In electrum it's under menu:wallet/infromation. It should start with xpub and then a bunch of numbers 
- save the settings. You may also want to set the 0-conf limit to 0 if you don't want to allow zer-confirmation transactions (probably a good idea)
- then make a new page, add a "Custom HTML" block, and paste in [woobtc_addresses]. Give it a nice easy permalink like /addresses
   - IF SAVING THE PAGE FAILS, THE CHANCES ARE YOU'RE MISSING ONE OF THE REQUIRED PHP MODULES
- once saved, visit the page and it should splurge a bunch of data. Basically it's calculating the next 50 or so addresses so that they're on hand, since the maths to calculate them on the fly is pretty heavy and would slow the page down. This way it has a list of addresses to hand which is much faster.
- reload that page 2/3 times till it's showing a full stack of (e.g. 50) addresses in the last box
- that's it

You should now be able to add an item to your cart, head to the checkout and with a bit of luck you'll see the bitcoin payment option. If you proceed with that it should then show you an address, QR-code etc which is now tied to this order and will not be reused. It's ok though, you can generate as many addresses as you like, all you need to do is re-visit that addresses page periodically to top up your stash of addresses. On a super busy site you might want to adjust the settings to pre-generate a larger number of addresses e.g. 200.

Since the /addresses page exposes all your addresses, you may wish to limit access to that page using something like my guestshortcode plugin, in which case your custom HTML block would look a bit like this:

[admin][woobtc_addresses][/admin]
[guest] Sorry, access denied [/guest]

That would mean you as admin can see the addresses apge and generate/refresh them, but nobody else can.

Alternatively you could wrap the [woobtc_addresses] in some php and have it pass in a password via a url some sort of scheduled thing like cron hit that page to keep your addresses topped up automatically. So then you'd set your cron to hit /addresses?p=somelongpassword2340903852924 and only show the [woobtc_addresses] shortcode if said password is present. 

PLEASE NOTE: segwit wallets doesn't seem to be supported by bitwasp, there's nothing i can really do about that currently.




Installation requirements:
------------------------------------------------

This plugin requires the following php modules to work. Please note Mcrypt is no longer included as part of the standard php modules so needs a little extra work to install, I've included a link to a set of instructions which worked. Replace "7.4" with whatever version of php you're using. I've only tested up to 7.4 currently:

- bcmath   :     sudo apt install php7.4-bcmath
- gmp      :     sudo apt install php7.4-gmp
- mcrypt   :     https://computingforgeeks.com/install-php-mcrypt-extension-on-ubuntu/




Stuff to do:
------------------------------------------------


MOSTLY DONE? - make it also work with blockstream.info's api, allow user to set preference but keep the other as a failover in case of rate limiting
   - does the failover work? 
 
IN PROG - add a settings field to allow css hacks? or could this be done at the theme customer level. The css id's should be unique so why not?
    - well..whilst not a settings field, been adding ids to buttons etc which then mean you can do "additional css" bits in your theme options.

IN PROG - idk, tidy it up a bit, remove any inline css

IN PROG (needs automating now) - auto refill addresses when running low, something like on payme page, self maintaining.
- this just needs a shortcode or something with a silent version of the [woobtc_addresses] function which gets hit by a cron+wget or a web cron like montastic.com periodically to refill the addresses. Done manually atm when you go to the "check/refill addresses" page. (btw if you don't have one of those pages you need one - just add a blank page ad /addresses and put the [woobtc_addresses] shortcode in it. You'll need to run that page before it'll work)

DITC - just before fresh address is linked to order in the postmeta, check past/present balances are still 0

- install/setup procedure?
  - install plugin
  - enable in woocommerce/settings/payments
  - go into the settings page for btc payments
  - get your xpub from electrum under wallet/information, it starts with xpubandthenalotofnumbers. Other wallet users google "how do i find my xpub [walletname]"
  - paste that in, fill in the settings, should be self explanatory. Main one is re 0-conf transactions. Set to 0 to require confirmations for all transactions or set a price up to which you will accept 0-conf transactions (not really recommended since wiht the current high fees someone could send a tx with 1sat/byte fees and there's a good chance it would get bounced back to them, so waiting for a confirmation is recommended, but the option is there).
  - make a page at /addresses add the shortcode [woobtc_addresses]
  - visit that page, it should do a bunch of maths
  - refresh that page a few times, it should have a stack of 50 (or however many you set) addresses liksted and have finished scanning/updating and removing used ones
  - that should be about it really?

- fee checking thing. So given the current fee situation and the threat of lowballing the fee and bouncing a 0-conf tx, perhaps enable 0-conf transactions up to the value specified as long as the fee is above e.g. a fixed value like 15s/b (low tech but easier to implement) or perhaps from some sort of fee estimating thing but it needs to be public api. check blockstream/blockchain.info. That way if the client cheaps out on the fee they have to wait for a confirmation, but honest users who just want it NOW get processed more quickly.

- Automate the address refill process like on btcpayme
   - add a "non-verbose" option to the [woobtc_addresses] thing which can be called from montastic or cron+wget

- add mbtc as well as sats etc for ppl on default electrum settings (came up in testing)

- figure out wtf's needed to get this into the WP repo once it's at that point

DONE - added "percentage discount for BTC payment" option (2021-11-02)
DONE - integrate in SS (test)
DONE - integrate in FDV
DONE - integrate in MSL
DONE - integrate in TG
DONE - auto prune addresses which may have been used in the mean time (e.g. multiple instances?)
NOPE - maybe allow user-definable derivation paths? Do other wallets use something other than m/0/1? << HA! How about no(t right now anyway)
NOPE - give the option of QR from google images or local libary depending on privacy preference << meh, why?





Considerations:


- depending on how busy your shop is, and the ratio of people who enter the checkout process vs those who complete the checkout process, there
will be quite a few addresses which remain unused. So say 1/10 people convert, you'll end up with 9 unusued addresses vs 1 used, which means 
electrum might start to "fall behind" with how far ahead it's looking for payments which would then mean you could be receiving payments but 
Electrum isn't checking the addresses that far ahead so you think you've not been paid, when actually you have. The payments are there but 
Electrum's just not checking that far ahead on the addresses list.

In this case you can set the lookahead value (gap limit?) for electrum from the electrum console (tab) by typing the following and your payments
will magically appear:

wallet.change_gap_limit(200)
wallet.synchronize()

see: https://bitcoin.stackexchange.com/questions/63641/how-to-list-all-the-hd-address-in-electrum


  


Big thank you to @orionwl for talking things through along the way and paitently explaining the maths side of it over and over till I get it :)


This uses the Bitwasp library for all the maths heavy lifting, address generating etc. https://github.com/Bit-Wasp/bitcoin-php



