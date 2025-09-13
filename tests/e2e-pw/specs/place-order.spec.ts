/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { useShortcodeCheckout } from '../helpers/checkout';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { logout } from '../helpers/login';
import { placeBitcoinOrder } from '../helpers/place-bitcoin-order';
import { switchToShortcodeTheme, verifyTheme } from '../helpers/theme-switcher';

test.describe( 'Place orders (Shortcode Checkout)', () => {
	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await configureBitcoinXpub( page );
		await createSimpleProduct( page );

		await useShortcodeCheckout();

		// Switch to Twenty Twelve theme for shortcode checkout testing
		await switchToShortcodeTheme();
		await verifyTheme( 'shortcode' );

		await page.close();
	} );

	test( 'should successfully place order using shortcode checkout and show payment details', async ( {
		page,
	} ) => {
		// Verify we're using the correct theme for shortcode checkout
		await switchToShortcodeTheme();

		// Checkout appears different when logged in/out due to saved address
		await logout( page );

		// Place order using shortcode checkout
		await placeBitcoinOrder( page );

		// Verify payment details are shown
		await expect(
			page.locator( 'text=Exchange rate at time of order' )
		).toBeVisible();
	} );
} );
