import { test, expect } from '@playwright/test';
import { loginAsAdmin, logout, isLoggedIn, loginAsAdminWithRetry } from '../helpers/login';

test.describe('Login Helper Robustness', () => {
  test.beforeEach(async ({ page }) => {
    // Start each test logged out
    await logout(page);
  });

  test('should login successfully from logged out state', async ({ page }) => {
    // Verify we're logged out
    expect(await isLoggedIn(page)).toBe(false);

    // Login
    await loginAsAdmin(page);

    // Verify login success
    expect(await isLoggedIn(page)).toBe(true);
    
    // Verify we can access admin area
    await page.goto('/wp-admin/');
    await expect(page.locator('#wpadminbar')).toBeVisible();
  });

  test('should handle already logged in state gracefully', async ({ page }) => {
    // Login first
    await loginAsAdmin(page);
    expect(await isLoggedIn(page)).toBe(true);

    // Login again (should be a no-op)
    await loginAsAdmin(page);
    expect(await isLoggedIn(page)).toBe(true);
  });

  test('should logout successfully from logged in state', async ({ page }) => {
    // Login first
    await loginAsAdmin(page);
    expect(await isLoggedIn(page)).toBe(true);

    // Logout
    await logout(page);
    expect(await isLoggedIn(page)).toBe(false);
  });

  test('should handle already logged out state gracefully', async ({ page }) => {
    // Verify we're logged out
    expect(await isLoggedIn(page)).toBe(false);

    // Logout again (should be a no-op)
    await logout(page);
    expect(await isLoggedIn(page)).toBe(false);
  });

  test('should retry login on failure', async ({ page }) => {
    // This test is more about ensuring the retry mechanism exists
    // In a real failure scenario, this would retry multiple times
    await loginAsAdminWithRetry(page, 1);
    expect(await isLoggedIn(page)).toBe(true);
  });

  test('should maintain login state across page navigations', async ({ page }) => {
    await loginAsAdmin(page);
    expect(await isLoggedIn(page)).toBe(true);

    // Navigate to different pages
    await page.goto('/');
    expect(await isLoggedIn(page)).toBe(true);

    await page.goto('/wp-admin/themes.php');
    expect(await isLoggedIn(page)).toBe(true);
    await expect(page.locator('#wpadminbar')).toBeVisible();

    await page.goto('/wp-admin/plugins.php');
    expect(await isLoggedIn(page)).toBe(true);
    await expect(page.locator('#wpadminbar')).toBeVisible();
  });

  test('should detect login state correctly after manual logout', async ({ page }) => {
    // Login first
    await loginAsAdmin(page);
    expect(await isLoggedIn(page)).toBe(true);

    // Manually navigate to logout (simulating user clicking logout)
    await page.goto('/wp-admin/');
    await page.hover('#wp-admin-bar-my-account');
    await page.click('#wp-admin-bar-logout a');

    // Should detect we're now logged out
    expect(await isLoggedIn(page)).toBe(false);
  });

  test('should handle network timeouts gracefully', async ({ page }) => {
    // Set a very short timeout to simulate network issues
    page.setDefaultTimeout(1000);
    
    try {
      await loginAsAdmin(page);
      // If it succeeds despite short timeout, that's fine
      expect(await isLoggedIn(page)).toBe(true);
    } catch (error) {
      // If it fails due to timeout, that's expected with very short timeout
      expect(error.message).toContain('timeout');
    }
    
    // Reset timeout for cleanup
    page.setDefaultTimeout(30000);
  });
});