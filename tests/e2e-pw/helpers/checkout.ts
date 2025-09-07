import { Page } from '@playwright/test';
import { testConfig } from '../config/test-config';
import config from "../../../playwright.config";
import * as fs from 'fs';
import * as path from 'path';

export type CheckoutType = 'blocks' | 'shortcode';

// returns json object of settings
async function getSettings(): Promise<object> {
  const baseURL: string = config.use.baseURL;
  var fullUrl = `${baseURL}/wp-json/wp/v2/settings`;

  const response: Response = await fetch(fullUrl);

  return await response.json();
}

async function getSetting(name: String): Promise<Response> {
  const settings = await getSettings();

  return settings[name];
}

async function getCheckoutPostId(): Promise<number> {
  // woocommerce_checkout_page_id
  const postId = await getSetting('woocommerce_checkout_page_id');
  return parseInt(postId);
}

async function setPageContent(postId: number, postContent: string) {

  const baseURL: string = config.use.baseURL;
  const fullUrl = baseURL + '/wp-json/wp/v2/pages/' + postId;
  const response = await fetch(fullUrl, {
    method: "POST",
    body: JSON.stringify( {
      "content": postContent
    }),
    headers:{
      "Content-Type": "application/json",
    }
  });
}

async function getPostContentRendered(postType: string, postId: number): Promise<string> {

  const baseURL: string = config.use.baseURL;
  const fullUrl = baseURL + '/wp-json/wp/v2/'+postType+'s/' + postId;

  const response: Response = await fetch(fullUrl);

  const result = await response.json();

  return result.content.rendered;
}

async function getCheckoutPageContent(): Promise<string> {
  const pageId = await getCheckoutPostId();
  return await getPostContentRendered('page', pageId);
}


async function setCheckoutPageContent(postContent:string) {
  const page_id = await getCheckoutPostId();
  await setPageContent(page_id, postContent);
}

export async function useBlocksCheckout() {
  const contentPath = path.join(__dirname, '../setup/blocks-checkout-post-content.txt');
  const postContent = fs.readFileSync(contentPath, 'utf8');
  await setCheckoutPageContent(postContent);
}

export async function useShortcodeCheckout() {
  const postContent = '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->';
  await setCheckoutPageContent(postContent);
}

export async function detectCheckoutType(page: Page): Promise<CheckoutType> {

  const postContent = await getCheckoutPageContent();

  // Check for blocks checkout indicators
  const blocksCheckoutStrings = [
    'wc-block-checkout',
    'wp-block-woocommerce-checkout',
    'wc-block-components-checkout-place-order-button'
  ];

  // Test for blocks checkout
  for (const htmlString of blocksCheckoutStrings) {
    if ( postContent.includes(htmlString) ) {
      return 'blocks';
    }
  }

  // Check for shortcode checkout indicators
  const shortcodeCheckoutElements = [
    '[woocommerce_checkout]',
    '.woocommerce-checkout',
    '#place_order',
    'form[name="checkout"]'
  ];

  // Test for shortcode checkout
  for (const htmlString of shortcodeCheckoutElements) {
    if ( postContent.includes(htmlString) ) {
      return 'shortcode';
    }
  }

  // TODO: Maybe throw error if neither detected?
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


    // await page.waitForLoadState( 'networkidle' );

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
    if(await page.isVisible('#billing_company')){
      await page.fill('#billing_company', billing.company);
    }
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
  // await page.waitForTimeout(2000);
  await page.waitForLoadState('networkidle');
}