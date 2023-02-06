const { merchant, createSimpleProduct } = require( '@woocommerce/e2e-utils' );

const configureBitcoinXpub = require( './configure-bitcoin-xpub.before.js' );
const placeBitcoinOrderBefore = require( './place-bitcoin-order.before.js' );

describe( 'Place orders', () => {
	// Configure the gateway.
	beforeAll( async () => {
		await merchant.login();
		await configureBitcoinXpub();
		await createSimpleProduct();
		await placeBitcoinOrderBefore();
	} );

	// Happy path.
	it( 'should successfully place order and show payment details', async () => {
		await expect( page ).toMatch( 'Exchange rate at time of order' );
	} );
} );
