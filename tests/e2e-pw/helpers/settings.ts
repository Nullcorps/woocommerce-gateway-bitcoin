// returns json object of settings
/**
 * Internal dependencies
 */
import config from '../../../playwright.config';

async function getSettings(): Promise< object > {
	const baseURL: string = config.use.baseURL;
	const fullUrl = `${ baseURL }/wp-json/wp/v2/settings`;

	const response: Response = await fetch( fullUrl );

	return await response.json();
}

export async function getSetting( name: string ): Promise< any > {
	const settings = await getSettings();

	return settings[ name ];
}
