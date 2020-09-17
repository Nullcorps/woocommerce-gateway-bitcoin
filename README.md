# woocommerce-gateway-bitcoin
Self-custody Bitcoin checkout for woocommerce. No middle man, privacy oriented, minimal maintenance, simple.

Get paid directly into your self-custody wallet without any middleman or any KYC'd APIs.
No signups, no Terms of Service, nobody taking a cut. Make a sale on your site and it drops straight into 
your Electrum (or whatever) wallet. Payments are between you and the Bitcoin network (well and possibly
the public API providers somewhat).

THIS IS VERY MUCH WIP - NOT PRODUCTION READY YET

NO FULL BITCOIN NODE REQUIRED \o/


- Built with using Electrum in mind. Love it or hate it it's the on-ramp for many people. Should work with other wallets, though
not currently tested

- This is not trying to be fancy and compete with BTCpayserver. BTCpayserver is an awesome product but the technical
threshold is still a bit high for some people.

- This plugin uses public APIs which require NO KYC and self-custody wallets. You just need your local wallet and your xpub
and you can start taking payments.

- Basic principle is that you drop your xpub (master public key, you can find this in electrum/wallet/information, starts
"xpubabunchanumbers") into the plugin settings, from that we use the bitwasp library to *locally* derive Bticoin addresses
using m/0/n derivation path (like electrum) which means the addresses generated will line up 100% with the addresses
which show up in your electrum wallet, BUT unlike the nomiddleman plugin, you don't have to "load" addresses in manually which
minimises ongoing maintenance, whilst allowing us to only issue each address once (to one order), for maximum privacy.

- Each address only gets issued once, and once issued is "tied" to the woocommerce order via postmeta, so that the two are linked

- Addresses are pre-generated in batches in the background  (either manually or via a cron/wget) so there's a list of 50 (or whatever)
sat there in a textfile ready to use, so we don't have to do any heavy maths on the fly, just pop the next address off the "address stack".
Once issued, that address is removed from the "fresh addresses" stack and pushed onto the "used addresses" stack, along with the
corresponding woocommerce order number. That way hopefully even if one method fails you have two sets of options for reconciling addresses
with orders.

- Currently this plugin uses blockchain.info's public api to check address balances but will soon also be compatible with blockstream.info's public api which I believe can also be accessed over tor. User will be able to set a preference for which to use, but the other will be available as a failover in case the site gets rate-limited by either API.


-----------------------------

I'm not a php ninja, I'm just persistent af and am copy & pasting and trial & error-ing my way thru this as I learn the maths/theory behind it, so before you judge my spaghetti code too harshly, please ask yourself with all the amazing coders out there, why it's fallen to little old me to actually write this. And if you can do better, join in ;)

I hope I got the licensing bit right, I have no idea really. It should be free and open source. That's the idea anyway.

Want it to work better in a particular way? congrats you just joined the team. Till then, IIWII :D


NullCorps






Stuff to do:

- add mbtc as well as sats etc for ppl on default electrum settings (came up in testing)

- add a settings field to allow css hacks? or could this be done at the theme customer level. The css id's should be unique so why not?

- Automate the address refill process like on btcpayme

IN PROG - integrate in TG

IN PROG - make it also work with blockstream.info's api, allow user to set preference but keep the other as a failover in case of rate limiting

IN PROG (needs automating now) - auto refill addresses when running low, something like on payme page, self maintaining.

DITC - just before fresh address is linked to order in the postmeta, check past/present balances are still 0

- idk, tidy it up a bit or something, remove any inline css

- figure out wtf's needed to get this into the WP repo once it's at that point

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
