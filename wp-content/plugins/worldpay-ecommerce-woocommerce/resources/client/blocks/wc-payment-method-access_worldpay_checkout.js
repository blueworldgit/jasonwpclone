/**
 * External dependencies
 */
import React from 'react';
import {
	useEffect,
	useLayoutEffect,
	useState
} from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { checkoutSDKInitPromise } from "../checkout-sdk/init";
import { SetupDDCFormIframe } from "../checkout-sdk/ddc";
import { SetupChallengeFormIframe } from "../checkout-sdk/challenge";
import { CARD } from "../checkout-sdk/constants";
import { CVV_ONLY } from "../checkout-sdk/constants";
import {
	PaymentMethodTitle,
	EnvironmentIndicator,
	PaymentMethodFields,
	PaymentMethodField,
	CardHolderName
} from "../form/elements";
import { UseOnPaymentSetup } from "../hooks/use-payment-setup";
import { UseOnCheckoutFail } from "../hooks/use-checkout-fail";
import { UseOnCheckoutSuccess } from "../hooks/use-checkout-success";

const settings = getSetting( 'access_worldpay_checkout_data', {} );

const label = decodeEntities( settings.title );

const PaymentMethodEditContent = () => {
	return decodeEntities( settings.description || '' )
}

const PaymentForm = ( props ) => {
	const {
		emitResponse,
		eventRegistration,
		isSavedPaymentMethod,
		token
	} = props;

	const {
		onCheckoutFail,
		onPaymentSetup,
		onCheckoutSuccess,
	} = eventRegistration;

	const [ checkout, setCheckout ] = useState( null );
	const [ cardHolderName, setCardHolderName ] = useState( '' );
	const [ ddcFormUrl, setDdcFormUrl ] = useState( '' );
	const [ challengeFormUrl, setChallengeFormUrl ] = useState( '' );
	const [ challengeFormWidth, setChallengeFormWidth ] = useState( '' );
	const [ challengeFormHeight, setChallengeFormHeight ] = useState( '' );

	useEffect( () => {
		const checkoutInit = () => {
			let formFields = CARD;
			if ( isSavedPaymentMethod ) {
				formFields = CVV_ONLY;
			}
			checkoutSDKInitPromise( settings.checkoutId, formFields )
				.then( ( checkoutInstance ) => {
					setCheckout( checkoutInstance );
				} )
				.catch( ( reason ) => {
					console.error( reason );
					return {
						type: emitResponse.responseTypes.ERROR,
						message: __(
							'Something went wrong while loading the payment form.',
							'worldpay-ecommerce-woocommerce'
						),
					};
				} );
		};

		checkoutInit();

	}, [
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
	] );

	UseOnPaymentSetup(
		checkout,
		emitResponse,
		onPaymentSetup,
		cardHolderName,
		setCardHolderName,
		token
	);

	UseOnCheckoutFail(
		onCheckoutFail,
		emitResponse,
		checkout,
		setDdcFormUrl,
		setCardHolderName
	);

	UseOnCheckoutSuccess(
		onCheckoutSuccess,
		emitResponse,
		checkout,
		setDdcFormUrl,
		setChallengeFormUrl,
		setChallengeFormWidth,
		setChallengeFormHeight,
		setCardHolderName
	);

	useLayoutEffect( () => {
			// Make sure to call the remove method (once) in order to deallocate the SDK from memory
			if ( typeof checkout !== 'undefined' && checkout !== null ) {
				return () => checkout.remove();
			}
		}, [] );

	return (
		<>
			{ settings.isTryMode && <EnvironmentIndicator /> }
			<PaymentMethodFields>
				{ ! isSavedPaymentMethod && (
					<>
						<PaymentMethodField
							id="card-number"
							label={ __( 'Card Number', 'worldpay-ecommerce-woocommerce' ) }
						/>
						<PaymentMethodField
							id="card-expiry"
							label={ __( 'Expiry Date', 'worldpay-ecommerce-woocommerce' ) }
						/>
					</>
				) }
				<PaymentMethodField
					id="card-cvc"
					label={ __( 'Card Code', 'worldpay-ecommerce-woocommerce' ) }
				/>
			</PaymentMethodFields>
			{ ! isSavedPaymentMethod &&
				<CardHolderName
					cardHolderName={ cardHolderName }
					setCardHolderName={ setCardHolderName }
				/>
			}
			{ ddcFormUrl &&
				<SetupDDCFormIframe ddcFormUrl={ ddcFormUrl } />
			}
			{ challengeFormUrl &&
				<SetupChallengeFormIframe
					challengeFormUrl={ challengeFormUrl }
					challengeFormWidth={ challengeFormWidth }
					challengeFormHeight={ challengeFormHeight }
				/>
			}
		</>
	);
}

const AccessWorldpayCheckout = {
	name: "access_worldpay_checkout",
	label: <PaymentMethodTitle text={ label } icons={ settings.icons } />,
	content: <PaymentForm isSavedPaymentMethod={ false } />,
	edit: <PaymentMethodEditContent />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
		showSaveOption: settings.canSaveCard ?? false,
		showSavedCards: settings.canSaveCard ?? false
	},
	savedTokenComponent: <PaymentForm isSavedPaymentMethod={ true } />
};

registerPaymentMethod( AccessWorldpayCheckout );
