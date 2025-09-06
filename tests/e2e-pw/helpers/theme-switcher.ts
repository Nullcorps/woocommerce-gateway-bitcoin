import config from "../../../playwright.config.ts"

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
export async function switchToTheme(themeType: ThemeType ): Promise<void> {
  console.log('switchToTheme type: ' + themeType);

  const theme = THEME_CONFIG[themeType];

  console.log('switchToTheme slug: ' + theme.slug);

  const baseURL: string = config.use.baseURL;
  const response = await fetch(baseURL + '/wp-json/e2e-test-helper/v1/activate', {
    method: "post",
    body: JSON.stringify( {
      "theme_slug": theme.slug
    }),
  });

  const text = await response.text();
  console.log('response: ' + text);
}

/**
 * Switch to shortcode-compatible theme (Twenty Twelve)
 */
export async function switchToShortcodeTheme(): Promise<void> {
  console.log('switchToShortcodeTheme');
  await switchToTheme('shortcode');
}

/**
 * Switch to blocks-compatible theme (Twenty Twenty-Five)
 */
export async function switchToBlocksTheme(): Promise<void> {
  console.log('switchToBlocksTheme');
  await switchToTheme('blocks');
}

/**
 * Get the current active theme information
 */
export async function getCurrentTheme(): Promise<{ slug: string }> {
  console.log('getCurrentTheme');

  // const baseURL: string = config?.use?.baseURL!;
  const baseURL: string = config.use.baseURL;

  const url = baseURL + '/wp-json/e2e-test-helper/v1/active_theme';

  console.log("url: " + url);

  const response = await fetch(url);

  const text = await response.text();
  console.log('response: ' + text);

  const json = JSON.parse(text);
  // const json = await response.json();
  return {slug: json.slug};
}

/**
 * Verify that the correct theme is active for the checkout type
 */
export async function verifyTheme(expectedType: ThemeType): Promise<void> {
  console.log('verifyTheme');

  const currentTheme = await getCurrentTheme();

  const expectedTheme = THEME_CONFIG[expectedType];
  
  if (currentTheme.slug !== expectedTheme.slug) {
    console.log(`Expected ${expectedTheme.name} (${expectedTheme.slug}) for ${expectedType} theme, ` +
      `but found ${currentTheme.slug}`);
    throw new Error(
      `Expected ${expectedTheme.name} (${expectedTheme.slug}) for ${expectedType} theme, ` +
      `but found ${currentTheme.slug}`
    );
  }
  
  console.log(`✓ Verified ${currentTheme.slug} theme is active for ${expectedType} theme`);
}