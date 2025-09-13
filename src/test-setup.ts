/**
 * External dependencies
 */
import '@testing-library/jest-dom';

// Mock global WordPress functions
global.__ = jest.fn( ( text: string ) => text );

// Mock jQuery
global.jQuery = {
	fn: {
		extend: jest.fn(),
	},
	extend: jest.fn(),
} as any;

global.$ = global.jQuery;

// WooCommerce mocks will be handled in individual test files

// Mock global variables that would be set by WordPress/WooCommerce
global.window = Object.create( window );
Object.defineProperty( window, 'bh_wp_bitcoin_gateway_order_details', {
	value: {
		btc_address: 'bc1test123',
		btc_total: '0.00123456',
		order_id: '123',
		btc_amount_received: '0.0',
		status: 'unpaid',
		amount_received: '0.00 BTC',
		order_status_formatted: 'Pending Payment',
		last_checked_time_formatted: 'Never',
	},
	writable: true,
} );

Object.defineProperty( window, 'bh_wp_bitcoin_gateway_ajax_data', {
	value: {
		ajax_url: 'http://localhost/wp-admin/admin-ajax.php',
		nonce: 'test-nonce-123',
	},
	writable: true,
} );

// Mock navigator clipboard
Object.defineProperty( navigator, 'clipboard', {
	value: {
		writeText: jest.fn( () => Promise.resolve() ),
	},
	writable: true,
} );
