/* global worldpay_ecommerce_admin_params */
( function ( worldpay_ecommerce_admin_params, $ ) {
	$( document ).ready(
		function () {
			let payment_method_id  = worldpay_ecommerce_admin_params.payment_method_id;
			let worldpay_ecommerce = {
				init() {
					this.setTestCredentialsButtonValue();
					this.setRequired();
					this.clearField();
					$( document ).on(
						'click',
						'.worldpay-ecommerce-test-credentials',
						this.testApiCredentials.bind( this )
					);
					$( document ).on(
						'click',
						'#woocommerce_' + payment_method_id + '_is_live_mode',
						this.setRequired.bind( this )
					);
				},
				testApiCredentials( e ) {
					let self       = this;

					let appMode         = $( e.target ).data( 'app-mode' );
					let usernameFieldId = 'woocommerce_' + payment_method_id + '_app_api_' + appMode + '_username';
					let passwordFieldId = 'woocommerce_' + payment_method_id + '_app_api_' + appMode + '_password';
					let checkoutIdFieldId = 'woocommerce_' + payment_method_id + '_app_merchant_' + appMode + '_checkout_id';
					let username        = $( '#' + usernameFieldId ).val();
					let password        = $( '#' + passwordFieldId ).val();
					let merchantEntity  = $( '#woocommerce_' + payment_method_id + '_app_merchant_entity' ).val();
					let data = {
						_wpnonce: worldpay_ecommerce_admin_params._wpnonce,
						app_mode: appMode,
						app_username: username,
						app_password: password,
						app_merchant_entity: merchantEntity,
						method_id: payment_method_id
					};
					if ('access_worldpay_checkout' === payment_method_id) {
						data.app_checkout_id = $( '#' + checkoutIdFieldId ).val();
					}

					let url = worldpay_ecommerce_admin_params.test_api_credentials_url;
					$( '.notice' ).remove();
					$( '#message' ).remove();

					$.ajax(
						{
							type: 'POST',
							url: url,
							data: data,
							success: function (response) {
								self.displayNotice( response.status, response.message );
							}
						}
					);
				},
				displayNotice: function (type, message) {
					let notice = $( '<div />' );
					notice.attr( 'class', 'inline notice notice-' + type );
					let noticeContent = $( '<strong />' );
					noticeContent.text( message );
					notice.append( noticeContent );

					$( 'html, body' ).animate(
						{
							scrollTop: $( '#wpwrap' ).offset().top
						},
						'slow',
						function () {
							notice.insertAfter( $( '#mainform' ).find( 'h1' ) );
						}
					);
				},
				setTestCredentialsButtonValue() {
					$( '#woocommerce_' + payment_method_id + '_test_try_credentials' ).attr( 'value', 'Test try credentials' );
					$( '#woocommerce_' + payment_method_id + '_test_live_credentials' ).attr( 'value', 'Test live credentials' );
				},
				clearField() {
					let fields = [];
					$.each(
						['#woocommerce_' + payment_method_id + '_app_api_try_password', '#woocommerce_' + payment_method_id + '_app_api_live_password', '#woocommerce_' + payment_method_id + '_app_merchant_entity'],
						function (key, value) {
							$( value ).focusin(
								function () {
									if ($( this ).val() == fields[value]) {
										$( this ).val( '' );
									}
								}
							);
							fields[value] = $( value ).val();
							$( value ).focusout(
								function () {
									if ($( this ).val() == '') {
										$( this ).val( fields[value] );
									}
								}
							);
						}
					);
				},
				setRequired() {
					let isLiveMode = $( '#woocommerce_' + payment_method_id + '_is_live_mode' ).is( ':checked' );

					$( '#woocommerce_' + payment_method_id + '_app_api_live_username' ).prop( 'required', isLiveMode );
					$( '#woocommerce_' + payment_method_id + '_app_api_live_password' ).prop( 'required', isLiveMode );
					$( '#woocommerce_' + payment_method_id + '_app_merchant_live_checkout_id' ).prop( 'required', isLiveMode );
				}
			};

			worldpay_ecommerce.init();
		}
	);
} )( worldpay_ecommerce_admin_params, jQuery );
