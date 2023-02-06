import { expect } from '@jest/globals';

const {
	shopper,
	uiUnblocked,
	merchant,
	createSimpleProduct,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const configureBitcoinXpub = require( './configure-bitcoin-xpub.before.js' );

const baseUrl = config.get( 'url' );

describe( 'Place orders on block checkout', () => {
	// Configure the gateway.
	beforeAll( async () => {
		await merchant.login();
		await configureBitcoinXpub();
		await createSimpleProduct();
	} );

	it( 'can see Bitcoin payment option on block checkout', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( simpleProductName );

		await page.goto( baseUrl + 'blocks-checkout/', {
			waitUntil: 'networkidle0',
		} );

		await expect( page ).toClick(
			'.wc-block-components-payment-method-label',
			{
				text: 'Bitcoin',
			}
		);
	} );

	// Happy path.
	it( 'should successfully place order and show payment details', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( simpleProductName );

		await page.goto( baseUrl + 'blocks-checkout/', {
			waitUntil: 'networkidle0',
		} );

		// TODO: use `await shopper.block.fillBillingDetails( BILLING_DETAILS )`.

		const customerBillingDetails = config.get(
			'addresses.customer.billing'
		);

		await expect( page ).toFill( '#email', customerBillingDetails.email );

		await expect( page ).toFill(
			'#billing-first_name',
			customerBillingDetails.firstname
		);
		await expect( page ).toFill(
			'#billing-last_name',
			customerBillingDetails.lastname
		);
		await expect( page ).toFill(
			'#billing-country',
			customerBillingDetails.country
		);
		await expect( page ).toFill(
			'#billing-address_1',
			customerBillingDetails.addressfirstline
		);
		await expect( page ).toFill(
			'#billing-address_2',
			customerBillingDetails.addresssecondline
		);
		await expect( page ).toFill(
			'#billing-city',
			customerBillingDetails.city
		);
		await expect( page ).toFill(
			'#billing-state',
			customerBillingDetails.state
		);
		await expect( page ).toFill(
			'#billing-postcode',
			customerBillingDetails.postcode
		);

		await uiUnblocked();

		await expect( page ).toClick(
			'.wc-block-components-payment-method-label',
			{
				text: 'Bitcoin',
			}
		);
		await uiUnblocked();

		await expect( page ).toMatchElement(
			'.wp-block-woocommerce-checkout-payment-block',
			{
				text: 'Pay quickly and easily with Bitcoin',
			}
		);

		// TODO: use `await shopper.block.placeOrder()`.

		// Wait for payment methods to be shown, otherwise we get flakey tests
		await page.waitForSelector(
			'.wc-block-components-payment-method-label'
		);
		// Wait for place order button to be clickable, otherwise we get flakey tests
		await page.waitForSelector(
			'.wc-block-components-checkout-place-order-button:not([disabled])'
		);
		await Promise.all( [
			page.click( '.wc-block-components-checkout-place-order-button' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );

		await expect( page ).toMatch( 'Order received' );
		await expect( page ).toMatch( 'Exchange rate at time of order' );
	} );
} );
