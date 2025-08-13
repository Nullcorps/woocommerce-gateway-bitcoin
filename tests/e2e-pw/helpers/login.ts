import { Page, expect } from '@playwright/test';
import { testConfig } from '../config/test-config';

export async function loginAsAdmin(page: Page) {
  await page.goto('/wp-login.php');
  await page.fill('#user_login', testConfig.users.admin.username);
  await page.fill('#user_pass', testConfig.users.admin.password);

  const locator = page.locator('#wp-submit');
  await expect(locator).toBeEnabled();

  await page.click('#wp-submit');
}

export async function logout(page: Page) {
  const cookies = await page.context().cookies();
  const wpCookies = cookies.filter(cookie => 
    cookie.name.startsWith('wordpress_') || 
    cookie.name.startsWith('wp_')
  );
  
  for (const cookie of wpCookies) {
    await page.context().clearCookies({ name: cookie.name });
  }
}

export async function isLoggedIn(page: Page): Promise<boolean> {
  const cookies = await page.context().cookies();
  return cookies.some(cookie => 
    cookie.name.startsWith('wordpress_logged_in_') && 
    cookie.value.length > 0
  );
}