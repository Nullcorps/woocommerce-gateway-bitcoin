// It seems when a payment is checked for the first time and no transaction is found,
// it doesn't properly schedule the next check. I think it's because `as_has_scheduled_action( $hook, $args )`
// is returning true for the action that is currently running.

const {
    merchant, uiUnblocked
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );

const configureBitcoinXpub = require( './configure-bitcoin-xpub.before.js' );
const placeBitcoinOrderBefore = require( './place-bitcoin-order.before.js' );

describe('Schedule payment checks', () => {

    // Configure the gateway.
    beforeAll(async () => {
        await configureBitcoinXpub();
    });

    it('should schedule new payment check after each check that does not have payment', async () => {

        // Open Action Scheduler and delete any existing events
        await merchant.login();
        let actionSchedulerUrlPending = 'http://localhost:8084/wp-admin/tools.php?page=action-scheduler&status=pending&s=bh_wc_bitcoin_gateway_check_unpaid_order';
        await page.goto( actionSchedulerUrlPending, {waitUntil: 'networkidle0',} );

        var [tableContainsNoItems] = await page.$x(`//td[contains(text(), "No items found")]`);

        if( Object.keys(tableContainsNoItems).length !== 0 ) {
            console.log( '"No items found" message seen when searching pending actions for "bh_wc_bitcoin_gateway_check_unpaid_order"' );

        } else {
            // Use `setCheckbox()`?
            await page.waitForSelector('#cb-select-all-1');
            await page.click('#cb-select-all-1');
            // Select the "Bulk actions" > "Delete" option.
            await page.select('#bulk-action-selector-top', 'delete');
            // Submit the form to send all bh_wc_bitcoin_gateway_check_unpaid_order actions to the trash.
            await page.click('#doaction');
            await uiUnblocked();
        }

        // Arrange.
        await placeBitcoinOrderBefore();

        // `.woocommerce-order-overview__order .order`
        // Order number:
        // 135

        await merchant.login();

        // Act.

        // Hook
        // "bh_wc_bitcoin_gateway_check_unpaid_order"

        // Arguments
        // "'order_id' => 135"

        // Open Action Scheduler and look for the event.
        let actionSchedulerUrl = 'http://localhost:8084/wp-admin/tools.php?page=action-scheduler&status=pending';
        await page.goto( actionSchedulerUrl, {waitUntil: 'networkidle0'} );

        var [pendingJobHookColumnTdElement] = await page.$x(`//td[@data-colname="Hook"][contains(text(), "bh_wc_bitcoin_gateway_check_unpaid_order")]`);

        if (!pendingJobHookColumnTdElement) {
            console.log( 'Did not find bh_wc_bitcoin_gateway_check_unpaid_order job in pending actions. BAD!');
        }

        var tdText = await page.evaluate(element => element.textContent, pendingJobHookColumnTdElement);
        console.log('tdText: ' + tdText);

        // Focus to unveil actions.
        await pendingJobHookColumnTdElement.focus();

        var runLink = await pendingJobHookColumnTdElement.$('.run a');

        if( runLink) {
            runLink.focus();
            console.log("Running bh_wc_bitcoin_gateway_check_unpaid_order action");
            await Promise.all(
                [
                    page.keyboard.press('Enter'),
                    page.waitForNavigation({waitUntil: 'networkidle0'})
                ]
            );
        } else {
            console.log( 'runLink not found (.run a)');
        }

        var [pendingJobHookColumnTdElementAfter] = await page.$x(`//td[@data-colname="Hook"][contains(text(), "bh_wc_bitcoin_gateway_check_unpaid_order")]`);

        if (pendingJobHookColumnTdElementAfter) {

            console.log('DID find bh_wc_bitcoin_gateway_check_unpaid_order job in pending actions. Good.');
        } else {
            console.log('Did NOT find bh_wc_bitcoin_gateway_check_unpaid_order job in pending actions. Bad. ');
        }

        expect(pendingJobHookColumnTdElementAfter).toHaveLength(1);

    });

});
