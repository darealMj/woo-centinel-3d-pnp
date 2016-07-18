<?php
/**
 * Plugin Name: MAJObytes Centinel 3D Secure
 * Plugin URI: http://www.majobytes.com/
 * Description: WooCommerce Plugin for using Centinel 3D Secure and Plug'n Pay.
 * Version: 1.1
 * Author: MAJObytes
 * Author URI: http://www.majobytes.com
 * Contributors: majobytes
 * //Requires at least: 3.5
 * //Tested up to: 4.1
 *
 * //Text Domain: centinel_pnp_majobytes
 * Domain Path: /lang/
 *
 * //@package  MAJObytes Centinel 3D Secure Direct Gateway for WooCommerce
 * //@author MAJObytes
 */
 
 add_action('plugins_loaded', 'init_centinel_pnpdirect');
 
 function init_centinel_pnpdirect(){
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) { 
			return; 
		}
		
		class centinel_pnpdirect extends WC_Payment_Gateway {
			public function __construct() {
				global $woocommerce;
				
				$this->id					= 'centinel3dplugnpaydirect';
				$this->icon 				= plugins_url( 'plugnpay.png', __FILE__ );
				$this->has_fields 			= TRUE;
				$this->method_title 		= __( 'Centinel 3D Secure', 'centinel_pnp_majobytes' );
				$this->method_description	= __( 'Centinel and Plug\'n Pay Direct', 'centinel_pnp_majobytes' );
				
				// Load the form fields.
				$this->init_form_fields();
				
				// Load the settings.
				$this->init_settings();
				
				// Define user set variables
				$this->title 				= $this->settings['title'];
				$this->description 			= $this->settings['description'];
				$this->woo_version 			= $this->get_woo_version();
				$this->processor_id			= $this->settings['processor_id'];
				$this->merchant_id			= $this->settings['merchant_id']; 
				$this->transaction_password = $this->settings['transaction_password'];
				$this->publisher_name 		= $this->settings['publisher_name'];
				$this->publisher_email 		= $this->settings['publisher_email'];
				$this->publisher_mode 		= $this->settings['publisher_mode'];
				
				add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));//need both of these  TODO:"
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action('woocommerce_api_centinel_pnpdirect', array($this, 'check_response'));
				// Receipt page before redirecting to payment gateway
				add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page' ));
				
				
			}
			
			public function check_response(){
				error_log("GET THERE", 0);
				error_log($_POST , 0);
				
				$this->auth_3dsecure();			
			}
			
			function receipt_page( $order ){			
				error_log("Receipt Page",0);
				echo '<p>' . __('Thank you for your purchase, please click below to pay via Centinel .', 'woocommerce').'</p>';
				echo $this->handle_3dsecure( );
			}
			
			/**
			 *Handle creation of form to POST to 3D Secure server			 
			 */			
			public function handle_3dsecure(){
				error_log("Handle_3DSecure",0);
				if ( ! empty( $_GET['acs'] ) ) {
					$order_id = wc_clean( $_GET['acs'] );
					$acsurl   = WC()->session->get( 'Centinel_ACSUrl' );
					$payload  = WC()->session->get( 'Centinel_Payload' );
					$termUrl = WC()->session->get( 'Centinel_TermUrl' );
					
					/*error_log("ACS Url: ".WC()->session->get( 'Centinel_ACSUrl' ), 0);	
					error_log("ACS Url Escaped: ".esc_attr(WC()->session->get( 'Centinel_ACSUrl' )), 0);
					error_log("Payload: ".WC()->session->get( 'Centinel_Payload' ), 0);	*/			
				
					echo '<form action="'.esc_url($acsurl).'" method="post" id="centinelPaymentForm">
					<input type=hidden name="PaReq" value="'.esc_attr($payload).'">
					<input type=hidden name="TermUrl" value="'.esc_attr($termUrl).'">
					<input type=hidden name="MD" value="'.absint( $order_id ).'">
					<input type="submit" value="Submit" id="centinelPaymentFormSubmit"> 				
					</form>
					<script type="text/javascript">
						//document.getElementById("centinelPaymentForm").submit(); // SUBMIT FORM
					</script>';	
				}		
				return;								
			}
			
			/**
			 * Admin Panel Options
			 * - Options for bits like 'title' and availability on a country-by-country basis
			 *
			 * @since 1.0.0
			 */
			public function admin_options() {
				
				?>
				<h3><?php _e('Centinel 3D Secure', 'centinel_pnp_majobytes'); ?></h3>
				<p><?php _e('Centinel 3D Secure works by verifying Credit Card credentials then processing Credit Cards on site. So users do not leave your site for payments to enter their payment information.' , 'centinel_pnp_majobytes'); ?></p>
				<table class="form-table">
				<?php
				
					// Generate the HTML For the settings form.
					$this->generate_settings_html();
					
				?>
				</table><!--/.form-table-->
				<?php
			} // End admin_options()	
			
			/**
			 * Initialise Gateway Settings Form Fields
			 */
			function init_form_fields() {
				$this->form_fields = array(
						'enabled' => array(
										'title' => __( 'Enable/Disable', 'centinel_pnp_majobytes' ),
										'type' => 'checkbox',
										'label' => __( 'Enable Centinel 3D Secure ', 'centinel_pnp_majobytes' ),
										'default' => 'yes'
									),
						'title' => array(
										'title' => __( 'Title', 'centinel_pnp_majobytes' ), 
										'type' => 'text', 
										'description' => __( 'This controls the title which the user sees during checkout.', 'centinel_pnp_majobytes' ), 
										'default' => __( 'Centinel 3D Secure', 'centinel_pnp_majobytes' )
									),
						'description' => array(
										'title' => __( 'Description', 'centinel_pnp_majobytes' ), 
										'type' => 'textarea', 
										'description' => __( 'This controls the description which the user sees during checkout.', 'centinel_pnp_majobytes' ), 
										'default' => __('Centinel 3D Secure; Enter your Credit Card Details Below which match the billing address above.', 'centinel_pnp_majobytes')
						),			
						'processor_id' => array(
										'title' => __( 'Processor ID', 'centinel_pnp_majobytes' ), 
										'type' => 'text',
										'description' => __( 'Your processor ID for the Centinel 3D Secure service', 'centinel_pnp_majobytes' ), 
										'default' => ''
						),
						'merchant_id' => array(
										'title' => __( 'Merchant ID', 'centinel_pnp_majobytes' ), 
										'type' => 'text',
										'description' => __( 'Your merchant ID for the Centinel 3D Secure service', 'centinel_pnp_majobytes' ), 
										'default' => ''
						),
						'transaction_password' => array(
										'title' => __( 'Transaction Password', 'centinel_pnp_majobytes' ), 
										'type' => 'password',
										'description' => __( 'Your transaction password for the Centinel 3D Secure service', 'centinel_pnp_majobytes' ), 
										'default' => ''
						),
						'publisher_name' => array(
										'title' => __( 'Publisher Name', 'centinel_pnp_majobytes' ), 
										'type' => 'text', 
										'description' => __( 'Your login username used for the Plug\'n Pay service.', 'centinel_pnp_majobytes' ), 
										'default' => ''
						),
						'publisher_email' => array(
										'title' => __( 'Publisher Email', 'centinel_pnp_majobytes' ), 
										'type' => 'text', 
										'description' => __( 'Merchant Confirmation email address used for confirmation emails.', 'centinel_pnp_majobytes' ), 
										'default' => ''
						),
						'publisher_mode' => array(
										'title' => __( 'Publisher Mode', 'centinel_pnp_majobytes' ), 
										'type' => 'text', 
										'description' => __( 'Merchant mode to be used (not implemented).', 'centinel_pnp_majobytes' ), 
										'default' => ''
						)
				);
			}	// End init_form_fields()
			
			/**
			 * Process the payment and return the result
			 **/
			function payment_fields() {
				if ($this->description) echo wpautop(wptexturize($this->description));
				?>
				<p class="form-row form-row-first">
					<label><?php echo __("Card Number", 'centinel_pnp_majobytes') ?> 
					<span class="required">*</span></label>
					<input class="input-text" style="width:180px;" type="text" size="16" maxlength="16" name="c3d_card_number" id="c3d_card_number" />
				</p>
				<div class="clear"></div>
				<p class="form-row form-row-first">
					<label><?php echo __("Expiration Date", 'centinel_pnp_majobytes') ?> <span class="required">*</span></label>
					<select id="c3d_expr_mm" name="c3d_expr_mm" class="woocommerce-select woocommerce-cc-month">
						<option value=""><?php _e('Month', 'centinel_pnp_majobytes') ?></option>
						<option value=01> 1 - January</option>
						<option value=02> 2 - February</option>
						<option value=03> 3 - March</option>
						<option value=04> 4 - April</option>
						<option value=05> 5 - May</option>
						<option value=06> 6 - June</option>
						<option value=07> 7 - July</option>
						<option value=08> 8 - August</option>
						<option value=09> 9 - September</option>
						<option value=10>10 - October</option>
						<option value=11>11 - November</option>
						<option value=12>12 - December</option>
					</select>
					<select id="c3d_expr_yyyy" name="c3d_expr_yyyy" class="woocommerce-select woocommerce-cc-year">
					<option value=""><?php _e('Year', 'centinel_pnp_majobytes') ?></option>
					<?php
						$today = (int)date('y', time());
						$today1 = (int)date('Y', time());
						for($i = 0; $i < 8; $i++)
						{
							?>
							<option value="<?php echo $today1; ?>"><?php echo $today1; ?></option>
							<?php
							$today++;
							$today1++;
						}
					?>
					</select>
				</p>
				<div class="clear"></div>
				<p class="form-row form-row-first">
					<label><?php echo __("Card CVV", 'centinel_pnp_majobytes') ?> 
					<span class="required">*</span></label>
					<input class="input-text" style="width:180px;" type="text" size="5" maxlength="5" id="c3d_cvv" name="c3d_cvv" />
				</p>
				<div class="clear"></div>
				<?php
			}
			
			
			public function validate_fields()
			{
				global $woocommerce;
				//error_log("Reached validate fields function!", 0);
				//Check if Credit Card Number is valid
				if (!$this->isCreditCardNumber($_POST['c3d_card_number'])){
					if( $this->woo_version >= 2.1 ){						
						wc_add_notice( __('(Credit Card Number) is not valid.', 'centinel_pnp_majobytes'), $notice_type = 'error' );		
					}else if( $woo_version < 2.1 ){						
						$woocommerce->add_error( __('(Credit Card Number) is not valid.', 'centinel_pnp_majobytes') );
					}else{
						$woocommerce->add_error( __('(Credit Card Number) is not valid.', 'centinel_pnp_majobytes') );
					}
				}
				if (!$this->isCorrectExpireDate($_POST['c3d_expr_mm'], $_POST['c3d_expr_yyyy'])){					/*error_log("(Card Expiry Date) is not valid.", 0);*/
					if( $this->woo_version >= 2.1 ){
						wc_add_notice( __('(Card Expiry Date) is not valid.', 'centinel_pnp_majobytes'), $notice_type = 'error' );		
					}else if( $woo_version < 2.1 ){
						$woocommerce->add_error( __('(Card Expiry Date) is not valid.', 'centinel_pnp_majobytes') );
					}else{
						$woocommerce->add_error( __('(Card Expiry Date) is not valid.', 'centinel_pnp_majobytes') );
					}
				}

				if (!$_POST['c3d_cvv']){					/*error_log("Card CVV is not entered.", 0);*/
					if( $this->woo_version >= 2.1 ){
						wc_add_notice( __('(Card CVV) is not entered.', 'centinel_pnp_majobytes'), $notice_type = 'error' );		
					}else if( $woo_version < 2.1 ){
						$woocommerce->add_error( __('(Card CVV) is not entered.', 'centinel_pnp_majobytes') );
					}else{
						$woocommerce->add_error( __('(Card CVV) is not entered.', 'centinel_pnp_majobytes') );
					}
				}				
				
			}
			
			/**
			 * cmpi_authenticate 3dsecure
			 */
			public function auth_3dsecure() {
				error_log("auth 3d secure function", 0);
				if ( ! class_exists( 'CentinelClient' ) ) {
					require('CentinelClient.php');
				}
				if ( ! class_exists( 'CentinelConfig' ) ) {
					require('CentinelConfig.php');
				}
				if ( ! class_exists( 'CentinelUtility' ) ) {
					require('CentinelUtility.php');
				}
				
				$pares        = ! empty( $_POST['PaRes'] ) ? $_POST['PaRes']   : '';
				$order_id     = absint( ! empty( $_POST['MD'] ) ? $_POST['MD'] : 0 );
				$order        = wc_get_order( $order_id );
				$redirect_url = $this->get_return_url( $order );
				
				try{
					if( ! empty( $pares ) ){
						$centinelClient = new CentinelClient;
						$centinelClient->add('MsgType', 'cmpi_authenticate');
						$centinelClient->add('Version', CENTINEL_MSG_VERSION);
						$centinelClient->add('MerchantId', CENTINEL_MERCHANT_ID);
						$centinelClient->add('ProcessorId', CENTINEL_PROCESSOR_ID);
						$centinelClient->add('TransactionPwd', CENTINEL_TRANSACTION_PWD);
						$centinelClient->add('TransactionType', WC()->session->get( 'Centinel_TransactionType' ));
						$centinelClient->add('OrderId', $order_id);
						$centinelClient->add('TransactionId', WC()->session->get( 'Centinel_TransactionId' ));
						$centinelClient->add('PAResPayload', $pares);
						
						$centinelClient->sendHttp(CENTINEL_MAPS_URL, CENTINEL_TIMEOUT_CONNECT, CENTINEL_TIMEOUT_READ);
						
						/*$_SESSION["Centinel_cmpiMessageResp"]       = $centinelClient->response; // Save authenticate response in session
						$_SESSION["Centinel_PAResStatus"]           = $centinelClient->getValue("PAResStatus");
						$_SESSION["Centinel_SignatureVerification"] = $centinelClient->getValue("SignatureVerification");
						$_SESSION["Centinel_ErrorNo"]               = $centinelClient->getValue("ErrorNo");
						$_SESSION["Centinel_ErrorDesc"]             = $centinelClient->getValue("ErrorDesc");*/
						
						WC()->session->set( 'Centinel_cmpiMessageResp', $centinelClient->response );
						WC()->session->set( 'Centinel_PAResStatus', $centinelClient->getValue("PAResStatus") );
						WC()->session->set( 'Centinel_SignatureVerification', $centinelClient->getValue("SignatureVerification") );
						WC()->session->set( 'Centinel_ErrorNo', $centinelClient->getValue("ErrorNo") );
						WC()->session->set( 'Centinel_ErrorDesc', $centinelClient->getValue("ErrorDesc") );						
						
						if( (strcasecmp('Y', WC()->session->get( 'Centinel_PAResStatus' )) == 0 || strcasecmp('A', WC()->session->get( 'Centinel_PAResStatus' )) == 0) && (strcasecmp('Y', WC()->session->get( 'Centinel_SignatureVerification' )) == 0) && (strcasecmp('0', WC()->session->get( 'Centinel_ErrorNo' )) == 0 || strcasecmp('1140', WC()->session->get( 'Centinel_ErrorNo' )) == 0) ) {
							// Transaction completed successfully. 
							//$_SESSION["Message"] = "Transaction completed successfully. (ErrorNo: [{$_SESSION['Centinel_ErrorNo']}], ErrorDesc: [{$_SESSION['Centinel_ErrorDesc']}])";
							WC()->session->set( 'Message', "Transaction completed successfully. (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])" );
					
							//$redirectPage = 'ccResults.php';
							
						} else if( (strcasecmp('N', WC()->session->get( 'Centinel_PAResStatus' )) == 0) && (strcasecmp('Y', WC()->session->get( 'Centinel_SignatureVerification' )) == 0) && (strcasecmp('0', WC()->session->get( 'Centinel_ErrorNo' )) == 0 || strcasecmp('1140', WC()->session->get( 'Centinel_ErrorNo' )) == 0) ) {
							// Unable to authenticate. Provide another form of payment. 
							//$_SESSION["Message"] = "Unable to authenticate. Provide another form of payment. (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [{$_SESSION['Centinel_ErrorDesc']}])";
							WC()->session->set( 'Message', "Unable to authenticate. Provide another form of payment. (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])" );
					
							//$redirectPage = 'ccResults.php';
						} else {
							// Transaction complete however is pending review. Order will be shipped once payment is verified. 
							//$_SESSION["Message"] = "Transaction complete however is pending review. Order will be shipped once payment is verified. (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [{$_SESSION['Centinel_ErrorDesc']}])";
							WC()->session->set( 'Message', "Transaction complete however is pending review. Order will be shipped once payment is verified. (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])" );
					
							//$redirectPage = 'ccResults.php';

						} // end processing logic
						
						if(WC()->session->get( 'Centinel_cmpiMessageResp' )){
							//send to plug n pay
							
							
							
							error_log("send to plug n pay", 0);
						}
						else{
							$order->update_status( 'failed', sprintf( __( '3D Secure error: %s', 'centinel_pnp_majobytes' ), WC()->session->get( 'Centinel_ErrorDesc' ) ) );
							throw new Exception( __( 'Payer Authentication failed. Please try a different payment method.','centinel_pnp_majobytes' ) );
						}					
						
					}
					else{
						//$_SESSION["Centinel_ErrorNo"]   = "0";
						//$_SESSION["Centinel_ErrorDesc"] = "NO PARES RETURNED";
						WC()->session->set( 'Centinel_ErrorNo', "0" );
						WC()->session->set( 'Centinel_ErrorDesc', "NO PARES RETURNED" );
						$order->update_status( 'failed', sprintf( __( '3D Secure error: %s', 'centinel_pnp_majobytes' ), WC()->session->get( 'Centinel_ErrorDesc' ) ) );
						throw new Exception( __( 'Payer Authentication failed. Please try a different payment method.','centinel_pnp_majobytes' ) );
					}				
					
				}
				catch( Exception $e ) {
					wc_add_notice( $e->getMessage(), 'error' );
				}
				
				wp_redirect( $redirect_url );
				exit;
			}
			
			
			/**
			 * Process the payment and return the result
			 **/			
			function process_payment( $order_id ) {
				
				if ( ! class_exists( 'CentinelClient' ) ) {
					require('CentinelClient.php');
				}
				if ( ! class_exists( 'CentinelConfig' ) ) {
					require('CentinelConfig.php');
				}
				if ( ! class_exists( 'CentinelUtility' ) ) {
					require('CentinelUtility.php');
				}
				/*require('CentinelClient.php');
				require('CentinelConfig.php');
				require('CentinelUtility.php');*/
				
				global $woocommerce;				
				error_log("Processing payment.", 0);				
								
				$order = new WC_Order( $order_id ); 
				
				$pnp_url = 'https://pay1.plugnpay.com/payment/pnpremote.cgi';
				$centinel_url = 'https://centineltest.cardinalcommerce.com/maps/txns.asp';				
							
				session_start();
				clearCentinelSession();
				$this->clear_centinel_session();
				
				/*******************************************************************************/
				/*                                                                             */
				/*Using the local variables and constants, build the Centinel message using the*/
				/*Centinel Thin Client.                                                        */
				/*                                                                             */
				/*******************************************************************************/

				$centinelClient = new CentinelClient;
				
				$centinelClient->add("MsgType", "cmpi_lookup");
				$centinelClient->add("Version", CENTINEL_MSG_VERSION);
				$centinelClient->add("ProcessorId", CENTINEL_PROCESSOR_ID);
				$centinelClient->add("MerchantId", CENTINEL_MERCHANT_ID);
				$centinelClient->add("TransactionPwd", CENTINEL_TRANSACTION_PWD);
				$centinelClient->add("UserAgent", $_SERVER["HTTP_USER_AGENT"]);
				$centinelClient->add("BrowserHeader", $_SERVER["HTTP_ACCEPT"]);
				$centinelClient->add("TransactionType", "C");
				$centinelClient->add('IPAddress', $_SERVER['REMOTE_ADDR']);
				
				// Standard cmpi_lookup fields								
				$centinelClient->add('OrderNumber', ltrim( $order->get_order_number(), '#' ));
				$product_description="";
				foreach($order->get_items() as $item){
					$product_description .= get_post($item['product_id'])->post_content; 
					$product_description = ". ";
				}	
				$centinelClient->add('OrderDescription', $product_description);
				$centinelClient->add('Amount', round($order->order_total*100));				
				$centinelClient->add('CurrencyCode', $this->getCurrencyCode($order->get_order_currency()));
				//$centinelClient->add('CurrencyCode', "388"); //TODO: fix. 388 is for Jamaican Dollars
				error_log("Order Currency: ".$order->get_order_currency(),0);
				error_log("Order Currency: ".$this->getCurrencyCode($order->get_order_currency()),0);
				$centinelClient->add('OrderChannel', "PRODUCT"); //TODO: fix
				$centinelClient->add('ProductCode', "PHY"); //TODO: fix
				$centinelClient->add('TransactionMode', "S"); 
				
				$centinelClient->add('BillingFirstName', $order->billing_first_name);
				$centinelClient->add('BillingLastName', $order->billing_last_name);
				$centinelClient->add('BillingAddress1', $order->billing_address_1);
				$centinelClient->add('BillingAddress2', $order->billing_address_2);
				$centinelClient->add('BillingCity', $order->billing_city);
				$centinelClient->add('BillingState', $order->billing_state); 
				$centinelClient->add('BillingPostalCode', $order->billing_postcode);
				$centinelClient->add('BillingCountryCode', $order->billing_country);
				$centinelClient->add('BillingPhone', str_replace("-", "", $order->billing_phone));				
				$centinelClient->add('EMail', $order->billing_email);				
				$centinelClient->add('ShippingFirstName', $order->shipping_first_name);
				$centinelClient->add('ShippingLastName', $order->shipping_last_name);
				$centinelClient->add('ShippingAddress1', $order->shipping_address_1);
				$centinelClient->add('ShippingAddress2', $order->shipping_address_2);
				$centinelClient->add('ShippingCity', $order->shipping_city);
				$centinelClient->add('ShippingState', $order->shipping_state);
				$centinelClient->add('ShippingPostalCode', $order->shipping_postcode);
				$centinelClient->add('ShippingCountryCode', $order->shipping_country);
				$centinelClient->add('ShippingPhone', str_replace("-", "", $order->_shipping_phone)); 
				error_log("Shipping Phone: ".str_replace("-", "", $order->_shipping_phone),0);
				
				
				// Items
				$item_loop = 0;
				//$product = new WC_Product($order_id);
				
				if ( sizeof( $order->get_items() ) > 0 ) {
					foreach ( $order->get_items() as $item ) {
						$item_loop++;
						$centinelClient->add('Item_Name_'.$item_loop, $item['name']);
						//$centinelClient->add('Item_SKU_'.$item_loop, $product->get_sku());
						//error_log("Product SKU: ".$product->get_sku(), 0);
						$centinelClient->add('Item_Price_'.$item_loop, number_format( ($order->get_item_total( $item, true, true ) * 100 ), 2, '.', '' ));
						$centinelClient->add('Item_Quantity_'.$item_loop, $item['qty']);
						$centinelClient->add('Item_Desc_'.$item_loop, $item['name']);
					}
				}
				
				
				
				//$centinelClient->add('Item_Name_1', "");
				//$centinelClient->add('Item_SKU_1', "");
				//$centinelClient->add('Item_Price_1', "");
				//$centinelClient->add('Item_Quantity_1', "");
				//$centinelClient->add('Item_Desc_1', "");
				
				// Recurring
				$centinelClient->add('Recurring', "N");
				$centinelClient->add('RecurringFrequency', "");
				$centinelClient->add('RecurringEnd', "");
				$centinelClient->add('Installment', "");
				
				// Payer Authentication specific fields
				$centinelClient->add('CardNumber', $_POST['c3d_card_number']);
				$centinelClient->add('CardExpMonth', $_POST['c3d_expr_mm']);
				$centinelClient->add('CardExpYear', $_POST['c3d_expr_yyyy']);
				/*$centinelClient->add('Password', $_POST['c3d_cvv']);*/
				$centinelClient->sendHttp(CENTINEL_MAPS_URL, CENTINEL_TIMEOUT_CONNECT, CENTINEL_TIMEOUT_READ);
				// Save response in session
				/*$_SESSION["Centinel_cmpiMessageResp"]   = $centinelClient->response; // Save lookup response in session
				$_SESSION["Centinel_Enrolled"]          = $centinelClient->getValue("Enrolled");
				$_SESSION["Centinel_TransactionId"]     = $centinelClient->getValue("TransactionId");
				$_SESSION["Centinel_OrderId"]           = $centinelClient->getValue("OrderId");
				$_SESSION["Centinel_ACSUrl"]            = $centinelClient->getValue("ACSUrl");
				$_SESSION["Centinel_Payload"]           = $centinelClient->getValue("Payload");
				$_SESSION["Centinel_ErrorNo"]           = $centinelClient->getValue("ErrorNo");
				$_SESSION["Centinel_ErrorDesc"]         = $centinelClient->getValue("ErrorDesc");*/
				
				WC()->session->set( 'Centinel_ACSUrl', $centinelClient->getValue("ACSUrl") );
				WC()->session->set( 'Centinel_ErrorNo', $centinelClient->getValue("ErrorNo") );
				WC()->session->set( 'Centinel_ErrorDesc', $centinelClient->getValue("ErrorDesc") );
				WC()->session->set( 'Centinel_TransactionId', $centinelClient->getValue("TransactionId") );
				WC()->session->set( 'Centinel_OrderId', $centinelClient->getValue("OrderId") );
				WC()->session->set( 'Centinel_Enrolled', $centinelClient->getValue("Enrolled") );
				WC()->session->set( 'Centinel_Payload', $centinelClient->getValue("Payload") );
				WC()->session->set( 'Centinel_cmpiMessageResp', $centinelClient->response );				
				WC()->session->set( 'Centinel_card_start_month', $_POST['c3d_expr_mm'] );
				WC()->session->set( 'Centinel_card_start_year', $_POST['c3d_expr_yyyy'] );
				
				/*error_log("Request: ".centinelClient->getRequestXML, 0);*/
				$dataRequest = $centinelClient->getRequestXml(CENTINEL_MAPS_URL, CENTINEL_TIMEOUT_READ);
				
				// Needed for the cmpi_authenticate message
				//$_SESSION["Centinel_TransactionType"] = "C";
				WC()->session->set( 'Centinel_TransactionType', "C" );
				$termUrl = get_home_url()."/?wc-api=" . strtolower(get_class($this));
				$termUrl = preg_replace("/^http:/i", "https:", $termUrl);
				
				// Add TermUrl to session
				//$_SESSION["Centinel_TermUrl"] = $termUrl;
				WC()->session->set( 'Centinel_TermUrl', $termUrl );
				//TODO: fix with additional else					
				
				if( (strcasecmp('Y', WC()->session->get( 'Centinel_Enrolled' )) == 0) && (strcasecmp('0', WC()->session->get( 'Centinel_ErrorNo' )) == 0) ) {					
					error_log("Enrolled ".WC()->session->get( 'Centinel_Enrolled' )." and Error No is ".WC()->session->get( 'Centinel_ErrorNo' )." .", 0);
					// Proceed with redirect				
					error_log("termUrl: ".$termUrl, 0);
					//$_SESSION["Message"] = "Proceed with redirect (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])";
					WC()->session->set( 'Message', "Proceed with redirect (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])" );
					/*return array(
						'result'   => 'success',
						'redirect' => $this->this->get_return_url( $order ) 
					);*/
					
					return array(
						'result'   => 'success',
						'redirect' => add_query_arg( array(
											'acs' => $order_id
										), $order->get_checkout_payment_url( true ) )
					);
					
					
				} else {
					
					if( (strcasecmp('N', WC()->session->get( 'Centinel_Enrolled' )) == 0) && (strcasecmp('0', WC()->session->get( 'Centinel_ErrorNo' ) ) == 0) ) {
						// Card not enrolled, continue to authorization	
						error_log("Enrolled ".WC()->session->get( 'Centinel_Enrolled' )." and Error No is ".WC()->session->get( 'Centinel_ErrorNo' )." .", 0);					
						//$_SESSION["Message"] = "Card not enrolled, continue to authorization (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])";
						WC()->session->set( 'Message', "Card not enrolled, continue to authorization (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])" );
					
						$error = "Card not enrolled";
						//redirectBrowser('ccResults.php');
						
					} else if( (strcasecmp('U', WC()->session->get( 'Centinel_Enrolled' )) == 0) && (strcasecmp('0', WC()->session->get( 'Centinel_ErrorNo' )) == 0) ) {
						// Authentication unavailable, continue to authorization	
						error_log("Enrolled ".WC()->session->get( 'Centinel_Enrolled' )." and Error No is ".WC()->session->get( 'Centinel_ErrorNo' )." .", 0);					
						//$_SESSION["Message"] = "Authentication unavailable, continue to authorization (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])";
						WC()->session->set( 'Message', "Authentication unavailable, continue to authorization (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])" );
					
						$error = "Authentication unavailable";
						//redirectBrowser('ccResults.php');
					} else {					
						error_log("Unable to complete transaction.", 0);
						$error = "Unable to complete transaction.";
						// Authentication unable to complete, continue to authorization 
						//$_SESSION["Message"] = "Authentication unable to complete, continue to authorization (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])";
						WC()->session->set( 'Message', "Authentication unable to complete, continue to authorization (ErrorNo: [".WC()->session->get( 'Centinel_ErrorNo' )."], ErrorDesc: [".WC()->session->get( 'Centinel_ErrorDesc' )."])" );
					
						//redirectBrowser('ccResults.php');
						error_log("Error # ".WC()->session->get( 'Centinel_ErrorNo' )." and Error Desc is ".WC()->session->get( 'Centinel_ErrorDesc' )." .", 0);					
						error_log("Authentication unable to complete, continue to authorization", 0);
					}
					
					if( $this->woo_version >= 2.1 ){
						wc_add_notice( __( 'Error in 3D secure authentication: . '.$error.' '.WC()->session->get( 'Centinel_ErrorDesc' ).'.', 'centinel_pnp_majobytes' ), $notice_type = 'error' );
					}else if( $woo_version < 2.1 ){
						$woocommerce->add_error( __( 'Error in 3D secure authentication: . '.$error.' '.WC()->session->get( 'Centinel_ErrorDesc' ).'.', 'centinel_pnp_majobytes' ) );
					}else{
						$woocommerce->add_error( __( 'Error in 3D secure authentication: . '.$error.' '.WC()->session->get( 'Centinel_ErrorDesc' ).'.', 'centinel_pnp_majobytes' ) );
					}
					return;
				} // end processing logic
				//add checking code here
				
				/*if successful
				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				$woocommerce->cart->empty_cart();

				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order )
				);
				$order->payment_complete();
				*/
			}
			
			private function currencyCode($wooCurrency){
				
			}
			
			/**
			 * clear_centinel_session function.
			 */
			public function clear_centinel_session() {
				WC()->session->set( 'Centinel_ErrorNo', null );
				WC()->session->set( 'Centinel_ErrorDesc', null );
				WC()->session->set( 'Centinel_TransactionId', null );
				WC()->session->set( 'Centinel_OrderId', null );
				WC()->session->set( 'Centinel_Enrolled', null );
				WC()->session->set( 'Centinel_ACSUrl', null );
				WC()->session->set( 'Centinel_Payload', null );
				WC()->session->set( 'Centinel_cmpiMessageResp', null );
				WC()->session->set( 'Centinel_TransactionType', null );
				WC()->session->set( 'TermUrl', null );
				WC()->session->set( 'Message', null );	
				WC()->session->set( 'Card_start_month', null );
				WC()->session->set( 'Card_start_year', null );				
			}
	
			private function isCreditCardNumber($toCheck)
			{
				if (!is_numeric($toCheck)){
					return false;					
				}
				$number = preg_replace('/[^0-9]+/', '', $toCheck);
				$strlen = strlen($number);
				$sum    = 0;

				if ($strlen < 13){
					return false;
				}

				for ($i=0; $i < $strlen; $i++)
				{
					$digit = substr($number, $strlen - $i - 1, 1);
					if($i % 2 == 1)
					{
						$sub_total = $digit * 2;
						if($sub_total > 9)
						{
							$sub_total = 1 + ($sub_total - 10);
						}
					}
					else
					{
						$sub_total = $digit;
					}
					$sum += $sub_total;
				}

				if ($sum > 0 AND $sum % 10 == 0){					
					return true;
				}
				return false;
			}
			
			private function isCorrectExpireDate($month, $year)
			{
				$now       = time();
				$result    = false;
				$thisYear  = (int)date('y', $now);
				$thisMonth = (int)date('m', $now);

				if (is_numeric($year) && is_numeric($month))
				{
					if($thisYear == (int)$year)
					{
						$result = (int)$month >= $thisMonth;
					}			
					else if($thisYear < (int)$year)
					{
						$result = true;
					}
				}

				return $result;
			}
			
			private function getCurrencyCode($key = 'JMD'){
				//abc used as some sites use abc for jamaican
				$currencyArray=array(
					'ABC'=>'388',
					'JMD'=>'388',
					'USD'=>'840',
					'AUD'=>'036',
					'CAD'=>'124',
					'EUR'=>'978',
					'GBP'=>'826',
					'JPY'=>'392',
					'CZK'=>'203',
					'DKK'=>'208',
					'HKD'=>'344',
					'HUF'=>'348',
					'ILS'=>'376',
					'MXN'=>'484',
					'NOK'=>'578',
					'NZD'=>'554',
					'PLN'=>'985',
					'SGD'=>'702',
					'SEK'=>'752',
					'CHF'=>'756'				
				);
				
				$currencyValue = $currencyArray[$key];
				
				//error_log("Currency Value of ".$key." is ".$currencyValue,0);
				return $currencyValue;
			}
			
			function get_woo_version() {
				
				// If get_plugins() isn't available, require it
				if ( ! function_exists( 'get_plugins' ) )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				
				// Create the plugins folder and file variables
				$plugin_folder = get_plugins( '/woocommerce' );
				$plugin_file = 'woocommerce.php';
				
				// If the plugin version number is set, return it 
				if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
					return $plugin_folder[$plugin_file]['Version'];
			
				} else {
					// Otherwise return null
					return NULL;
				}
			}
			
			
			
			
		}
		
		/**
		 * Add the gateway to WooCommerce
		 **/
		function add_centinel_pnpdirect( $methods ) {
			$methods[] = 'centinel_pnpdirect'; 
			return $methods;
		}
		
		/*
		 * Add shipping phone number to order form
		 */
		function custom_override_checkout_fields( $fields ) {
			error_log("Added shipping field function", 0);
			 $fields['shipping']['shipping_phone'] = array(
				'label'     => __('Shipping Address Phone Number', 'woocommerce'),
			'placeholder'   => _x('Phone', 'placeholder', 'woocommerce'),
			'required'  => true,
			'class'     => array('form-row-first'),
			'clear'     => true
			 );

			 return $fields;
		}
		
		/*
		 * Display Shipping Phone number on order results page
		 */
		function my_custom_checkout_field_display_admin_order_meta($order){
			echo '<p><strong>'.__('Shipping Phone').':</strong> ' . get_post_meta( $order->id, '_shipping_phone', true ) . '</p>';
		}
		
		add_filter('woocommerce_payment_gateways', 'add_centinel_pnpdirect' );
		add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );
 } 
