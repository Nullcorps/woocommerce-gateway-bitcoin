import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/login';
import config from "../../../playwright.config";

test.describe('Set log level', () => {
  test('should respect the log level that is saved on the gateway settings page', async ({ page }) => {
    // Login as admin
    await loginAsAdmin(page);
    
    // Navigate to Bitcoin gateway settings
    await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bitcoin_gateway');
    
    // Set log level to notice
    await page.selectOption('#woocommerce_bitcoin_gateway_log_level', 'notice');
    
    // Save changes
    await page.click('.woocommerce-save-button');
    await page.waitForSelector('.notice-success', { timeout: 10000 });

    const baseURL: string = config.use.baseURL;
    // Navigate to logs page
    await page.goto(baseURL + '/wp-admin/admin.php?page=bh-wp-bitcoin-gateway-logs');
    
    // Verify log level is set to Notice
    await expect(page.locator('text=Current log level: Notice')).toBeVisible();
  });
});