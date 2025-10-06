/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	switchToShortcodeTheme,
	switchToBlocksTheme,
	getCurrentTheme,
	verifyTheme,
} from '../../helpers/rest/theme-switcher';

test.describe( 'Theme Switching for Checkout Types', () => {
	test( 'should switch to Twenty Twelve for shortcode checkout', async ( {
		page,
	} ) => {
		await switchToShortcodeTheme();

		const currentTheme = await getCurrentTheme();
		expect( currentTheme.slug ).toBe( 'twentytwelve' );

		// Verify theme validation works
		await verifyTheme( 'shortcode' );
	} );

	test( 'should switch to Twenty Twenty-Five for blocks checkout', async ( {
		page,
	} ) => {
		await switchToBlocksTheme();

		const currentTheme = await getCurrentTheme();
		expect( currentTheme.slug ).toBe( 'twentytwentyfive' );

		// Verify theme validation works
		await verifyTheme( 'blocks' );
	} );

	test( 'should be able to switch between themes multiple times', async ( {
		page,
	} ) => {
		// Switch to shortcode theme
		await switchToShortcodeTheme();
		let currentTheme = await getCurrentTheme();
		expect( currentTheme.slug ).toBe( 'twentytwelve' );

		// Switch to blocks theme
		await switchToBlocksTheme();
		currentTheme = await getCurrentTheme();
		expect( currentTheme.slug ).toBe( 'twentytwentyfive' );

		// Switch back to shortcode theme
		await switchToShortcodeTheme();
		currentTheme = await getCurrentTheme();
		expect( currentTheme.slug ).toBe( 'twentytwelve' );
	} );

	test( 'should handle theme already being active', async ( { page } ) => {
		// Switch to shortcode theme
		await switchToShortcodeTheme();

		// Switch to the same theme again (should not fail)
		await switchToShortcodeTheme();

		const currentTheme = await getCurrentTheme();
		expect( currentTheme.slug ).toBe( 'twentytwelve' );
	} );
} );
