/**
 * External dependencies
 */
import { Response } from 'node/globals';

/**
 * Internal dependencies
 */
import config from '../../../playwright.config';

export async function debugFetch(
	url: string,
	options?: object,
	withBaseUrl: boolean = true
): Promise< Response > {
	url = url.replace( /\/+$/, '' ); // remove trailing slashes
	let fullUrl = null;
	if ( withBaseUrl ) {
		const baseURL: string = config.use.baseURL;
		fullUrl = `${ baseURL }/${ url }`;
	} else {
		fullUrl = url;
	}
	if ( fullUrl.indexOf( '?' ) > -1 ) {
		fullUrl = fullUrl + '&XDEBUG_SESSION=PHPSTORM';
	} else {
		fullUrl = fullUrl + '?XDEBUG_SESSION=PHPSTORM';
	}
	let response: Response = null;
	if ( options ) {
		response = await fetch( fullUrl, options );
	} else {
		response = await fetch( fullUrl );
	}
	const method =
		options && ( options as any ).method
			? ( options as any ).method
			: 'GET';
	console.log( `${ method.toUpperCase() }ing URL: ${ fullUrl }` );
	const body = await response.clone().text();
	console.log( `Response: ${ body }` );
	return response;
}
