// Fix bugs when refreshing the payment details box (i.e. looking for new transactions).

// 1. Place an order, arrive on the Thank You page, press refresh.
// Does not work when logged out.

// import {expect, jest, test} from '@jest/globals';
import {expect} from '@jest/globals';

const {
    shopper,
    uiUnblocked, merchant, setCheckbox, settingsPageSaveChanges, verifyCheckboxIsSet, clearAndFillInput, createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const configureBitcoinXpub = require( './configure-bitcoin-xpub.before.js' );
const placeBitcoinOrderBefore = require( './place-bitcoin-order.before.js' );


describe('Refresh order details', () => {

    // Configure the gateway.
    beforeAll(async () => {
        await configureBitcoinXpub();
        await createSimpleProduct();
        await placeBitcoinOrderBefore();
    });

    // The last checked time is only specific to the minute, so unless we let each test
    // run for 60 seconds, we can't be sure the text updated... unless we seed it with
    // something else!
    // .bh_wc_bitcoin_gateway_last_checked_time
    it('should successfully refresh the details for logged out user', async () => {

        await expect(page).toMatch('Exchange rate at time of order');


        // Get the last checked time
        // e.g. "January 18, 2023, 4:15 pm +00:00",
        let lastCheckedHtmlElement = await page.$('.bh_wc_bitcoin_gateway_last_checked_time');

        // Change that text so we know when it is updated later.
        await page.evaluate( 'document.querySelector(".bh_wc_bitcoin_gateway_last_checked_time:first-child").innerHTML = "TEXT WHICH SHOULD BE UPDATED AFTER REFRESH REQUEST"' );

        // Get the element's text value.
        var lastCheckedText = await page.evaluate(element => element.textContent, lastCheckedHtmlElement);
        lastCheckedText = lastCheckedText.trim();

        // And click the last checked element to refresh
        await page.click('.bh_wc_bitcoin_gateway_last_checked_time', { text: lastCheckedText } );

        await uiUnblocked();

        var lastCheckedTextNew = await page.evaluate(element => element.textContent, lastCheckedHtmlElement);
        lastCheckedTextNew = lastCheckedTextNew.trim();

        expect( lastCheckedTextNew ).not.toEqual( lastCheckedText );
    });

});
