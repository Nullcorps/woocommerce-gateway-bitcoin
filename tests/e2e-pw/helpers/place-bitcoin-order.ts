import { Page, expect } from '@playwright/test';
import { testConfig } from '../config/test-config';
import { detectCheckoutType, fillBilling } from "./checkout";
import { logout } from './login';

async function selectBitcoinPaymentMethod(page: Page, checkoutType?: string) {
  if (checkoutType === 'blocks') {
    await page.click('#radio-control-wc-payment-method-options-bitcoin_gateway');
  }else{
    await page.click('label[for="payment_method_bitcoin_gateway"]');
  }

  // Verify Bitcoin payment method description appears
  // await page.waitForSelector('.payment_method_bitcoin_gateway', { state: 'visible' });
  // Pay quickly and easily with Bitcoin
  await expect(page.getByText('Pay quickly and easily with Bitcoin')).toBeVisible();

  // TODO: else throw error.
}

export async function placeBitcoinOrder(page: Page): Promise<string> {

  await logout(page);

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
  const checkoutType = await detectCheckoutType(page);

  await selectBitcoinPaymentMethod(page, checkoutType);

  // Place order
  // await page.click(''Place Order');
  await page.getByText('Place Order').click();
  // await page.locator('.wc-block-components-checkout-place-order-button').isEnabled();
  // await page.click('.wc-block-components-checkout-place-order-button');

  // Wait for order received page
  await page.waitForSelector('text=Order received', { timeout: 30000 });
  
  // Extract order ID from URL
  const url = page.url();
  const orderIdMatch = url.match(/order-received\/(\d+)\//);
  const orderId = orderIdMatch ? orderIdMatch[1] : '';
  
  return orderId;
}