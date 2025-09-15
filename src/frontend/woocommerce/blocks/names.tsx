/**
 * Given "brianhenryie/whatever" returns "whatever".
 * @param namespacedName
 */
export function getClassNameFromNamespacedName(
	namespacedName: string
): string {
	return namespacedName.replace( /\//g, '-' );
}

/**
 * Given "brianhenryie/whatever" returns "brianhenryie-whatever".
 * @param namespacedName
 */
export function getLocalNameFromNamespacedName(
	namespacedName: string
): string {
	return namespacedName.split( '/' )[ 1 ];
}
