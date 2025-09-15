/**
 * Given "brianhenryie/whatever" returns "whatever".
 */
export function getClassNameFromNamespacedName( namespacedName: string ): string {
  return namespacedName.replace( /\//g, '-' );
}

/**
 * Given "brianhenryie/whatever" returns "brianhenryie-whatever".
 */
export function getLocalNameFromNamespacedName( namespacedName: string ): string {
  return namespacedName.split( '/' )[ 1 ];
}
