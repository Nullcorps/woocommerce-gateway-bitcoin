/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { testConfig } from '../config/test-config';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { loginAsAdmin } from '../helpers/login';
import { placeBitcoinOrder } from '../helpers/place-bitcoin-order';

test.describe( 'Schedule payment checks', () => {
	test.setTimeout( 60000 );

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await configureBitcoinXpub( page );
		await createSimpleProduct( page );
		await page.close();
	} );

	test.beforeEach( async ( { page } ) => {
		await deletePendingActionSchedulerPaymentChecks( page );
	} );

	async function deletePendingActionSchedulerPaymentChecks( page: any ) {
		const actionSchedulerUrl =
			'/wp-admin/tools.php?page=action-scheduler&status=pending&s=bh_wp_bitcoin_gateway_check_unpaid_order';

		// Login as admin
		await loginAsAdmin( page );

		await page.goto( actionSchedulerUrl );

		const bulkSelector = page.locator( '#bulk-action-selector-top' );
		if ( ( await bulkSelector.count() ) > 0 ) {
			await page.check( '#cb-select-all-1' );
			await page.selectOption( '#bulk-action-selector-top', 'delete' );
			await page.click( '#doaction' );
		}
	}

	async function isJobScheduledForOrder(
		page: any,
		orderId: string
	): Promise< boolean > {
		const tableRow = await getActionSchedulerTableRowForOrder(
			page,
			orderId
		);
		return tableRow !== null;
	}

	async function getActionSchedulerTableRowForOrder(
		page: any,
		orderId: string
	) {
		const actionSchedulerUrl =
			'/wp-admin/tools.php?page=action-scheduler&status=pending&s=bh_wp_bitcoin_gateway_check_unpaid_order';
		await page.goto( actionSchedulerUrl );

		const rowSelector = `td[data-colname="Arguments"]:has-text("'order_id' => ${ orderId }")`;
		const tableRow = page.locator( rowSelector ).locator( '..' ).first();

		return ( await tableRow.count() ) > 0 ? tableRow : null;
	}

	async function setOrderStatus(
		page: any,
		orderId: string,
		status: string
	) {
		// Navigate to edit order page
		await page.goto( `/wp-admin/post.php?post=${ orderId }&action=edit` );

		// Update order status
		await page.selectOption( '#order_status', status );
		await page.click( '#woocommerce-order-actions .save_order' );
		await page.waitForSelector( '.notice-success', { timeout: 10000 } );
	}

	async function runActionInRow( page: any, actionSchedulerTableRow: any ) {
		const hookColumn = actionSchedulerTableRow.locator(
			'td[data-colname="Hook"]'
		);
		await hookColumn.hover();

		const runLink = hookColumn.locator( '.run a' );
		if ( ( await runLink.count() ) > 0 ) {
			await runLink.click();
			await page.waitForLoadState( 'networkidle' );
		}
	}

	test( 'should schedule a payment check when a Bitcoin order is placed', async ( {
		page,
	} ) => {
		const orderId = await placeBitcoinOrder( page );

		// Login as admin to check action scheduler
		await loginAsAdmin( page );

		const isScheduled = await isJobScheduledForOrder( page, orderId );
		expect( isScheduled ).toBe( true );
	} );

	test( 'should schedule a payment check when a Bitcoin orders status is set to on-hold', async ( {
		page,
	} ) => {
		const orderId = await placeBitcoinOrder( page );

		// Login as admin
		await loginAsAdmin( page );

		await setOrderStatus( page, orderId, 'wc-pending' );
		await deletePendingActionSchedulerPaymentChecks( page );
		await setOrderStatus( page, orderId, 'wc-on-hold' );

		const isScheduled = await isJobScheduledForOrder( page, orderId );
		expect( isScheduled ).toBe( true );
	} );

	test( 'should cancel the scheduled check when the order is marked paid', async ( {
		page,
	} ) => {
		const orderId = await placeBitcoinOrder( page );

		// Login as admin
		await loginAsAdmin( page );

		const isScheduledBefore = await isJobScheduledForOrder( page, orderId );
		expect( isScheduledBefore ).toBe( true );

		await setOrderStatus( page, orderId, 'wc-processing' );

		const isScheduledAfter = await isJobScheduledForOrder( page, orderId );
		expect( isScheduledAfter ).toBe( false );
	} );

	test( 'should schedule new payment check after each check that does not have payment', async ( {
		page,
	} ) => {
		const orderId = await placeBitcoinOrder( page );

		// Login as admin
		await loginAsAdmin( page );

		const tableRowForOrder = await getActionSchedulerTableRowForOrder(
			page,
			orderId
		);
		if ( tableRowForOrder ) {
			await runActionInRow( page, tableRowForOrder );
		}

		const isScheduled = await isJobScheduledForOrder( page, orderId );
		expect( isScheduled ).toBe( true );
	} );
} );
