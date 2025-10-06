/**
 * External dependencies
 */
import { Page, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { testConfig } from '../config/test-config';

import { fillBilling, selectPaymentGateway } from './checkout';
import { logout } from './login';

async function selectBitcoinPaymentMethod( page: Page ) {
	await selectPaymentGateway( page, 'bitcoin_gateway' );

	// Verify Bitcoin payment method description appears
	await expect(
		page.getByText( 'Pay quickly and easily with Bitcoin' )
	).toBeVisible();
}

export async function placeBitcoinOrder( page: Page ): Promise< number > {
	await logout( page );

	// Go to shop
	await page.goto( '/shop/' );

	// Add simple product to cart
	await page.click( `text="${ testConfig.products.simple.name }"` );
	await page.click( '.single_add_to_cart_button' );

	// Go to checkout
	await page.goto( '/checkout/' );

	// Fill billing details
	await fillBilling( page );

	await selectBitcoinPaymentMethod( page );

	// Place order
	// await page.click(''Place Order');
	await page.getByText( 'Place Order' ).click();
	// await page.locator('.wc-block-components-checkout-place-order-button').isEnabled();
	// await page.click('.wc-block-components-checkout-place-order-button');

	// Wait for order received page
	await page.waitForSelector( 'text=Order received' );

	// Extract order ID from URL
	const url = page.url();
	const orderIdMatch = url.match( /order-received\/(\d+)\// )!;
	return parseInt( orderIdMatch[ 1 ] );
}
