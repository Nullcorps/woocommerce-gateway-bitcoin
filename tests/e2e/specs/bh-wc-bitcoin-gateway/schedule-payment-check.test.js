// It seems when a payment is checked for the first time and no transaction is found,
// it doesn't properly schedule the next check. I think it's because `as_has_scheduled_action( $hook, $args )`
// is returning true for the action that is currently running.

const {
    merchant, uiUnblocked, waitForSelectorWithoutThrow, createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );

const configureBitcoinXpub = require( './configure-bitcoin-xpub.before.js' );
const placeBitcoinOrderBefore = require( './place-bitcoin-order.before.js' );

jest.setTimeout(60000);

describe('Schedule payment checks', () => {

    // Configure the gateway.
    beforeAll(async () => {
        await merchant.login();
        await createSimpleProduct();
        await configureBitcoinXpub();
    });

    // Clear the all pending jobs.
    beforeEach( async () => {
        await deletePendingActionSchedulerPaymentChecks();
    });

    // Open Action Scheduler and delete any existing events
    async function deletePendingActionSchedulerPaymentChecks() {

        let actionSchedulerUrlPending = 'http://localhost:8084/wp-admin/tools.php?page=action-scheduler&status=pending&s=bh_wc_bitcoin_gateway_check_unpaid_order';
        await page.goto( actionSchedulerUrlPending, {waitUntil: 'networkidle0',} );
        

        const found = await waitForSelectorWithoutThrow('#bulk-action-selector-top');

        if (found) {
            // TODO: use `setCheckbox()`?
            await page.click('#cb-select-all-1');
            // Select the "Bulk actions" > "Delete" option.
            await page.select('#bulk-action-selector-top', 'delete');
            // Submit the form to send all bh_wc_bitcoin_gateway_check_unpaid_order actions to the trash.
            await page.click('#doaction');
            
        }
    }

    async function isJobScheduledForOrder( orderId ) {
        var actionSchedulerTableRowForOrder = await getActionSchedulerTableRowForOrder(orderId);

        return typeof actionSchedulerTableRowForOrder !== 'undefined';
    }

    async function getActionSchedulerTableRowForOrder( orderId ) {

        let actionSchedulerUrl = 'http://localhost:8084/wp-admin/tools.php?page=action-scheduler&status=pending&s=bh_wc_bitcoin_gateway_check_unpaid_order';
        await page.goto( actionSchedulerUrl, {waitUntil: 'networkidle0'} );

        var [pendingJobTrElement] = await page.$x(`//td[@data-colname="Arguments"][contains(., "'order_id' => ${orderId}")]/..`);

        return pendingJobTrElement;
    }

    async function setOrderStatus(orderId, status){

        await merchant.goToOrder(orderId);

        await expect(page.title()).resolves.toMatch('Edit order');
        await merchant.updateOrderStatus(orderId, status);
        
    }


    async function runActionInRow(actionSchedulerTableRow) {

        var [pendingJobHookColumnTdElement] = await actionSchedulerTableRow.$x(`//td[@data-colname="Hook"]`);

        expect(pendingJobHookColumnTdElement).toBeDefined();

        // Focus to unveil actions.
        await pendingJobHookColumnTdElement.focus();

        var runLink = await pendingJobHookColumnTdElement.$('.run a');

        expect(runLink).toBeDefined();

        runLink.focus();

        await Promise.all(
            [
                page.keyboard.press('Enter'),
                page.waitForNavigation({waitUntil: 'networkidle0'})
            ]
        );
    }


    it('should schedule a payment check when a Bitcoin order is placed', async() => {

        let orderId = await placeBitcoinOrderBefore();
        await merchant.login();

        let isScheduled = await isJobScheduledForOrder(orderId);
        expect(isScheduled).toBe(true);
    });

    it('should schedule a payment check when a Bitcoin orders status is set to on-hold', async() => {

        let orderId = await placeBitcoinOrderBefore();
        await merchant.login();

        await setOrderStatus(orderId, 'wc-pending');

        await deletePendingActionSchedulerPaymentChecks();

        await setOrderStatus(orderId, 'wc-on-hold');

        let isScheduled = await isJobScheduledForOrder(orderId);
        expect(isScheduled).toBe(true);
    });

    it('should cancel the scheduled check when the order is marked paid', async() => {
        let orderId = await placeBitcoinOrderBefore();
        await merchant.login();

        let isScheduledBefore = await isJobScheduledForOrder(orderId);
        expect(isScheduledBefore).toEqual(true);

        await setOrderStatus(orderId, 'wc-processing');

        let isScheduledAfter = await isJobScheduledForOrder(orderId);
        expect(isScheduledAfter).toBe(false);
    });

    it('should schedule a payment check when a Bitcoin orders status is set to on hold via the bulk actions menu', async() => {

        let orderId = await placeBitcoinOrderBefore();
        await merchant.login();

        await setOrderStatus(orderId, 'wc-pending');

        await deletePendingActionSchedulerPaymentChecks();

        await merchant.openAllOrdersView();

        await page.click('#cb-select-all-1');
        // Select the "Bulk actions" > "Change status to on-hold" option.
        await page.select('#bulk-action-selector-top', 'mark_on-hold');
        await page.click('#doaction');

        let isScheduled = await isJobScheduledForOrder(orderId);
        expect(isScheduled).toBe(true);
    });

    // The old way was to create a new scheduled task each time the order was checked for payment but had not yet been paid.
    // The new way is to create a single repeating task, and only cancel that task when the order is paid. (TODO: or expiry time passes).
    it('should schedule new payment check after each check that does not have payment', async () => {

        // Arrange.
        let orderId = await placeBitcoinOrderBefore();
        await merchant.login();

        let tableRowForOrder = await getActionSchedulerTableRowForOrder(orderId);

        await runActionInRow(tableRowForOrder);

        let isScheduled = await isJobScheduledForOrder(orderId);
        expect(isScheduled).toBe(true);
    });

});
