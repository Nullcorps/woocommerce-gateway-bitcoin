import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const bhSettings = getSetting( 'bitcoin_gateway_data', {} );

const bhDefaultLabel = __( 'Bitcoin', 'bh-wc-bitcoin-gateway' );

const bhLabel = decodeEntities( bhSettings.title ) || bhDefaultLabel;

/**
 * Content component
 */
const BHContent = () => {
	return decodeEntities( bhSettings.description || '' );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const BHLabel = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ bhLabel } />;
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
	ariaLabel: bhLabel,
	supports: {
		features: bhSettings.supports,
	},
};

registerPaymentMethod( BitcoinGateway );
