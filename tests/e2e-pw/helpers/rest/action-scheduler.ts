/**
 * External dependencies
 */
// import { Response } from 'node/globals';

/**
 * Internal dependencies
 */
import { debugFetch } from '../fetch';

// GET /wp-json/e2e-test-helper/v1/action_scheduler/search?hook={$hook}

export async function fetchActions(
	hook: string,
	future: boolean = true
): Promise< Object > {
	let path = `/wp-json/e2e-test-helper/v1/action_scheduler/search?hook=${ hook }`;
	if ( future ) {
		path += `&date_compare=>=&date=${ new Date().toISOString() }`;
	}
	const response = await debugFetch( path );
	const json = await response.json();
	return json.data;
}
export async function fetchActionsWithArgs(
	hook: string,
	args?: object
): Promise< Object > {
	const results = await fetchActions( hook );

	if ( args ) {
		// filter results to only those that match args
		// results.data.args
	} else {
		return results.data;
	}
}

async function deleteActions( hook: string ): Promise< void > {
	const actions = await fetchActions( hook );

	const numberOfActions = Object.keys( actions ).length;

	if ( numberOfActions === 0 ) {
		console.log( `No actions found for hook: ${ hook }` );
		return;
	}
	console.log(
		`Deleting ${ numberOfActions } actions found for hook: ${ hook }`
	);

	Object.keys( actions ).forEach( async ( key ) => {
		const path = `/wp-json/e2e-test-helper/v1/action_scheduler/${ key }`;
		const response = await debugFetch( path, {
			method: 'DELETE',
			headers: {
				'Content-Type': 'application/json',
			},
		} );

		const body = await response.text();

		console.log( body );
	} );
}

// bh_wp_bitcoin_gateway_check_unpaid_order
export async function hasUnpaidOrderActions(): Promise< boolean > {
	const actions = await fetchActions(
		'bh_wp_bitcoin_gateway_check_unpaid_order'
	);
	return actions.count > 0;
}
export async function deleteUnpaidOrderActions(): Promise< number > {
	await deleteActions( 'bh_wp_bitcoin_gateway_check_unpaid_order' );

	return 1;
}
