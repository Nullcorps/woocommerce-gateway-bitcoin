const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if (wcDepMap[request]) {
		return wcDepMap[request];
	}
};

const requestToHandle = (request) => {
	if (wcHandleMap[request]) {
		return wcHandleMap[request];
	}
};

// Export configuration.
const myConfig = {
	...defaultConfig,
	entry: {
		'frontend/blocks/checkout/bh-wc-bitcoin-gateway-blocks-checkout': '/assets/js/frontend/blocks/checkout/bh-wc-bitcoin-gateway-blocks-checkout.js',
		'frontend/bh-wc-bitcoin-gateway': '/assets/js/frontend/bh-wc-bitcoin-gateway.js',
	},
	output: {
		path: path.resolve( __dirname, 'assets/js' ),
		filename: '[name].min.js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
			&& plugin.constructor.name !== 'CleanWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		})
	]
};

module.exports = myConfig;