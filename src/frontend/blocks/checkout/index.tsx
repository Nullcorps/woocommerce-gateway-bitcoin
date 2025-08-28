/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import React from 'react';

/**
 * @see Bitcoin_Gateway_Blocks_Checkout_Support::get_payment_method_data()
 */
interface BitcoinGatewaySettings {
  title?: string;
  description?: string;
  supports?: string[];
  currency_symbol?: string;
  exchange_rate_information?: string;
}

const bhSettings: BitcoinGatewaySettings = getSetting('bitcoin_gateway_data', {});

const bhDefaultLabel: string = __('Bitcoin', 'bh-wp-bitcoin-gateway');

const bhLabel: string = decodeEntities(bhSettings.title || '') || bhDefaultLabel;

/**
 * Content component
 */
const BHContent: React.FC = (): React.ReactElement => {
  console.log(bhSettings);
  return (
    <div>
      <p className="wc-block-components-checkout-step__description">{decodeEntities(bhSettings.description || '')}</p>
      <p className="wc-block-components-checkout-step__description">{bhSettings.exchange_rate_information || ''}</p>
    </div>
  );
};

/**
 * Label component
 *
 * @param props Props from payment API.
 */
const BHLabel: React.FC<PaymentMethodProps> = (props): React.ReactElement => {
  const { PaymentMethodLabel } = props.components;
  return <PaymentMethodLabel text={bhLabel} />;
};

/**
 * Payment method config object.
 */
const BitcoinGateway: PaymentMethodConfig = {
  name: 'bitcoin_gateway',
  label: <BHLabel />,
  content: <BHContent />,
  edit: <BHContent />,
  canMakePayment: (): boolean => true,
  ariaLabel: bhLabel,
  supports: {
    features: bhSettings.supports || [],
  },
};

registerPaymentMethod(BitcoinGateway);