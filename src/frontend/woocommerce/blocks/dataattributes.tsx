/**
 * Internal dependencies
 */
import {
	getClassNameFromNamespacedName,
	getLocalNameFromNamespacedName,
} from './names';

export function getAttributes(
	element: Element,
	attributes: string[]
): {
	[ key: string ]: string | boolean | number;
} {
	const attributeValues: { [ key: string ]: string | boolean | number } = {};

	// Cast numbers and bools to their proper types.
	Object.entries( attributes ).forEach( ( [ name, _v ] ) => {
		const dataAttr = 'data-attribute-' + name.toLowerCase();
		const attrValue = element.getAttribute( dataAttr );
		if ( attrValue ) {
			if ( attributes[ name ].type === 'boolean' ) {
				attributeValues[ name ] = attrValue === 'true';
			} else if ( attributes[ name ].type === 'numeric' ) {
				attributeValues[ name ] = parseFloat( attrValue );
			} else {
				attributeValues[ name ] = attrValue;
			}
		} else {
			attributeValues[ name ] = attributes[ name ].default;
		}
	} );

	return attributeValues;
}
export function getContext(
	element: Element,
	contextItemNames: string[]
): {
	[ key: string ]: string | boolean | number;
} {
	const context: { [ key: string ]: string | boolean | number } = {};

	// Get context from ancestor data attributes
	contextItemNames.forEach( ( name: string ) => {
		let parent = element.parentElement;
		while ( parent ) {
			const dataAttr =
				'data-context-' + getClassNameFromNamespacedName( name );
			const attrValue = parent.getAttribute( dataAttr );
			if ( attrValue ) {
				context[ getLocalNameFromNamespacedName( name ) ] = attrValue;
				break;
			}
			parent = parent.parentElement;
		}
		if ( ! context[ 'bh-wp-bitcoin-gateway/' + name ] ) {
			console.log(
				`Context attribute ${ name } not found in ancestors of`,
				element
			);
			console.warn(
				`Context attribute ${ name } not found in ancestors of`,
				element
			);
		} else {
			console.log(
				`Context attribute ${ name } found: ${ context[ name ] }`
			);
		}
	} );
	return context;
}
