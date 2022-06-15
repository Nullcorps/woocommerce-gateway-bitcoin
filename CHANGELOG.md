# Changelog

### 1.3.0

* UI: add xpub and derivation path to admin order metabox
* Don't continue checking for address transactions after order is marked paid
* More logging of update order background job
* Refactor: move admin order metabox to template
* Fix: add btc_total to formatted order details
* Fix: don't add empty notes to orders when there are not transaction updates

### 1.2.0

* Store wallets & addresses as custom post type
* Display payments details in admin order UI metabox
* Increase debug logging around scheduling checks for payments
* Check for payments using Sochain API 
* Separate data and formatting function
* Refactor fetch address data (update address) logic
* Add CLI commands

### 1.1.1

* Fix: enqueue dashicons on Thank You page
* Fix: link to logs page was not visible on settings page

And improved testing, linting and code quality.

### 1.1.0

* Add: Additional logging and order notes. i.e. links to the address and transactions on each order.
* Fix: Email function was being invoked with an old order object, now refreshes so address metadata will be available.

### 1.0.1

Fixes some teething problems:

* Misspelled const was causing the Settings link on plugins.php not to appear (and maybe deeper problems).
* Address generation now starts when the xpub is saved/changed on the settings screen (bg task via Action Scheduler, so maybe not immediately)
* Gateway will not appear at checkout if there is no destination payment address generated and ready to go
* Exception handling when printing instructions on Thank You page and Emails – now catches the exception (no address) and prints nothing, logs the error
* JS and CSS are now only enqueued on pages whose order used a Bitcoin gateway (previously they enqueued on all Thank You and View Order pages)

### 1.0.0 2022-03-30

* This is reasonably well unit-tested and somewhat tested locally, but has not been run in production yet.