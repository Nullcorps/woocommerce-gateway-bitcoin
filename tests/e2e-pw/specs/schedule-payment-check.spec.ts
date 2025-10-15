/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	ActionSchedulerItem,
	deleteAction,
	fetchActionsWithArgs,
} from '../helpers/rest/action-scheduler';
import { switchToShortcodeTheme } from '../helpers/rest/theme-switcher';
import {
	getActionSchedulerTableRowForOrder,
	runActionInRow,
} from '../helpers/ui/action-scheduler';
import { configureBitcoinXpub } from '../helpers/ui/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/ui/create-simple-product';
import { loginAsAdmin } from '../helpers/ui/login';
import { placeBitcoinOrder } from '../helpers/ui/place-bitcoin-order';
import { setOrderStatus } from '../helpers/ui/wc-order';

test.describe( 'Schedule payment checks', () => {
	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await configureBitcoinXpub( page );
		await createSimpleProduct( page );
		await page.close();
	} );

	async function getPendingUnpaidOrderActionForOrder(
		orderId: number
	): Promise< [ Record< string, ActionSchedulerItem > ] > {
		return await fetchActionsWithArgs(
			'bh_wp_bitcoin_gateway_check_unpaid_order',
			{ order_id: orderId }
		);
	}

	async function hasPendingUnpaidOrderActionForOrder(
		orderId: number
	): Promise< boolean > {
		const actionsForOrder =
			await getPendingUnpaidOrderActionForOrder( orderId );

		return Object.entries( actionsForOrder ).length > 0;
	}

	async function deleteUnpaidOrderActions(
		orderId: number
	): Promise< void > {
		const actionsForOrder =
			await getPendingUnpaidOrderActionForOrder( orderId );
		for ( const key of Object.keys( actionsForOrder ) ) {
			await deleteAction( parseInt( key ) );
		}
	}

	test( 'should schedule a payment check when a Bitcoin order is placed', async ( {
		page,
	} ) => {
		await switchToShortcodeTheme();

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
		await deleteUnpaidOrderActions( orderId );
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

		await page.waitForLoadState( 'networkidle' );

		const unpaidOrders =
			await getPendingUnpaidOrderActionForOrder( orderId );
		const isScheduledAfter = unpaidOrders.length === 0;
		expect(
			isScheduledAfter,
			`Expected bh_wp_bitcoin_gateway_check_unpaid_order Action Scheduler job to be deleted for order_id:  ${ orderId }`
		).toBe( false );
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
