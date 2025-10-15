/**
 * Internal dependencies
 */
import { fetchActionsWithArgs } from '../../helpers/rest/action-scheduler';

const mockResponse = {
	'3124': {
		id: 3124,
		hook: 'bh_wp_bitcoin_gateway_check_unpaid_order',
		status: 'pending',
		args: {
			order_id: 807,
		},
		group: '',
		recurrence: 600,
		scheduled_date: {
			date: '2025-10-14 20:57:23.000000',
			timezone_type: 1,
			timezone: '+00:00',
		},
		schedule: {},
		hook_priority: 10,
	},
	'1234': {
		id: 1234,
		hook: 'bh_wp_bitcoin_gateway_check_unpaid_order',
		status: 'pending',
		args: {
			order_id: 123,
		},
		group: '',
		recurrence: 600,
		scheduled_date: {
			date: '2025-10-13 20:07:20.000000',
			timezone_type: 1,
			timezone: '+00:00',
		},
		schedule: {},
		hook_priority: 10,
	},
};

const testArgs = {
	order_id: 808,
};

jest.mock( '../../helpers/fetch', () => ( {
	debugFetch: jest.fn(),
} ) );

describe( 'fetchActionsWithArgs', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'should return filtered actions when args match', async () => {
		const { debugFetch } = require( '../../helpers/fetch' );
		debugFetch.mockResolvedValue( {
			json: jest.fn().mockResolvedValue( { data: mockResponse } ),
		} );

		const result = await fetchActionsWithArgs(
			'bh_wp_bitcoin_gateway_check_unpaid_order',
			{ order_id: 807 }
		);

		expect( result.length ).toEqual( 1 );
		expect( result.pop()[ 1 ].id ).toEqual( 3124 );
	} );

	test( 'should return empty array when args do not match', async () => {
		const { debugFetch } = require( '../../helpers/fetch' );
		debugFetch.mockResolvedValue( {
			json: jest.fn().mockResolvedValue( { data: mockResponse } ),
		} );

		const result = await fetchActionsWithArgs(
			'bh_wp_bitcoin_gateway_check_unpaid_order',
			testArgs
		);

		expect( result.length ).toEqual( 0 );
	} );

	test( 'should return all actions when no args provided', async () => {
		const { debugFetch } = require( '../../helpers/fetch' );
		debugFetch.mockResolvedValue( {
			json: jest.fn().mockResolvedValue( { data: mockResponse } ),
		} );

		const result = await fetchActionsWithArgs(
			'bh_wp_bitcoin_gateway_check_unpaid_order'
		);

		expect( result.length ).toEqual( 2 );
	} );
} );
