const {
    shopper,
    uiUnblocked, merchant, setCheckbox, settingsPageSaveChanges, verifyCheckboxIsSet, clearAndFillInput
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const enableChequePaymentsEnableBitcoinAndConfigureXpub = require( './configure-gateway.before.js' );


describe('Place orders', () => {


    // Happy path.
    it('should successfully place order and show payment details', async () => {

        await merchant.login();

        // Enable cheque payment method.
        await merchant.openSettings( 'checkout', 'cheque' );
        await setCheckbox( '#woocommerce_cheque_enabled' );
        await settingsPageSaveChanges();

        // Verify that settings have been saved
        await verifyCheckboxIsSet( '#woocommerce_cheque_enabled' );

        await merchant.openSettings( 'checkout', 'bitcoin_gateway' );
        // TODO: read from env.secret.
        // This is the empty "wp_plugin_wallet" wallet.
        var xpub = 'zpub6n37hVDJHFyDG1hBERbMBVjEd6ws6zVhg9bMs5STo21i9DgDE9Z9KTedtGxikpbkaucTzpj79n6Xg8Zwb9kY8bd9GyPh9WVRkM55uK7w97K';
        await clearAndFillInput( '#woocommerce_bitcoin_gateway_xpub', xpub )

        await settingsPageSaveChanges();

        await merchant.logout();

        await shopper.goToShop();

        // console.log( 'simpleProductName ' + simpleProductName );

        // TODO: Just find the first product on the page and add that.
        await shopper.addToCartFromShopPage(simpleProductName);

        // console.log( 'go to checkout' );

        await shopper.goToCheckout();

        await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

        await uiUnblocked();

        await expect(page).toClick('.wc_payment_method label', {
            text: 'Bitcoin'
        });
        await uiUnblocked();

        await expect(page).toMatchElement('.payment_method_bitcoin_gateway', {text: 'Pay quickly and easily with Bitcoin'});

        await shopper.placeOrder();

        await expect(page).toMatch('Exchange rate at time of order');

    });


});
