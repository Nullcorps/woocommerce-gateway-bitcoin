import { test, expect } from '@playwright/test';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { switchToBlocksTheme, verifyTheme } from '../helpers/theme-switcher';
import { testConfig } from '../config/test-config';
import { fillBilling, useBlocksCheckout } from "../helpers/checkout";

test.describe('Place orders on block checkout', () => {
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await configureBitcoinXpub(page);
    await createSimpleProduct(page);

    // Switch to Twenty Twenty-Five theme for block checkout testing
    await switchToBlocksTheme();

    await useBlocksCheckout();

    await verifyTheme('blocks');
    
    await page.close();
  });

  test('can see Bitcoin payment option on block checkout', async ({ page }) => {
    // Verify we're using the correct theme for block checkout
    await verifyTheme('blocks');
    
    // Go to shop and add product to cart
    await page.goto('/shop/');
    await page.click(`text="${testConfig.products.simple.name}"`);
    await page.click('.single_add_to_cart_button');
    
    // Go to block checkout
    await page.goto('/checkout/');
    
    // Click Bitcoin payment option
    // await page.click('.wc-block-components-payment-method-label:has-text("Bitcoin")');
    await page.locator('#radio-control-wc-payment-method-options-bitcoin_gateway__label').click();
    
    // Verify Bitcoin payment option is selected
    await expect(page.locator('#radio-control-wc-payment-method-options-bitcoin_gateway__label')).toBeVisible();
    // await expect(page.locator('.wc-block-components-payment-method-label:has-text("Bitcoin")')).toBeVisible();

    // Verify Bitcoin payment description appears
    await expect(page.locator('.wp-block-woocommerce-checkout-payment-block:has-text("Pay quickly and easily with Bitcoin")')).toBeVisible();
  });

  test('should successfully place order and show payment details', async ({ page }) => {
    // Verify we're using the correct theme for block checkout
    await verifyTheme('blocks');
    
    // const billing = testConfig.addresses.customer.billing;

    // Go to shop and add product to cart
    await page.goto('/shop/');
    await page.click(`text="${testConfig.products.simple.name}"`);
    await page.click('.single_add_to_cart_button');
    
    // Go to block checkout
    await page.goto('/checkout/');

    await fillBilling(page);

    // Select Bitcoin payment method
    // await page.click('.wc-block-components-payment-method-label:has-text("Bitcoin")');
    await page.click('#radio-control-wc-payment-method-options-bitcoin_gateway');
    await page.waitForTimeout(1000);
    
    // Wait for place order button to be enabled
    await page.waitForSelector('.wc-block-components-checkout-place-order-button:not([disabled])');
    
    // Place order
    await page.locator('.wc-block-components-checkout-place-order-button').isEnabled();
    await page.click('.wc-block-components-checkout-place-order-button');

    await page.waitForLoadState('networkidle');

    // Wait for order received page
    await page.waitForSelector('text=Your order has been received', { timeout: 30000 });
    
    // Verify payment details are shown
    await expect(page.locator('text=Order received')).toBeVisible();
    await expect(page.locator('text=Exchange rate at time of order')).toBeVisible();
  });
});