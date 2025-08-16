import { Page } from '@playwright/test';
import { loginAsAdminWithRetry } from './login';

export type ThemeType = 'shortcode' | 'blocks';

const THEME_CONFIG = {
  shortcode: {
    name: 'Twenty Twelve',
    slug: 'twentytwelve',
    description: 'Classic theme for shortcode checkout testing'
  },
  blocks: {
    name: 'Twenty Twenty-Five', 
    slug: 'twentytwentyfive',
    description: 'Modern theme for block checkout testing'
  }
} as const;

/**
 * Switch to a theme appropriate for the checkout type
 */
export async function switchToTheme(page: Page, themeType: ThemeType): Promise<void> {
  const theme = THEME_CONFIG[themeType];
  
  // Login as admin if not already logged in (with retry logic)
  await loginAsAdminWithRetry(page);
  
  // Navigate to themes page
  await page.goto('/wp-admin/themes.php');
  
  // Wait for themes to load
  await page.waitForSelector('.theme', { timeout: 10000 });
  
  // Look for the theme by name or slug
  const themeSelector = `.theme[data-slug="${theme.slug}"], .theme:has-text("${theme.name}")`;
  const themeElement = page.locator(themeSelector).first();
  
  // Check if theme is already active
  const isActive = await themeElement.locator('.button[disabled]').count() > 0;
  
  if (!isActive) {
    // Click on the theme to open details
    await themeElement.click();
    
    // Wait for the theme details modal/overlay
    await page.waitForSelector('.theme-overlay, .theme-actions', { timeout: 5000 });
    
    // Click activate button
    const activateButton = page.locator('.activate, .button-primary:has-text("Activate")').first();
    await activateButton.click();
    
    // Wait for activation to complete
    await page.waitForSelector('.notice-success, .updated', { timeout: 10000 });
    
    console.log(`✓ Switched to ${theme.name} theme for ${themeType} checkout testing`);
  } else {
    console.log(`✓ ${theme.name} theme is already active`);
  }
}

/**
 * Switch to shortcode-compatible theme (Twenty Twelve)
 */
export async function switchToShortcodeTheme(page: Page): Promise<void> {
  await switchToTheme(page, 'shortcode');
}

/**
 * Switch to blocks-compatible theme (Twenty Twenty-Five)
 */
export async function switchToBlocksTheme(page: Page): Promise<void> {
  await switchToTheme(page, 'blocks');
}

/**
 * Get the current active theme information
 */
export async function getCurrentTheme(page: Page): Promise<{ name: string; slug: string }> {
  await loginAsAdminWithRetry(page);
  await page.goto('/wp-admin/themes.php');
  
  // Wait for themes to load
  await page.waitForSelector('.theme', { timeout: 10000 });
  
  // Find the active theme
  const activeTheme = page.locator('.theme.active').first();
  
  if (await activeTheme.count() === 0) {
    throw new Error('No active theme found');
  }
  
  const name = await activeTheme.locator('.theme-name').textContent() || 'Unknown';
  const slug = await activeTheme.getAttribute('data-slug') || 'unknown';
  
  return { name: name.trim(), slug };
}

/**
 * Verify that the correct theme is active for the checkout type
 */
export async function verifyThemeForCheckoutType(page: Page, expectedType: ThemeType): Promise<void> {
  const currentTheme = await getCurrentTheme(page);
  const expectedTheme = THEME_CONFIG[expectedType];
  
  if (currentTheme.slug !== expectedTheme.slug) {
    throw new Error(
      `Expected ${expectedTheme.name} (${expectedTheme.slug}) for ${expectedType} checkout, ` +
      `but found ${currentTheme.name} (${currentTheme.slug})`
    );
  }
  
  console.log(`✓ Verified ${currentTheme.name} theme is active for ${expectedType} checkout`);
}