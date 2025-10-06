/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	deleteUnpaidOrderActions,
	fetchActions,
} from '../helpers/rest/action-scheduler';
import {
	runActionInRow,
} from '../helpers/ui/action-scheduler';
import { configureBitcoinXpub } from '../helpers/ui/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/ui/create-simple-product';
import { loginAsAdmin } from '../helpers/ui/login';
import { placeBitcoinOrder } from '../helpers/ui/place-bitcoin-order';
import { switchToShortcodeTheme } from '../helpers/rest/theme-switcher';
import { setOrderStatus } from '../helpers/ui/wc-order';

test.describe( 'Schedule payment checks', () => {
	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await configureBitcoinXpub( page );
		await createSimpleProduct( page );
		// await page.close();
	} );

	// PlaywrightTestArgs & PlaywrightTestOptions & PlaywrightWorkerArgs & PlaywrightWorkerOptions'
	test.beforeEach( async () => {
		await deleteUnpaidOrderActions();
	} );

	async function hasPendingUnpaidOrderActionForOrder(
		orderId: number
	): Promise< boolean > {
		const unpaidOrderActions = await fetchActions(
			'bh_wp_bitcoin_gateway_check_unpaid_order'
		);

		const count = Object.entries( unpaidOrderActions ).reduce( function (
			total,
			actionIterated: Array< any >
		) {
			const action: Object = actionIterated[ 1 ];
			const args: Object = action.args;
			const argsOrderId: number = args.order_id;

			const status: string = action.status;

			if (
				argsOrderId > 0 &&
				argsOrderId === orderId &&
				status === 'pending'
			) {
				return total + 1;
			}
			return total;
		}, 0 );

		return count > 0; // !== null;
	}

	async function getActionSchedulerTableRowForOrder(
		page: any,
		orderId: number
	) {
		const actionSchedulerUrl =
			'/wp-admin/tools.php?page=action-scheduler&status=pending&s=bh_wp_bitcoin_gateway_check_unpaid_order';
		await page.goto( actionSchedulerUrl );

		const rowSelector = `td[data-colname="Arguments"]:has-text("'order_id' => ${ orderId }")`;
		const tableRow = page.locator( rowSelector ).locator( '..' ).first();

		return ( await tableRow.count() ) > 0 ? tableRow : null;
	}

	test( 'should schedule a payment check when a Bitcoin order is placed', async ( {
		page,
	} ) => {
		await switchToShortcodeTheme();
		await deleteUnpaidOrderActions();

		const orderId = await placeBitcoinOrder( page );

		// Login as admin to check action scheduler
		await loginAsAdmin( page );

		const isScheduled =
			await hasPendingUnpaidOrderActionForOrder( orderId );
		expect(
			isScheduled,
			`Expected bh_wp_bitcoin_gateway_check_unpaid_order Action Scheduler job for order_id:  ${ orderId }`
		).toBe( true );
	} );

	test( 'should schedule a payment check when a Bitcoin orders status is set to on-hold', async ( {
		page,
	} ) => {
		const orderId = await placeBitcoinOrder( page );

		// Login as admin
		await loginAsAdmin( page );

		await setOrderStatus( page, orderId, 'wc-pending' );
		await deleteUnpaidOrderActions();
		await setOrderStatus( page, orderId, 'wc-on-hold' );

		const isScheduled =
			await hasPendingUnpaidOrderActionForOrder( orderId );
		expect( isScheduled ).toBe( true );
	} );

	test( 'should cancel the scheduled check when the order is marked paid', async ( {
		page,
	} ) => {
		const orderId = await placeBitcoinOrder( page );

		const isScheduledBefore =
			await hasPendingUnpaidOrderActionForOrder( orderId );
		expect( isScheduledBefore ).toBe( true );

		await loginAsAdmin( page );
		await setOrderStatus( page, orderId, 'wc-processing' );

		const isScheduledAfter =
			await hasPendingUnpaidOrderActionForOrder( orderId );
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

		const isScheduled =
			await hasPendingUnpaidOrderActionForOrder( orderId );
		expect( isScheduled ).toBe( true );
	} );
} );
