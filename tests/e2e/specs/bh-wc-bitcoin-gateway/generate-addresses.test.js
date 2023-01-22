// After running 25 tests, new addresses had not been generated!

// What is the threshold below which addresses are created?

// Configure the gateway.
// Check how many addresses have been created
// Place 10 test orders
// Confirm more addresses have been created

// First, it's reporting the wrong number: "All (0) | Unused (25)".

// This was really a problem with cron running in the docker environment.

// http://localhost:8084/wp-admin/site-health.php
// "Your site could not complete a loopback request"

jest.setTimeout(60000);

const {
    merchant
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );

const configureBitcoinXpub = require( './configure-bitcoin-xpub.before.js' );
const placeBitcoinOrderBefore = require( './place-bitcoin-order.before.js' );

describe('Generate new addresses', () => {

    // Configure the gateway.
    beforeAll(async () => {
        await configureBitcoinXpub();
    });

    it('should generate addresses when number available falls below 50', async () => {

        // Arrange.
        // API class says if the number drops below 50, it will generate 25 new ones.
        // Delete unused addresses until there are only 50 remaining.

        await merchant.login();

        // Visit list of unused addresses.
        await page.goto( 'http://localhost:8084/wp-admin/edit.php?post_type=bh-bitcoin-address&post_status=unused', {
            waitUntil: 'networkidle0',
        } );

        // Get count of unused addresses.
        var unusedAddressCountElement = await page.$('.unused a .count');
        var unusedAddressCountText = await page.evaluate(element => element.textContent, unusedAddressCountElement);
        var unusedAddressCountNum = unusedAddressCountText.replace(/[^\d]/g, '');

        console.log( 'unusedAddressCountNum: ' + unusedAddressCountNum );

        do {
            // Use `setCheckbox()`?
            await page.waitForSelector('#cb-select-all-1');
            await page.click('#cb-select-all-1');
            // Select the "bulk actions" > "trash" option.
            await page.select('#bulk-action-selector-top', 'trash');
            // Submit the form to send all draft/scheduled/published posts to the trash.
            await page.click('#doaction');
            await page.waitForXPath(
                '//*[contains(@class, "updated notice")]/p[contains(text(), "moved to the Trash.")]'
            );

            unusedAddressCountElement = await page.$('.unused a .count');
            unusedAddressCountText = await page.evaluate(element => element.textContent, unusedAddressCountElement);
            unusedAddressCountNum = unusedAddressCountText.replace(/[^\d]/g, '');

        } while ( unusedAddressCountNum >= 50 );

        console.log( 'unusedAddressCountNum: ' + unusedAddressCountNum );


        // Act.
        // Place an order – which should create a background job to generate the addresses.
        // Invoke cron – which should generate them.
        await placeBitcoinOrderBefore();


        // Open Action Scheduler and look for the event.

        let actionSchedulerUrl = 'http://localhost:8084/wp-admin/tools.php?page=action-scheduler&status=pending';
        await page.goto( actionSchedulerUrl, {waitUntil: 'networkidle0',} );

        await new Promise((r) => setTimeout(r, 2000));

        var [pendingJobName] = await page.$x(`//td[@data-colname="Hook"][contains(text(), "bh_wc_bitcoin_gateway_generate_new_addresses")]`);

        if (!pendingJobName) {

            console.log( 'Did not find bh_wc_bitcoin_gateway_generate_new_addresses job in pending actions. BAD?');


        } else {

            // await new Promise((r) => setTimeout(r, 2000));

            var tdText = await page.evaluate(element => element.textContent, pendingJobName);
            // tdText: bh_wc_bitcoin_gateway_generate_new_addressesRun | CancelShow more details
            console.log('tdText: ' + tdText);

            // Focus to unveil actions.
            await pendingJobName.focus();

            var runLink = await pendingJobName.$('.run a');

            if( runLink) {

                runLink.focus();
                await Promise.all([page.keyboard.press('Enter'), page.waitForNavigation({
                    waitUntil: 'networkidle0'
                })]);
                // await runLink.click();
            } else {
                console.log( 'runLink not found (.run a)');
            }
            // await pendingJobName.click('.run a', {text: 'Run'});
            // await page.waitForNavigation( { waitUntil: 'networkidle0' } );


            [pendingJobName] = await page.$x(`//td[@data-colname="Hook"][contains(text(), "bh_wc_bitcoin_gateway_generate_new_addresses")]`);

            if (pendingJobName) {

                console.log('DID find bh_wc_bitcoin_gateway_generate_new_addresses job in pending actions. Bad.');
            } else {
                console.log('Did NOT find bh_wc_bitcoin_gateway_generate_new_addresses job in pending actions. Good. ');
            }

        }


        await page.goto( 'http://localhost:8084/wp-admin/edit.php?post_type=bh-bitcoin-address', {
            waitUntil: 'networkidle0',
        } );

        // The corresponding background job to check the newly derived addresses for existing transactions should run now.
        var unknownAddressCountElement = await page.$('.unknown a .count');
        while ( unknownAddressCountElement ) {
            await new Promise((r) => setTimeout(r, 1000));

            await page.goto( 'http://localhost:8084/wp-admin/edit.php?post_type=bh-bitcoin-address', {
                waitUntil: 'networkidle0',
            } );

            unknownAddressCountElement = await page.$('.unknown a .count');
        }
        
        // Assert.
        let updatedUnusedAddressCountElement = await page.$('.unused a .count');
        var updatedUnusedAddressCountText = await page.evaluate(element => element.textContent, updatedUnusedAddressCountElement);
        var updatedUnusedAddressCountNumAfter = updatedUnusedAddressCountText.replace(/[\D]/g, '');
        console.log('updatedUnusedAddressCountNumAfter: ' + updatedUnusedAddressCountNumAfter );
        updatedUnusedAddressCountNumAfter = parseInt(updatedUnusedAddressCountNumAfter);

        console.log( 'updatedUnusedAddressCountNumAfter: ' + updatedUnusedAddressCountNumAfter );

        expect( updatedUnusedAddressCountNumAfter ).toBeGreaterThan(50 );

    });

    it('should correctly report the all addresses count', async () => {

        await merchant.login();

        await page.goto( 'http://localhost:8084/wp-admin/edit.php?post_type=bh-bitcoin-address', {
            waitUntil: 'networkidle0',
        } );

        // Filter posts list
        // All (0) |
        // Unused (54) |
        // Assigned (21)

        // .subsubsub .all a .count
        // .subsubsub .unused a .count
        // .subsubsub .assigned a .count

        let allAddressCountElement = await page.$('.all a .count');
        var allAddressCountText = await page.evaluate(element => element.textContent, allAddressCountElement);
        var allAddressCountNum = allAddressCountText.replace(/[\D]/g, '');
        allAddressCountNum = parseInt(allAddressCountNum);

        expect( allAddressCountNum !== 0 );

        let unusedAddressCountElement = await page.$('.unused a .count');
        var unusedAddressCountText = await page.evaluate(element => element.textContent, unusedAddressCountElement);
        var unusedAddressCountNum = unusedAddressCountText.replace(/[\D]/g, '');
        unusedAddressCountNum = parseInt(unusedAddressCountNum);

        var assignedAddressCountNum = 0;
        let assignedAddressCountElement = await page.$('.assigned a .count');
        if( assignedAddressCountElement ) {
            var assignedAddressCountText = await page.evaluate(element => element.textContent, assignedAddressCountElement);
            assignedAddressCountNum = assignedAddressCountText.replace(/[\D]/g, '');
            assignedAddressCountNum = parseInt(assignedAddressCountNum);
        }

        expect( (unusedAddressCountNum + assignedAddressCountNum ) === allAddressCountNum );

    });


});
