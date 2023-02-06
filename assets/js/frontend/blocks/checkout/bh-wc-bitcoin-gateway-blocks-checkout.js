import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const bh_settings = getSetting( 'bitcoin_gateway_data', {} );

const bh_defaultLabel = __( 'Bitcoin', 'bh-wc-bitcoin-gateway' );

const bh_label = decodeEntities( bh_settings.title ) || bh_defaultLabel;

/**
 * Content component
 */
const BHContent = () => {
	return decodeEntities( bh_settings.description || '' );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const BHLabel = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ bh_label } />;
};

/**
 * Payment method config object.
 */
const BitcoinGateway = {
	name: 'bitcoin_gateway',
	label: <BHLabel />,
	content: <BHContent />,
	edit: <BHContent />,
	canMakePayment: () => true,
	ariaLabel: bh_label,
	supports: {
		features: bh_settings.supports,
	},
};

registerPaymentMethod( BitcoinGateway );
