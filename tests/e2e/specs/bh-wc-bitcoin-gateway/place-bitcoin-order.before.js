const {
    shopper,
    uiUnblocked, createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const placeBitcoinOrderBefore = async(dispatch ) => {

        await shopper.goToShop();

        await shopper.addToCartFromShopPage(simpleProductName);

        await shopper.goToCheckout();

        await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

        await uiUnblocked();

        await expect(page).toClick('.wc_payment_method label', {
            text: 'Bitcoin'
        });
        await uiUnblocked();

        await expect(page).toMatchElement('.payment_method_bitcoin_gateway', {text: 'Pay quickly and easily with Bitcoin'});

        await shopper.placeOrder();
}

module.exports = placeBitcoinOrderBefore;