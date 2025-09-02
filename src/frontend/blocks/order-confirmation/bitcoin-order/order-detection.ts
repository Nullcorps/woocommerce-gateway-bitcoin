/**
 * Utility functions for detecting order information on WooCommerce Thank You pages
 */

/**
 * Extract order ID from various possible sources
 */
export const detectOrderId = (): number => {
	// Try URL parameters first (most common on Thank You pages)
	if (typeof window !== 'undefined') {
		const urlParams = new URLSearchParams(window.location.search);
		const orderReceived = urlParams.get('order-received');
		if (orderReceived) {
			const orderId = parseInt(orderReceived, 10);
			if (!isNaN(orderId)) {
				return orderId;
			}
		}

		// Try to find order ID in the URL path
		const pathMatch = window.location.pathname.match(/\/order-received\/(\d+)/);
		if (pathMatch && pathMatch[1]) {
			const orderId = parseInt(pathMatch[1], 10);
			if (!isNaN(orderId)) {
				return orderId;
			}
		}

		// Try to find order ID in WooCommerce data attributes
		const orderReceivedElement = document.querySelector('[data-order_id]');
		if (orderReceivedElement) {
			const orderId = parseInt(orderReceivedElement.getAttribute('data-order_id') || '0', 10);
			if (!isNaN(orderId)) {
				return orderId;
			}
		}

		// Try to find in global WooCommerce variables if available
		if (typeof (window as any).wc_order_params !== 'undefined') {
			const orderId = parseInt((window as any).wc_order_params.order_id, 10);
			if (!isNaN(orderId)) {
				return orderId;
			}
		}
	}

	return 0;
};

/**
 * Check if we're on a WooCommerce Thank You page
 */
export const isThankYouPage = (): boolean => {
	if (typeof window === 'undefined') {
		return false;
	}

	// Check URL patterns
	return (
		window.location.pathname.includes('/order-received/') ||
		window.location.search.includes('order-received=') ||
		document.body.classList.contains('woocommerce-order-received')
	);
};