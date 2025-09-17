const defaultConfig                                = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const path = require( 'path' );

const wcDepMap = {
	'@woocommerce/blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
	'@woocommerce/settings': [ 'wc', 'wcSettings' ],
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings': 'wc-settings',
};

const requestToExternal = ( request ) => {
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = ( request ) => {
	if ( wcHandleMap[ request ] ) {
		return wcHandleMap[ request ];
	}
};

// Export configuration.
const myConfig = {
	...defaultConfig,
	entry: {
		'frontend/woocommerce/blocks/checkout/gateway/gateway':
			'./src/frontend/woocommerce/blocks/checkout/gateway/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-admin':
			'./src/frontend/woocommerce/blocks/order-confirmation/exchange-rate/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate-block':
			'./src/frontend/woocommerce/blocks/order-confirmation/exchange-rate/view.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-address/payment-address-admin':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-address/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-address/payment-address-block':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-address/view.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-status/payment-status-admin':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-status/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-status/payment-status-block':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-status/view.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-total/payment-total-admin':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-total/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-total/payment-total-block':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-total/view.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-amount-received/payment-amount-received-admin':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-amount-received/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-amount-received/payment-amount-received-block':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-amount-received/view.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-last-checked/payment-last-checked-admin':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-last-checked/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-last-checked/payment-last-checked-block':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-last-checked/view.tsx',
		'frontend/woocommerce/blocks/order-confirmation/bitcoin-order-confirmation-group/bitcoin-order-confirmation-group':
			'./src/frontend/woocommerce/blocks/order-confirmation/bitcoin-order-confirmation-group/index.tsx',
		'frontend/woocommerce/shortcode/thank-you/thank-you':
			'./src/frontend/woocommerce/shortcode/thank-you/index.ts'
	},
	output: {
		path: path.resolve( __dirname, 'assets/js' ),
		filename: '[name].min.js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
			plugin.constructor.name !==
					'DependencyExtractionWebpackPlugin' &&
				plugin.constructor.name !== 'CleanWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(
			{
					requestToExternal,
					requestToHandle,
			}
		),
	],
};

module.exports = myConfig;
