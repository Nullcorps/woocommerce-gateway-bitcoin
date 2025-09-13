/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { testConfig } from '../config/test-config';
import {
	getBitcoinAddressCount,
	deleteBitcoinAddresses,
} from '../helpers/bitcoin-address';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { loginAsAdmin, logout } from '../helpers/login';
import { placeBitcoinOrder } from '../helpers/place-bitcoin-order';

test.describe( 'Generate new addresses', () => {
	test.setTimeout( 60000 );

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await configureBitcoinXpub( page );
		await createSimpleProduct( page );
		await page.close();
	} );

	test( 'should generate addresses when number available falls below 20', async ( {
		page,
	} ) => {
		/**
		 * Delete all but 19 unused addresses to test the generation
		 * 20 is the threshold to trigger generation
		 * @see API::generate_new_addresses_for_wallet()
		 */
		const beforeDeletingUnusedCount =
			await getBitcoinAddressCount( 'unused' );
		const toDelete = beforeDeletingUnusedCount - 49;
		if ( toDelete > 0 ) {
			await deleteBitcoinAddresses( toDelete, 'unused' );
			const afterDeletingUnusedCount =
				await getBitcoinAddressCount( 'unused' );
		} else {
			const afterDeletingUnusedCount = beforeDeletingUnusedCount;
		}

		// Place an order to trigger address generation
		await placeBitcoinOrder( page );

		// Login as admin again
		await loginAsAdmin( page );

		// Check Action Scheduler for pending job
		await page.goto(
			'/wp-admin/tools.php?page=action-scheduler&status=pending'
		);

		const pendingJob = page.locator(
			'td[data-colname="Hook"]:has-text("bh_wp_bitcoin_gateway_generate_new_addresses")'
		);

		if ( ( await pendingJob.count() ) > 0 ) {
			// Run the job
			await pendingJob.hover();
			const runButton = pendingJob.locator( '.run a' );
			if ( ( await runButton.count() ) > 0 ) {
				await runButton.click();
				await page.waitForLoadState( 'networkidle' );
			}
		}

		const finalUnusedCount = await getBitcoinAddressCount( 'unused' );

		expect( finalUnusedCount ).toBeGreaterThanOrEqual( 20 );
	} );

	test( 'should correctly report the all addresses count', async ( {
		page,
	} ) => {
		// Login as admin
		await loginAsAdmin( page );

		await page.goto( '/wp-admin/edit.php?post_type=bh-bitcoin-address' );

		// Get all address counts
		const allCountElement = page.locator( '.all a .count' );
		const allCountText = await allCountElement.textContent();
		const allCount = parseInt(
			allCountText?.replace( /[^\d]/g, '' ) || '0'
		);

		expect( allCount ).not.toEqual( 0 );

		const unusedCountElement = page.locator( '.unused a .count' );
		const unusedCountText = await unusedCountElement.textContent();
		const unusedCount = parseInt(
			unusedCountText?.replace( /[^\d]/g, '' ) || '0'
		);

		let assignedCount = 0;
		const assignedCountElement = page.locator( '.assigned a .count' );
		if ( ( await assignedCountElement.count() ) > 0 ) {
			const assignedCountText = await assignedCountElement.textContent();
			assignedCount = parseInt(
				assignedCountText?.replace( /[^\d]/g, '' ) || '0'
			);
		}

		expect( unusedCount + assignedCount ).toEqual( allCount );
	} );
} );
