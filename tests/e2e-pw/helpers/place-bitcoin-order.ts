import { Page } from '@playwright/test';
import { testConfig } from '../config/test-config';

export async function placeBitcoinOrder(page: Page): Promise<string> {
  const baseUrl = testConfig.url;
  const billing = testConfig.addresses.customer.billing;
  
  // Go to shop
  await page.goto(`${baseUrl}shop/`);
  
  // Add simple product to cart
  await page.click(`text="${testConfig.products.simple.name}"`);
  await page.click('.single_add_to_cart_button');
  
  // Go to checkout
  await page.goto(`${baseUrl}checkout/`);
  
  // Fill billing details
  await page.fill('#billing_first_name', billing.firstname);
  await page.fill('#billing_last_name', billing.lastname);
  await page.fill('#billing_company', billing.company);
  await page.selectOption('#billing_country', 'US');
  await page.fill('#billing_address_1', billing.addressfirstline);
  await page.fill('#billing_address_2', billing.addresssecondline);
  await page.fill('#billing_city', billing.city);
  await page.selectOption('#billing_state', billing.state);
  await page.fill('#billing_postcode', billing.postcode);
  await page.fill('#billing_phone', billing.phone);
  await page.fill('#billing_email', billing.email);
  
  // Wait for page to be ready
  await page.waitForTimeout(2000);
  
  // Select Bitcoin payment method
  await page.click('label[for="payment_method_bitcoin_gateway"]');
  await page.waitForTimeout(1000);
  
  // Verify Bitcoin payment method description appears
  await page.waitForSelector('.payment_method_bitcoin_gateway', { state: 'visible' });
  
  // Place order
  await page.click('#place_order');
  
  // Wait for order received page
  await page.waitForSelector('text=Order received', { timeout: 30000 });
  
  // Extract order ID from URL
  const url = page.url();
  const orderIdMatch = url.match(/order-received\/(\d+)\//);
  const orderId = orderIdMatch ? orderIdMatch[1] : '';
  
  return orderId;
}