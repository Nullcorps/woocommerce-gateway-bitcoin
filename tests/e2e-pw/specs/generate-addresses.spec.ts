import { test, expect } from '@playwright/test';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { placeBitcoinOrder } from '../helpers/place-bitcoin-order';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { testConfig } from '../config/test-config';

test.describe('Generate new addresses', () => {
  test.setTimeout(60000);

  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await configureBitcoinXpub(page);
    await createSimpleProduct(page);
    await page.close();
  });

  test('should generate addresses when number available falls below 50', async ({ page }) => {
    const baseUrl = testConfig.url;
    
    // Login as admin
    await page.goto(`${baseUrl}wp-login.php`);
    await page.fill('#user_login', testConfig.users.admin.username);
    await page.fill('#user_pass', testConfig.users.admin.password);
    await page.click('#wp-submit');
    
    // Visit list of unused addresses
    await page.goto(`${baseUrl}wp-admin/edit.php?post_type=bh-bitcoin-address&post_status=unused`);
    
    // Get count of unused addresses
    const unusedCountElement = page.locator('.unused a .count');
    let unusedCountText = await unusedCountElement.textContent();
    let unusedCount = parseInt(unusedCountText?.replace(/[^\d]/g, '') || '0');
    
    // Delete addresses until we have fewer than 50
    while (unusedCount >= 50) {
      await page.check('#cb-select-all-1');
      await page.selectOption('#bulk-action-selector-top', 'trash');
      await page.click('#doaction');
      
      // Wait for the bulk action to complete
      await page.waitForSelector('.updated.notice', { timeout: 10000 });
      
      // Refresh count
      unusedCountText = await unusedCountElement.textContent();
      unusedCount = parseInt(unusedCountText?.replace(/[^\d]/g, '') || '0');
    }
    
    // Place an order to trigger address generation
    await placeBitcoinOrder(page);
    
    // Login as admin again
    await page.goto(`${baseUrl}wp-login.php`);
    await page.fill('#user_login', testConfig.users.admin.username);
    await page.fill('#user_pass', testConfig.users.admin.password);
    await page.click('#wp-submit');
    
    // Check Action Scheduler for pending job
    await page.goto(`${baseUrl}wp-admin/tools.php?page=action-scheduler&status=pending`);
    
    const pendingJob = page.locator('td[data-colname="Hook"]:has-text("bh_wp_bitcoin_gateway_generate_new_addresses")');
    
    if (await pendingJob.count() > 0) {
      // Run the job
      await pendingJob.hover();
      const runButton = pendingJob.locator('.run a');
      if (await runButton.count() > 0) {
        await runButton.click();
        await page.waitForLoadState('networkidle');
      }
    }
    
    // Check addresses page
    await page.goto(`${baseUrl}wp-admin/edit.php?post_type=bh-bitcoin-address`);
    
    // Wait for unknown addresses to be processed
    while (await page.locator('.unknown a .count').count() > 0) {
      await page.waitForTimeout(1000);
      await page.reload();
    }
    
    // Verify we have at least 50 unused addresses
    const finalUnusedCountElement = page.locator('.unused a .count');
    const finalUnusedCountText = await finalUnusedCountElement.textContent();
    const finalUnusedCount = parseInt(finalUnusedCountText?.replace(/[^\d]/g, '') || '0');
    
    expect(finalUnusedCount).toBeGreaterThanOrEqual(50);
  });

  test('should correctly report the all addresses count', async ({ page }) => {
    const baseUrl = testConfig.url;
    
    // Login as admin
    await page.goto(`${baseUrl}wp-login.php`);
    await page.fill('#user_login', testConfig.users.admin.username);
    await page.fill('#user_pass', testConfig.users.admin.password);
    await page.click('#wp-submit');
    
    await page.goto(`${baseUrl}wp-admin/edit.php?post_type=bh-bitcoin-address`);
    
    // Get all address counts
    const allCountElement = page.locator('.all a .count');
    const allCountText = await allCountElement.textContent();
    const allCount = parseInt(allCountText?.replace(/[^\d]/g, '') || '0');
    
    expect(allCount).not.toEqual(0);
    
    const unusedCountElement = page.locator('.unused a .count');
    const unusedCountText = await unusedCountElement.textContent();
    const unusedCount = parseInt(unusedCountText?.replace(/[^\d]/g, '') || '0');
    
    let assignedCount = 0;
    const assignedCountElement = page.locator('.assigned a .count');
    if (await assignedCountElement.count() > 0) {
      const assignedCountText = await assignedCountElement.textContent();
      assignedCount = parseInt(assignedCountText?.replace(/[^\d]/g, '') || '0');
    }
    
    expect(unusedCount + assignedCount).toEqual(allCount);
  });
});