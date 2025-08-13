import { test, expect } from '@playwright/test';
import { testConfig } from '../config/test-config';

test.describe('Set log level', () => {
  test('should respect the log level that is saved on the gateway settings page', async ({ page }) => {
    const baseUrl = testConfig.url;
    
    // Login as admin
    await page.goto(`${baseUrl}wp-login.php`);
    await page.fill('#user_login', testConfig.users.admin.username);
    await page.fill('#user_pass', testConfig.users.admin.password);
    await page.click('#wp-submit');
    
    // Navigate to Bitcoin gateway settings
    await page.goto(`${baseUrl}wp-admin/admin.php?page=wc-settings&tab=checkout&section=bitcoin_gateway`);
    
    // Set log level to notice
    await page.selectOption('#woocommerce_bitcoin_gateway_log_level', 'notice');
    
    // Save changes
    await page.click('.woocommerce-save-button');
    await page.waitForSelector('.notice-success', { timeout: 10000 });
    
    // Navigate to logs page
    await page.goto(`${baseUrl}wp-admin/admin.php?page=bh-wp-bitcoin-gateway-logs`);
    
    // Verify log level is set to Notice
    await expect(page.locator('text=Current log level: Notice')).toBeVisible();
  });
});