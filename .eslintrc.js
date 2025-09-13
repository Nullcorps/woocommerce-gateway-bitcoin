module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: {
		browser: true,
		es6: true,
		node: true,
		jest: true,
	},
	globals: {
		wp: 'readonly',
		wc: 'readonly',
		jQuery: 'readonly',
		$: 'readonly',
	},
	parser: '@babel/eslint-parser',
	parserOptions: {
		ecmaVersion: 2020,
		sourceType: 'module',
		ecmaFeatures: {
			jsx: true,
		},
		requireConfigFile: false,
		babelOptions: {
			presets: [ '@wordpress/babel-preset-default' ],
		},
	},
	overrides: [
		{
			files: [ '**/*.ts', '**/*.tsx' ],
			parser: '@typescript-eslint/parser',
			extends: [ 'plugin:@typescript-eslint/recommended' ],
			rules: {
				'@typescript-eslint/no-unused-vars': [
					'error',
					{
						argsIgnorePattern: '^_',
						varsIgnorePattern: '^_',
					},
				],
				'@typescript-eslint/explicit-function-return-type': 'off',
				'@typescript-eslint/explicit-module-boundary-types': 'off',
				'@typescript-eslint/no-explicit-any': 'warn',
			},
		},
		{
			files: [ '**/*.test.js', '**/*.test.ts', '**/*.test.tsx' ],
			env: {
				jest: true,
			},
			extends: [ 'plugin:jest/recommended' ],
		},
	],
	ignorePatterns: [
		'**/*.min.js',
		'**/*.asset.php',
		'dist-archive',
		'node_modules',
		'scratch',
		'includes',
		'templates',
		'vendor',
		'vendor-prefixed',
		'wordpress',
		'wp-content',
		'assets/js/**/*.min.js',
		'build/',
	],
	rules: {
		// WordPress specific rules
		'@wordpress/i18n-translator-comments': 'warn',
		'@wordpress/valid-sprintf': 'warn',
		'@wordpress/no-unsafe-wp-apis': 'warn',
		'@wordpress/dependency-group': 'error',

		// JSDoc rules
		'jsdoc/check-tag-names': [
			'error',
			{ definedTags: [ 'jest-environment' ] },
		],
		'jsdoc/require-param-description': 'off',
		'jsdoc/require-returns-description': 'off',

		// General code quality rules
		'no-console': 'warn',
		'no-debugger': 'error',
		'no-unused-vars': [
			'error',
			{
				argsIgnorePattern: '^_',
				varsIgnorePattern: '^_',
			},
		],

		// Import rules
		'import/order': [
			'error',
			{
				groups: [
					'builtin',
					'external',
					'internal',
					'parent',
					'sibling',
					'index',
				],
				'newlines-between': 'always',
				alphabetize: {
					order: 'asc',
					caseInsensitive: true,
				},
			},
		],
		'import/no-unresolved': 'off', // TypeScript handles this

		// React specific rules for JSX files
		'react/prop-types': 'off', // Using TypeScript for prop validation
		'react/react-in-jsx-scope': 'off', // Not needed with new JSX transform
	},
	settings: {
		react: {
			version: 'detect',
		},
	},
};
