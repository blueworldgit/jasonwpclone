import {getSetting} from '@woocommerce/settings';
import {decodeEntities} from '@wordpress/html-entities';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';

const settings = getSetting( 'access_worldpay_hpp_data', {} )

const label = decodeEntities( settings.title )

const Content = () => {
	return decodeEntities( settings.description || '' )
}

const Label                          = (props) => {
	const {PaymentMethodLabel}       = props.components
	return <PaymentMethodLabel text = {label} />
}

const AccessWorldpayHPP = {
	name: "access_worldpay_hpp",
	label: <Label /> ,
	content: <Content /> ,
	edit: <Content /> ,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
		showSaveOption: settings.canSaveCard ?? false,
		showSavedCards: settings.canSaveCard ?? false
	}
};

registerPaymentMethod( AccessWorldpayHPP );
