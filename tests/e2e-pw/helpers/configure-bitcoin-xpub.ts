import { Page } from '@playwright/test';
import { loginAsAdmin, logout } from './login';

export async function configureBitcoinXpub(page: Page) {
  // Login as admin
  await loginAsAdmin(page);
  
  // Navigate to Bitcoin gateway settings
  await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bitcoin_gateway');
  
  // This is the empty "wp_plugin_wallet" wallet
  const xpub = 'zpub6n37hVDJHFyDG1hBERbMBVjEd6ws6zVhg9bMs5STo21i9DgDE9Z9KTedtGxikpbkaucTzpj79n6Xg8Zwb9kY8bd9GyPh9WVRkM55uK7w97K';
  
  // Check if it already filled in to save time
  const existingXpub = await page.locator('#woocommerce_bitcoin_gateway_xpub').inputValue();
  
  if (existingXpub !== xpub) {
    await page.fill('#woocommerce_bitcoin_gateway_xpub', xpub);
    await page.click('.woocommerce-save-button');
    await page.waitForSelector('.notice-success', { timeout: 10000 });
  }
  
  // Logout
  await logout(page);
}