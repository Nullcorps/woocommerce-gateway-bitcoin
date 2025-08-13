import { test, expect } from '@playwright/test';
import { configureBitcoinXpub } from '../helpers/configure-bitcoin-xpub';
import { createSimpleProduct } from '../helpers/create-simple-product';
import { placeBitcoinOrder } from '../helpers/place-bitcoin-order';

test.describe('Place orders', () => {
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await configureBitcoinXpub(page);
    await createSimpleProduct(page);
    await page.close();
  });

  test('should successfully place order and show payment details', async ({ page }) => {
    await placeBitcoinOrder(page);
    await expect(page.locator('text=Exchange rate at time of order')).toBeVisible();
  });
});