import { Page, expect } from '@playwright/test';
import { testConfig } from '../config/test-config';
import { fillBilling } from './checkout';

export async function placeBitcoinOrder(page: Page): Promise<string> {
  
  // Go to shop
  await page.goto('/shop/');
  
  // Add simple product to cart
  await page.click(`text="${testConfig.products.simple.name}"`);
  await page.click('.single_add_to_cart_button');
  
  // Go to checkout
  await page.goto('/checkout/');
  
  // Fill billing details
  await fillBilling(page);
  
  // Select Bitcoin payment method
  // await page.click('label[for="payment_method_bitcoin_gateway"]');
  // await page.waitForTimeout(1000);
  // radio-control-wc-payment-method-options-bitcoin_gateway
  await page.click('#radio-control-wc-payment-method-options-bitcoin_gateway');
  
  // Verify Bitcoin payment method description appears
  // await page.waitForSelector('.payment_method_bitcoin_gateway', { state: 'visible' });
  // Pay quickly and easily with Bitcoin
  await expect(page.getByText('Pay quickly and easily with Bitcoin')).toBeVisible();


  // Place order
  // await page.click(''Place Order');
  await page.getByText('Place Order').click();

  // Wait for order received page
  await page.waitForSelector('text=Order received', { timeout: 30000 });
  
  // Extract order ID from URL
  const url = page.url();
  const orderIdMatch = url.match(/order-received\/(\d+)\//);
  const orderId = orderIdMatch ? orderIdMatch[1] : '';
  
  return orderId;
}