const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
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
		'frontend/woocommerce/blocks/order-confirmation/exchange-rate/exchange-rate':
			'./src/frontend/woocommerce/blocks/order-confirmation/exchange-rate/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-address/payment-address':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-address/index.tsx',
		'frontend/woocommerce/blocks/order-confirmation/payment-status/payment-status':
			'./src/frontend/woocommerce/blocks/order-confirmation/payment-status/index.tsx',
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
		new WooCommerceDependencyExtractionWebpackPlugin( {
			requestToExternal,
			requestToHandle,
		} ),
	],
};

module.exports = myConfig;
