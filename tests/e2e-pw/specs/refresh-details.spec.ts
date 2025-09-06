import { test, expect } from '@playwright/test';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { placeBitcoinOrder } from '../helpers/place-bitcoin-order';
import { switchToShortcodeTheme } from "../helpers/theme-switcher";
import { useBlocksCheckout, useShortcodeCheckout } from "../helpers/checkout";

test.describe('Refresh order details', () => {
  let orderId: string;

  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();

    await useShortcodeCheckout(page);

    await switchToShortcodeTheme(page);
    await configureBitcoinXpub(page);
    await createSimpleProduct(page);
    console.log('placeBitcoinOrder');
    orderId = await placeBitcoinOrder(page);
    await page.close();
  });

  test('should successfully refresh the details for logged out user', async ({ page }) => {


    // Ensure we're on the order received page
    await expect(page.locator('text=Thank you. Your order has been received.')).toBeVisible();
    
    // Get the last checked time element
    const lastCheckedElement = page.locator('.bh_wp_bitcoin_gateway_last_checked_time');
    
    // Change the text so we know when it updates later
    await page.evaluate(() => {
      const element = document.querySelector('.bh_wp_bitcoin_gateway_last_checked_time');
      if (element) {
        element.textContent = 'TEXT WHICH SHOULD BE UPDATED AFTER REFRESH REQUEST';
      }
    });
    
    // Get the current text
    let lastCheckedText = await lastCheckedElement.textContent();
    lastCheckedText = lastCheckedText?.trim() || '';
    
    // Click the element to refresh
    await lastCheckedElement.click();
    
    // Wait for the update
    await page.waitForTimeout(2000);
    
    // Get the new text
    let newLastCheckedText = await lastCheckedElement.textContent();
    newLastCheckedText = newLastCheckedText?.trim() || '';
    
    expect(newLastCheckedText).not.toEqual(lastCheckedText);
  });

  test('should successfully refresh the details twice', async ({ page }) => {

    await switchToShortcodeTheme(page);
    await placeBitcoinOrder(page);

    // Ensure we're on the order received page
    await expect(page.locator('text=Thank you. Your order has been received.')).toBeVisible();
    
    const lastCheckedElement = page.locator('.bh_wp_bitcoin_gateway_last_checked_time');
    
    // First refresh
    let lastCheckedText = await lastCheckedElement.textContent();
    lastCheckedText = lastCheckedText?.trim() || '';
    
    await lastCheckedElement.click();
    await page.waitForTimeout(2000);
    
    // Second refresh - change text so we know when it updates
    await page.evaluate(() => {
      const element = document.querySelector('.bh_wp_bitcoin_gateway_last_checked_time');
      if (element) {
        element.textContent = 'TEXT WHICH SHOULD BE UPDATED AFTER REFRESH REQUEST';
      }
    });
    
    lastCheckedText = await lastCheckedElement.textContent();
    lastCheckedText = lastCheckedText?.trim() || '';
    
    // Click again to refresh
    await lastCheckedElement.click();
    await page.waitForTimeout(2000);
    
    // Get the new text
    let newLastCheckedText = await lastCheckedElement.textContent();
    newLastCheckedText = newLastCheckedText?.trim() || '';
    
    expect(newLastCheckedText).not.toEqual(lastCheckedText);
  });
});