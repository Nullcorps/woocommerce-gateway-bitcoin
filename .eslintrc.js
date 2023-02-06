module.exports = {
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:import/recommended',
		'plugin:import/typescript',
	],
	ignorePatterns: [
		'**/*.min.js',
		'dist-archive',
		'node_modules',
		'scratch',
		'src',
		'templates',
		'vendor',
		'vendor-prefixed',
		'wordpress',
		'wp-content',
	],
	rules: {
		// You can use the 'rules' key to override specific settings in the WooCommerce plugin
		'@wordpress/i18n-translator-comments': 'warn',
		'@wordpress/valid-sprintf': 'warn',
		'jsdoc/check-tag-names': [
			'error',
			{ definedTags: [ 'jest-environment' ] },
		],
	},
};
