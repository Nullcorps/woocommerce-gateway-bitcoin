/**
 * Internal dependencies
 */
import config from '../../../../playwright.config';

export async function setPageContent( postId: number, postContent: string ) {
	const baseURL: string = config.use.baseURL;
	const fullUrl = baseURL + '/wp-json/wp/v2/pages/' + postId;
	const response = await fetch( fullUrl, {
		method: 'POST',
		body: JSON.stringify( {
			content: postContent,
		} ),
		headers: {
			'Content-Type': 'application/json',
		},
	} );
}

export async function getPostContentRendered(
	postType: string,
	postId: number
): Promise< string > {
	const baseURL: string = config.use.baseURL;
	const fullUrl = baseURL + '/wp-json/wp/v2/' + postType + 's/' + postId;

	const response: Response = await fetch( fullUrl );

	const result = await response.json();

	return result.content.rendered;
}
