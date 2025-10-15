/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	switchToShortcodeTheme,
	verifyTheme,
} from '../helpers/rest/theme-switcher';
import { useShortcodeCheckout } from '../helpers/ui/checkout';
import { configureBitcoinXpub } from '../helpers/ui/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/ui/create-simple-product';
import { logout } from '../helpers/ui/login';
import { placeBitcoinOrder } from '../helpers/ui/place-bitcoin-order';

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
