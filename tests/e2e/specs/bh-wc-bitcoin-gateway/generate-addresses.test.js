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
import {
    createNewPost,
    pressKeyTimes,
    publishPost,
    trashAllPosts,
    visitAdminPage,
} from '@wordpress/e2e-test-utils';

const {
    shopper,
    uiUnblocked, merchant, setCheckbox, settingsPageSaveChanges, verifyCheckboxIsSet, clearAndFillInput, createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const configureBitcoinXpub = require( './configure-bitcoin-xpub.before.js' );
const placeBitcoinOrderBefore = require( './place-bitcoin-order.before.js' );

describe('Generate new addresses', () => {

    // Configure the gateway.
    beforeAll(async () => {
        await configureBitcoinXpub();
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
