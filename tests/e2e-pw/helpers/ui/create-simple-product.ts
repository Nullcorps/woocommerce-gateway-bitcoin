/**
 * External dependencies
 */
import { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { testConfig } from '../../config/test-config';

import { loginAsAdmin, logout } from './login';

export async function createSimpleProduct( page: Page ) {
	// Login as admin
	await loginAsAdmin( page );

	// Navigate to products page
	await page.goto( '/wp-admin/edit.php?post_type=product' );

	// Check if simple product already exists
	const existingProduct = await page
		.locator( `text="${ testConfig.products.simple.name }"` )
		.first();
	const productExists = ( await existingProduct.count() ) > 0;

	if ( ! productExists ) {
		// Add new product
		await page.click( '.page-title-action' );

		// Fill product details
		await page.fill( '#title', testConfig.products.simple.name );

		// Set regular price
		await page.fill( '#_regular_price', '20.00' );

		// Publish product
		await page.click( '#publish' );
		await page.waitForSelector( '.notice-success' );
	}

	// Logout
	await logout( page );
}
