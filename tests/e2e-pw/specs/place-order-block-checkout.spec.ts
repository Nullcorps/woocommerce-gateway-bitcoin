import { test, expect } from '@playwright/test';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { testConfig } from '../config/test-config';

test.describe('Place orders on block checkout', () => {
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await configureBitcoinXpub(page);
    await createSimpleProduct(page);
    await page.close();
  });

  test('can see Bitcoin payment option on block checkout', async ({ page }) => {
    const baseUrl = testConfig.url;
    
    // Go to shop and add product to cart
    await page.goto(`${baseUrl}shop/`);
    await page.click(`text="${testConfig.products.simple.name}"`);
    await page.click('.single_add_to_cart_button');
    
    // Go to block checkout
    await page.goto(`${baseUrl}blocks-checkout/`);
    
    // Click Bitcoin payment option
    await page.click('.wc-block-components-payment-method-label:has-text("Bitcoin")');
    
    // Verify Bitcoin payment option is selected
    await expect(page.locator('.wc-block-components-payment-method-label:has-text("Bitcoin")')).toBeVisible();
  });

  test('should successfully place order and show payment details', async ({ page }) => {
    const baseUrl = testConfig.url;
    const billing = testConfig.addresses.customer.billing;
    
    // Go to shop and add product to cart
    await page.goto(`${baseUrl}shop/`);
    await page.click(`text="${testConfig.products.simple.name}"`);
    await page.click('.single_add_to_cart_button');
    
    // Go to block checkout
    await page.goto(`${baseUrl}blocks-checkout/`);
    
    // Fill billing details
    await page.fill('#email', billing.email);
    await page.fill('#billing-first_name', billing.firstname);
    await page.fill('#billing-last_name', billing.lastname);
    await page.fill('#billing-country', billing.country);
    await page.fill('#billing-address_1', billing.addressfirstline);
    await page.fill('#billing-address_2', billing.addresssecondline);
    await page.fill('#billing-city', billing.city);
    await page.fill('#billing-state', billing.state);
    await page.fill('#billing-postcode', billing.postcode);
    
    // Wait for form to update
    await page.waitForTimeout(2000);
    
    // Select Bitcoin payment method
    await page.click('.wc-block-components-payment-method-label:has-text("Bitcoin")');
    await page.waitForTimeout(1000);
    
    // Verify Bitcoin payment description appears
    await expect(page.locator('.wp-block-woocommerce-checkout-payment-block:has-text("Pay quickly and easily with Bitcoin")')).toBeVisible();
    
    // Wait for place order button to be enabled
    await page.waitForSelector('.wc-block-components-checkout-place-order-button:not([disabled])');
    
    // Place order
    await page.click('.wc-block-components-checkout-place-order-button');
    
    // Wait for order received page
    await page.waitForSelector('text=Order received', { timeout: 30000 });
    
    // Verify payment details are shown
    await expect(page.locator('text=Order received')).toBeVisible();
    await expect(page.locator('text=Exchange rate at time of order')).toBeVisible();
  });
});