/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';

interface ExchangeRateDisplayProps {
	showLabel?: boolean;
	isPreview?: boolean;
  orderId?: number;
}

export const ExchangeRateDisplay: React.FC<ExchangeRateDisplayProps> = ({ 
	showLabel = true, 
	isPreview = false,
  orderId = null
}) => {
	const [exchangeRate, setExchangeRate] = useState<string>('');
	const [loading, setLoading] = useState(!isPreview);

  console.log('orderId' + orderId);

	useEffect(() => {

    // TODO: get the current exchange rate from the db.
		if (isPreview) {
			setExchangeRate('$45,000.00 USD');
			return;
		}

		// Get order ID from URL - WordPress order received page format

		// Try to get from URL parameter first
		// const orderReceivedParam = urlParams.get('order-received');
		// if (orderReceivedParam) {
		// 	orderId = parseInt(orderReceivedParam);
		// }
		//
		// // Fallback: try to get from pathname
		// if (!orderId) {
    console.log(window.location.pathname);

			const pathMatch = window.location.pathname.match(/order-received\/(\d+)/);
			orderId = pathMatch ? parseInt(pathMatch[1]) : null;
		// }
		
		// Fallback: check for order-received in hash
		if (!orderId && window.location.hash) {
			const hashMatch = window.location.hash.match(/order-received\/(\d+)/);
			orderId = hashMatch ? parseInt(hashMatch[1]) : null;
		}
		
		console.log('Exchange rate block - detected order ID:', orderId);
		
		if (!orderId) {
			console.log('Exchange rate block - no order ID found in URL');
			setLoading(false);
			return;
		}

		// Fetch order meta
		const fetchExchangeRate = async () => {
			try {
				const response = await fetch(`/wp-json/wc/v3/orders/${orderId}`, {
					credentials: 'include',
					headers: {
						'X-WP-Nonce': (window as any).wpApiSettings?.nonce || '',
					},
				});
				
				if (!response.ok) {
					setLoading(false);
					return;
				}

				const order = await response.json();
				
				// Check if this is a Bitcoin Gateway order
				if (order.payment_method !== 'bh_wp_bitcoin_gateway') {
					setLoading(false);
					return;
				}

				// Get the exchange rate from meta
				const exchangeRateMeta = order.meta_data?.find(
					(meta: any) => meta.key === 'bh_wp_bitcoin_gateway_exchange_rate_at_time_of_purchase'
				);

				if (exchangeRateMeta?.value) {

          console.error('Found exchange rate:', exchangeRateMeta?.value);

          setExchangeRate(exchangeRateMeta.value);
				}
			} catch (error) {
				console.error('Error fetching exchange rate:', error);
			}
			
			setLoading(false);
		};

		fetchExchangeRate();
	}, []);

	if (loading) {
		return <div className="bh-wp-bitcoin-gateway-exchange-rate-loading">{__('Loading...', 'bh-wp-bitcoin-gateway')}</div>;
	}

	if (!exchangeRate) {
		return null;
	}

	return (
		<div className="bh-wp-bitcoin-gateway-exchange-rate-block">
			{showLabel && (
				<span className="exchange-rate-label">
					{__('Exchange rate at time of order:', 'bh-wp-bitcoin-gateway')} 
				</span>
			)}
			<span className="exchange-rate-value">
				1 BTC = {exchangeRate}
			</span>
		</div>
	);
};