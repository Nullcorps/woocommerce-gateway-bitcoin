// NB: Setting an order status to "processing" does not trigger the same actions as marking it paid.
export async function setOrderStatus(
	page: any,
	orderId: number,
	status: string
) {
	// Navigate to edit order page
	await page.goto( `/wp-admin/post.php?post=${ orderId }&action=edit` );

  await page.waitForLoadState( 'networkidle' );
  
	// Update order status
	await page.selectOption( '#order_status', status );
	await page.click( '#woocommerce-order-actions .save_order' );
	await page.waitForSelector( '.notice-success' );
}
