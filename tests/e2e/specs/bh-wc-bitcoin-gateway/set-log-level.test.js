const {
	merchant,
	settingsPageSaveChanges,
} = require( '@woocommerce/e2e-utils' );

describe( 'Set log level', () => {
	it( 'should respect the log level that is saved on the gateway settings page', async () => {
		await merchant.login();

		await merchant.openSettings( 'checkout', 'bitcoin_gateway' );

		await page.select( '#woocommerce_bitcoin_gateway_log_level', 'notice' );

		await settingsPageSaveChanges();

		await page.goto(
			'http://localhost:8084/wp-admin/admin.php?page=bh-wc-bitcoin-gateway-logs',
			{
				waitUntil: 'networkidle0',
			}
		);

		// Previous: "Current log level: Debug".
		// Default: "Current log level: Info".
		// Desired: "Current log level: Notice".
		await expect( page ).toMatch( 'Current log level: Notice' );
	} );
} );
