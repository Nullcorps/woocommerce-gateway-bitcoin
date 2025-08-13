import { Page } from '@playwright/test';
import { testConfig } from '../config/test-config';

export async function createSimpleProduct(page: Page) {
  const baseUrl = testConfig.url;
  
  // Login as admin
  await page.goto(`${baseUrl}wp-login.php`);
  await page.fill('#user_login', testConfig.users.admin.username);
  await page.fill('#user_pass', testConfig.users.admin.password);
  await page.click('#wp-submit');
  
  // Navigate to products page
  await page.goto(`${baseUrl}wp-admin/edit.php?post_type=product`);
  
  // Check if simple product already exists
  const existingProduct = await page.locator(`text="${testConfig.products.simple.name}"`).first();
  const productExists = await existingProduct.count() > 0;
  
  if (!productExists) {
    // Add new product
    await page.click('.page-title-action');
    
    // Fill product details
    await page.fill('#title', testConfig.products.simple.name);
    
    // Set regular price
    await page.fill('#_regular_price', '20.00');
    
    // Publish product
    await page.click('#publish');
    await page.waitForSelector('.notice-success', { timeout: 10000 });
  }
  
  // Logout
  await page.hover('#wp-admin-bar-my-account');
  await page.click('#wp-admin-bar-logout a');
}