import { Page } from '@playwright/test';
import { testConfig } from '../config/test-config';

export type CheckoutType = 'blocks' | 'shortcode';

export async function detectCheckoutType(page: Page): Promise<CheckoutType> {
  // Wait a moment for page to fully load
  await page.waitForTimeout(1000);

  // Check for blocks checkout indicators
  const blocksCheckoutElements = [
    '.wc-block-checkout',
    '.wp-block-woocommerce-checkout',
    '.wc-block-components-checkout-place-order-button'
  ];

  // Check for shortcode checkout indicators
  const shortcodeCheckoutElements = [
    '.woocommerce-checkout',
    '#place_order',
    'form[name="checkout"]'
  ];

  // Test for blocks checkout
  for (const selector of blocksCheckoutElements) {
    if (await page.locator(selector).count() > 0) {
      return 'blocks';
    }
  }

  // Test for shortcode checkout
  for (const selector of shortcodeCheckoutElements) {
    if (await page.locator(selector).count() > 0) {
      return 'shortcode';
    }
  }

  // If we can't detect, try to infer from URL
  const url = page.url();
  if (url.includes('blocks-checkout') || url.includes('block-checkout')) {
    return 'blocks';
  }

  // Check page content for block-specific classes
  const bodyClasses = await page.getAttribute('body', 'class') || '';
  if (bodyClasses.includes('wc-block') || bodyClasses.includes('wp-block')) {
    return 'blocks';
  }

  // Default to shortcode if uncertain
  return 'shortcode';
}

export async function isBlocksCheckout(page: Page): Promise<boolean> {
  return (await detectCheckoutType(page)) === 'blocks';
}

export async function isShortcodeCheckout(page: Page): Promise<boolean> {
  return (await detectCheckoutType(page)) === 'shortcode';
}

export async function fillBilling(page: Page): Promise<void> {
  const billing = testConfig.addresses.customer.billing;
  const checkoutType = await detectCheckoutType(page);

  if (checkoutType === 'blocks') {
    // Blocks checkout field selectors
    await page.fill('#email', billing.email);
    await page.fill('#billing-first_name', billing.firstname);
    await page.fill('#billing-last_name', billing.lastname);
    // await page.fill('#billing-country', billing.country);

    let billingAddress = await page.locator('#billing-fields');
    await billingAddress.getByLabel('Country/Region').selectOption(billing.country);
    // await billingAddress.getByLabel('Country/Region').click();
    // await billingAddress.getByLabel('Country/Region').fill('united');
    // await billingAddress.getByLabel('United States (US)', { exact: true }).click();
    await page.waitForLoadState( 'networkidle' );

    await page.fill('#billing-address_1', billing.addressfirstline);
    await page.fill('#billing-address_2', billing.addresssecondline);
    await page.fill('#billing-city', billing.city);

    // await page.fill('#billing-state', billing.state);
    await billingAddress.getByLabel('State').selectOption(billing.state);

    await page.fill('#billing-postcode', billing.postcode);
  } else {
    // Shortcode checkout field selectors
    await page.fill('#billing_first_name', billing.firstname);
    await page.fill('#billing_last_name', billing.lastname);
    await page.fill('#billing_company', billing.company);
    await page.selectOption('#billing_country', 'US');
    await page.fill('#billing_address_1', billing.addressfirstline);
    await page.fill('#billing_address_2', billing.addresssecondline);
    await page.fill('#billing_city', billing.city);
    await page.selectOption('#billing_state', billing.state);
    await page.fill('#billing_postcode', billing.postcode);
    await page.fill('#billing_phone', billing.phone);
    await page.fill('#billing_email', billing.email);
  }

  // Wait for form to update
  await page.waitForTimeout(2000);
}