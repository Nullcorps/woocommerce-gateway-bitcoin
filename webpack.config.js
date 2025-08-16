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
		'frontend/blocks/checkout/bh-wp-bitcoin-gateway-blocks-checkout':
			'./src/frontend/blocks/checkout/index.tsx',
		'frontend/bh-wp-bitcoin-gateway':
			'./src/frontend/index.ts',
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
