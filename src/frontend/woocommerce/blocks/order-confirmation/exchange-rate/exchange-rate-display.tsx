/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

interface ExchangeRateDisplayProps {
	exchangeRate: string;
	showLabel: boolean;
	useUrl: boolean;
	exchangeRateUrl?: string;
}

export const ExchangeRateDisplay: React.FC< ExchangeRateDisplayProps > = ( {
	exchangeRate,
	showLabel = true,
	useUrl = true,
	exchangeRateUrl = null,
} ) => {
	if ( ! exchangeRateUrl ) {
		useUrl = false;
	}

	return (
		<div className="bh-wp-bitcoin-gateway-exchange-rate-block">
			{ showLabel && (
				<span className="exchange-rate-label">
					{ __(
						'Exchange rate at time of order:',
						'bh-wp-bitcoin-gateway'
					) }
				</span>
			) }
			{ useUrl && (
				<a href={ exchangeRateUrl! } target="_blank" rel="noreferrer">
					<span className="exchange-rate-value">
						1 BTC ={ ' ' }
						<span
							dangerouslySetInnerHTML={ { __html: exchangeRate } }
						/>
					</span>
				</a>
			) }
			{ ! useUrl && (
				<span className="exchange-rate-value">
					1 BTC ={ ' ' }
					<span
						dangerouslySetInnerHTML={ { __html: exchangeRate } }
					/>
				</span>
			) }
		</div>
	);
};
