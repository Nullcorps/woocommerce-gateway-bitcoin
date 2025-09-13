/**
 * External dependencies
 */
import { Page, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { testConfig } from '../config/test-config';

export async function loginAsAdmin( page: Page ): Promise< void > {
	// Check if already logged in
	if ( await isLoggedIn( page ) ) {
		console.log( '✓ Already logged in as admin' );
		return;
	}

	// Navigate to login page
	await page.goto( '/?login_as_user=admin' );

	// Check if login was successful
	await verifyLoginSuccess( page );
}

async function verifyLoginSuccess( page: Page ): Promise< void > {
	// #wp-admin-bar-my-account
	if ( await page.isVisible( '#wp-admin-bar-my-account' ) ) {
		// console.log('✓ Successfully logged in as admin');
		return;
	}

	const currentUrl = page.url();

	// If we're still on the login page, check for errors
	if ( currentUrl.includes( 'wp-login.php' ) ) {
		const errorMessage = page.locator( '#login_error' );
		if ( ( await errorMessage.count() ) > 0 ) {
			const errorText = await errorMessage.textContent();
			throw new Error( `Login failed: ${ errorText }` );
		}

		// Sometimes login succeeds but doesn't redirect - check for success indicators
		const successIndicators = [
			page.locator( '#login .message' ),
			page.locator( '.login .success' ),
		];

		let hasSuccessMessage = false;
		for ( const indicator of successIndicators ) {
			if ( ( await indicator.count() ) > 0 ) {
				hasSuccessMessage = true;
				break;
			}
		}

		if ( ! hasSuccessMessage ) {
			throw new Error(
				'Login appears to have failed - still on login page with no success message'
			);
		}
	}

	// Wait a moment for any redirects to complete
	await page.waitForTimeout( 1000 );

	// Verify we're actually logged in by checking for admin bar or dashboard elements
	const adminIndicators = [
		'#wpadminbar',
		'#adminmenumain',
		'.wp-admin',
		'#dashboard-widgets',
	];

	let isInAdmin = false;
	for ( const indicator of adminIndicators ) {
		const count = await page.locator( indicator ).count();
		if ( count > 0 ) {
			isInAdmin = true;
			break;
		}
	}

	// If not in admin area, navigate to dashboard to confirm login
	if ( ! isInAdmin ) {
		await page.goto( '/wp-admin/' );

		try {
			await page.waitForSelector( '#wpadminbar, #adminmenumain', {
				timeout: 10000,
			} );
		} catch ( error ) {
			// Check if we got redirected back to login
			const finalUrl = page.url();
			if ( finalUrl.includes( 'wp-login.php' ) ) {
				throw new Error(
					'Navigation to wp-admin redirected to login page - login failed'
				);
			}
			throw error;
		}
	}

	// Final verification using cookie check
	const loginStatus = await isLoggedIn( page );

	if ( ! loginStatus ) {
		throw new Error(
			'Login verification failed - login status check returned false'
		);
	}

	console.log( '✓ Successfully logged in as admin' );
}

export async function logout( page: Page ): Promise< void > {
	// Check if already logged out
	if ( ! ( await isLoggedIn( page ) ) ) {
		console.log( '✓ Already logged out' );
		return;
	}
	await logoutViaCookies( page );

	// try {
	//   // Try the UI logout method first (more realistic)
	//   await logoutViaUI(page);
	//   console.log('✓ Logged out via UI');
	// } catch (error) {
	//   console.warn('UI logout failed, falling back to cookie clearing:', error);
	//   // Fallback to cookie clearing method
	//   await logoutViaCookies(page);
	//   console.log('✓ Logged out via cookie clearing');
	// }

	// Verify logout was successful
	if ( await isLoggedIn( page ) ) {
		throw new Error( 'Logout failed - still appears to be logged in' );
	}
}

async function logoutViaUI( page: Page ): Promise< void > {
	// Go to a WordPress admin page to ensure we have the admin bar
	await page.goto( '/wp-admin/' );

	// Wait for admin bar to be visible
	await page.waitForSelector( '#wpadminbar', { timeout: 10000 } );

	// Hover over the user account menu
	await page.hover( '#wp-admin-bar-my-account' );

	// Wait for logout link to be visible and click it
	const logoutLink = page.locator( '#wp-admin-bar-logout a' );
	await logoutLink.waitFor( { state: 'visible', timeout: 5000 } );

	await Promise.all( [
		page.waitForNavigation( { timeout: 10000 } ),
		logoutLink.click(),
	] );
}

async function logoutViaCookies( page: Page ): Promise< void > {
	const cookies = await page.context().cookies();
	const wpCookies = cookies.filter(
		( cookie ) =>
			cookie.name.startsWith( 'wordpress_' ) ||
			cookie.name.startsWith( 'wp_' ) ||
			cookie.name.includes( 'login' ) ||
			cookie.name.includes( 'session' )
	);

	for ( const cookie of wpCookies ) {
		await page.context().clearCookies( { name: cookie.name } );
	}

	// Also clear all cookies from WordPress domain to be extra sure
	await page.context().clearCookies();
}

export async function isLoggedIn( page: Page ): Promise< boolean > {
	try {
		// Method 1: Check for WordPress login cookies
		const cookies = await page.context().cookies();
		const hasLoginCookie = cookies.some(
			( cookie ) =>
				( cookie.name.startsWith( 'wordpress_logged_in_' ) ||
					cookie.name.startsWith( 'wp_' ) ||
					cookie.name.includes( 'logged_in' ) ) &&
				cookie.value.length > 0
		);

		// Method 2: Check if we can access wp-admin without redirect
		try {
			const adminResponse = await page.request.get( '/wp-admin/' );

			// If we get redirected to login page, we're not logged in
			const finalUrl = adminResponse.url();
			if ( finalUrl.includes( 'wp-login.php' ) ) {
				return false;
			}

			// If we get a 200 response and we're not on login page, we're logged in
			if ( adminResponse.status() === 200 ) {
				return true;
			}
		} catch ( error ) {
			console.warn( 'Error checking admin access:', error );
		}

		// Method 3: Try to access a admin-only endpoint
		try {
			const response = await page.request.get(
				'/wp-admin/admin-ajax.php',
				{
					data: { action: 'heartbeat' },
				}
			);

			// Check response for login indicators
			if ( response.status() === 200 ) {
				const responseText = await response.text();
				// WordPress heartbeat returns different responses for logged in vs logged out users
				if (
					responseText.includes( '"nonces_expired"' ) ||
					responseText.includes( '"logged_in":false' )
				) {
					return false;
				}
				return hasLoginCookie; // Only trust if we also have cookies
			}
		} catch ( error ) {
			console.warn( 'Error checking heartbeat:', error );
		}

		return false;
	} catch ( error ) {
		// If there's any error, assume not logged in
		console.warn( 'Error checking login status:', error );
		return false;
	}
}

/**
 * Enhanced login function with retry logic for flaky connections
 * @param page
 * @param maxRetries
 */
export async function loginAsAdminWithRetry(
	page: Page,
	maxRetries: number = 3
): Promise< void > {
	for ( let attempt = 1; attempt <= maxRetries; attempt++ ) {
		try {
			await loginAsAdmin( page );
			return; // Success, exit retry loop
		} catch ( error ) {
			console.warn( `Login attempt ${ attempt } failed:`, error );

			if ( attempt === maxRetries ) {
				throw new Error(
					`Failed to login after ${ maxRetries } attempts. Last error: ${ error }`
				);
			}

			// Wait before retrying
			await page.waitForTimeout( 2000 * attempt );

			// Clear any existing session before retry
			await logoutViaCookies( page );
		}
	}
}
