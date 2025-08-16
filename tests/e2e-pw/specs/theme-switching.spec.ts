import { test, expect } from '@playwright/test';
import { 
  switchToShortcodeTheme, 
  switchToBlocksTheme, 
  getCurrentTheme, 
  verifyThemeForCheckoutType 
} from '../helpers/theme-switcher';

test.describe('Theme Switching for Checkout Types', () => {
  test('should switch to Twenty Twelve for shortcode checkout', async ({ page }) => {
    await switchToShortcodeTheme(page);
    
    const currentTheme = await getCurrentTheme(page);
    expect(currentTheme.slug).toBe('twentytwelve');
    expect(currentTheme.name).toContain('Twenty Twelve');
    
    // Verify theme validation works
    await verifyThemeForCheckoutType(page, 'shortcode');
  });

  test('should switch to Twenty Twenty-Five for blocks checkout', async ({ page }) => {
    await switchToBlocksTheme(page);
    
    const currentTheme = await getCurrentTheme(page);
    expect(currentTheme.slug).toBe('twentytwentyfive');
    expect(currentTheme.name).toContain('Twenty Twenty-Five');
    
    // Verify theme validation works
    await verifyThemeForCheckoutType(page, 'blocks');
  });

  test('should be able to switch between themes multiple times', async ({ page }) => {
    // Switch to shortcode theme
    await switchToShortcodeTheme(page);
    let currentTheme = await getCurrentTheme(page);
    expect(currentTheme.slug).toBe('twentytwelve');
    
    // Switch to blocks theme
    await switchToBlocksTheme(page);
    currentTheme = await getCurrentTheme(page);
    expect(currentTheme.slug).toBe('twentytwentyfive');
    
    // Switch back to shortcode theme
    await switchToShortcodeTheme(page);
    currentTheme = await getCurrentTheme(page);
    expect(currentTheme.slug).toBe('twentytwelve');
  });

  test('should handle theme already being active', async ({ page }) => {
    // Switch to shortcode theme
    await switchToShortcodeTheme(page);
    
    // Switch to the same theme again (should not fail)
    await switchToShortcodeTheme(page);
    
    const currentTheme = await getCurrentTheme(page);
    expect(currentTheme.slug).toBe('twentytwelve');
  });
});