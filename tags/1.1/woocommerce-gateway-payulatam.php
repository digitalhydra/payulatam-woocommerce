<?php
/*
Plugin Name: WooCommerce - PayU Latam Gateway
Plugin URI: http://www.thecodeisintheair.com/wordpress-plugins/woocommerce-pa…gateway-plugin/
Description: PayU Latinoamerica Payment Gateway for WooCommerce. Recibe pagos en internet en latinoamérica desde cualquier parte del mundo. ¡La forma más rápida, sencilla y segura para vender y recibir pagos por internet!
Version: 1.1.1
Author: Code is in the Air - Jairo Ivan Rondon Mejia
Author URI: http://www.thecodeisintheair.com/
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

add_action('plugins_loaded', 'woocommerce_payulatam_init', 0);
define('IMGDIR', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');

function woocommerce_payulatam_init(){
	if(!class_exists('WC_Payment_Gateway')) return;

    if( isset($_GET['msg']) && !empty($_GET['msg']) ){
        add_action('the_content', 'showPayuLatamMessage');
    }
    function showPayuLatamMessage($content){
            return '<div class="'.htmlentities($_GET['type']).'">'.htmlentities(urldecode($_GET['msg'])).'</div>'.$content;
    }

    /**
	 * PayU Gateway Class
     *
     * @access public
     * @param 
     * @return 
     */
	class WC_payulatam extends WC_Payment_Gateway{
		
		public function __construct(){
			global $woocommerce;
			$this->load_plugin_textdomain();
	        //add_action('init', array($this, 'load_plugin_textdomain'));

			$this->id 					= 'payulatam';
			$this->icon         		= IMGDIR . 'logo.png';
			$this->method_title 		= __('PayU Latam','payu-latam-woocommerce');
			$this->method_description	= __("The easiest way to sell and recive payments online in latinamerica",'payu-latam-woocommerce');
			$this->has_fields 			= false;
			
			$this->init_form_fields();
			$this->init_settings();
			$this->language 		= get_bloginfo('language');

			$this->testmode 		= $this->settings['testmode'];
			$this->testmerchant_id	= '500238';
			$this->testaccount_id	= '500537';
			$this->testapikey		= '6u39nqhq8ftd0hlvnjfs66eh8c';
			
			$this->title 			= $this->settings['title'];
			$this->description 		= $this->settings['description'];
			$this->merchant_id 		= ($this->testmode=='yes')?$this->testmerchant_id:$this->settings['merchant_id'];
			$this->account_id 		= ($this->testmode=='yes')?$this->testaccount_id:$this->settings['account_id'];
			$this->apikey 			= ($this->testmode=='yes')?$this->testapikey:$this->settings['apikey'];
			$this->redirect_page_id = $this->settings['redirect_page_id'];
			$this->payu_language 	= $this->settings['payu_language'];
			$this->addcop 			= $this->settings['addcop'];
			$this->taxes 			= $this->settings['taxes'];
			$this->tax_return_base 	= $this->settings['tax_return_base'];
			$this->currency 		= ($this->is_valid_currency())?get_woocommerce_currency():'USD';
			$this->textactive 		= 0;
			$this->liveurl 			= 'https://gateway.payulatam.com/ppp-web-gateway/';
			$this->testurl 			= 'https://stg.gateway.payulatam.com/ppp-web-gateway';

			if ($this->testmode == "yes")
				$this->debug = "yes";

			add_filter( 'woocommerce_currencies', 'add_all_currency' );
			add_filter( 'woocommerce_currency_symbol', 'add_all_symbol', 10, 2);

			$this->msg['message'] 	= "";
			$this->msg['class'] 	= "";
			// Logs
			if ( 'yes' == $this->debug )
				$this->log = $woocommerce->logger();
					
			add_action('payulatam_init', array( $this, 'pauylatam_successful_request'));
			add_action( 'woocommerce_receipt_payulatam', array( $this, 'receipt_page' ) );
			//update for woocommerce >2.0
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_payulatam_response' ) );
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				/* 2.0.0 */
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				/* 1.6.6 */
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			
		}

	    public function load_plugin_textdomain()
	    {
			load_plugin_textdomain( 'payu-latam-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );
	    }
    	/**
		 * Settings Options
	     *
	     * @access public
	     * @return void
	     */
		function init_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> __('Enable/Disable', 'payu-latam-woocommerce'),
					'type' 			=> 'checkbox',
					'label' 		=> __('Enable PayU Latam Payment Module.', 'payu-latam-woocommerce'),
					'default' 		=> 'no',
					'description' 	=> __('Show in the Payment List as a payment option', 'payu-latam-woocommerce')
				),
      			'title' => array(
					'title' 		=> __('Title:', 'payu-latam-woocommerce'),
					'type'			=> 'text',
					'default' 		=> __('PayU Latam Online Payments', 'payu-latam-woocommerce'),
					'description' 	=> __('This controls the title which the user sees during checkout.', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
				),
      			'description' => array(
					'title' 		=> __('Description:', 'payu-latam-woocommerce'),
					'type' 			=> 'textarea',
					'default' 		=> __('Pay securely by Credit or Debit Card or Internet Banking through PayU Latam Secure Servers.','payu-latam-woocommerce'),
					'description' 	=> __('This controls the description which the user sees during checkout.', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
				),
      			'merchant_id' => array(
					'title' 		=> __('Merchant ID', 'payu-latam-woocommerce'),
					'type' 			=> 'text',
					'description' 	=> __('Given to Merchant by PayU Latam', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
				),
      			'account_id' => array(
					'title' 		=> __('Account ID', 'payu-latam-woocommerce'),
					'type' 			=> 'text',
					'description' 	=> __('Some Countrys (Brasil, Mexico) require this ID, Gived to you by PayU Latam on regitration.', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
				),
      			'apikey' => array(
					'title' 		=> __('ApiKey', 'payu-latam-woocommerce'),
					'type' 			=> 'text',
					'description' 	=>  __('Given to Merchant by PayU Latam', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
                ),
      			'testmode' => array(
					'title' 		=> __('TEST Mode', 'payu-latam-woocommerce'),
					'type' 			=> 'checkbox',
					'label' 		=> __('Enable PayU Latam TEST Transactions.', 'payu-latam-woocommerce'),
					'default' 		=> 'no',
					'description' 	=> __('Tick to run TEST Transaction on the PayU Latam platform', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
                ),
                'taxes' => array(
					'title' 		=> __('Tax Rate - Read', 'payu-latam-woocommerce').' <a target="_blank" href="http://docs.payulatam.com/manual-integracion-web-checkout/informacion-adicional/tablas-de-variables-complementarias/">PayU Documentacion</a>',
					'type' 			=> 'text',
					'default' 		=> '0',
					'description' 	=> __('Tax rates for Transactions (IVA).', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
		        ),
      			'tax_return_base' => array(
					'title' 		=> __('Tax Return Base', 'payu-latam-woocommerce'),
					'type' 			=> 'text',
					//'options' 		=> array('0' => 'None', '2' => '2% Credit Cards Payments Return (Colombia)'),
					'default' 		=> '0',
					'description' 	=> __('Tax base to calculate IVA ', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
                ),
      			'payu_language' => array(
					'title' 		=> __('Gateway Language', 'payu-latam-woocommerce'),
					'type' 			=> 'select',
					'options' 		=> array('ES' => 'ES', 'EN' => 'EN', 'PT' => 'PT'),
					'description' 	=> __('PayU Latam Gateway Language ', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
                ),
      			'redirect_page_id' => array(
					'title' 		=> __('Return Page', 'payu-latam-woocommerce'),
					'type' 			=> 'select',
					'options' 		=> $this->get_pages(__('Select Page', 'payu-latam-woocommerce')),
					'description' 	=> __('URL of success page', 'payu-latam-woocommerce'),
					'desc_tip' 		=> true
                ),
                'empty_cart' => array(
					'title' 		=> __('Empty Cart after payment completed.', 'payu-latam-woocommerce'),
					'type' 			=> 'checkbox',
					'label' 		=> __('Do you want empty the user shopping cart after the payment is complete?.', 'payu-latam-woocommerce'),
					'default' 		=> 'no',
					'description' 	=> __('Do you want empty the user shopping cart after the payment is complete?.', 'payu-latam-woocommerce')
				),
			);
                
		} 
        /**
         * Generate Admin Panel Options
	     *
	     * @access public
	     * @return string
         **/
		public function admin_options(){
			echo '<h3>'.__('PayU Latam', 'payu-latam-woocommerce').'</h3>';
			echo '<p>'.__('The easiest way to sell and recive payments online in latinamerica', 'payu-latam-woocommerce').'</p>';
			echo '<table class="form-table">';
			// Generate the HTML For the settings form.
			$this->generate_settings_html();
			echo '</table>';
		}
        /**
		 * Generate the PayU Latam Payment Fields
	     *
	     * @access public
	     * @return string
	     */
		function payment_fields(){
			if($this->description) echo wpautop(wptexturize($this->description));
		}
		/**
		* Generate the PayU Latam Form for checkout
	    *
	    * @access public
	    * @param mixed $order
	    * @return string
		**/
		function receipt_page($order){
			echo '<p>'.__('Thank you for your order, please click the button below to pay with PayU Latam.', 'payu-latam-woocommerce').'</p>';
			echo $this->generate_payulatam_form($order);
		}
		/**
		* Generate PayU POST arguments
	    *
	    * @access public
	    * @param mixed $order_id
	    * @return string
		**/
		function get_payulatam_args($order_id){
			global $woocommerce;
			$order = new WC_Order( $order_id );
			$txnid = $order->order_key;
			
			$redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0)?get_site_url() . "/":get_permalink($this->redirect_page_id);
			//For wooCoomerce 2.0
			$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
			$redirect_url = add_query_arg( 'order_id', $order_id, $redirect_url );

			$productinfo = "Order $order_id";
			
				$str = "$this->apikey~$this->merchant_id~$txnid~$order->order_total~$this->currency";
				$hash = strtolower(md5( $str));
			
			
			$payulatam_args = array(
				'merchantId' 		=> $this->merchant_id,
				'accountId' 		=> $this->account_id,
				'signature' 		=> $hash,
				'referenceCode' 	=> $txnid,
				'amount' 			=> $order->order_total,
				'currency' 			=> $this->currency,
				'payerFullName'		=> $order->billing_first_name .' '.$order->billing_last_name,
				'buyerEmail' 		=> $order->billing_email,
				'telephone' 		=> $order->billing_phone,
				'billingAddress' 	=> $order->billing_address_1.' '.$order->billing_address_2,
				'shippingAddress' 	=> $order->billing_address_1.' '.$order->billing_address_2,
				'billingCity' 		=> $order->billing_city,
				'shippingCity' 		=> $order->billing_city,
				'billingCountry' 	=> $order->billing_country,
				'shippingCountry' 	=> $order->billing_country,
				'zipCode' 			=> $order->billing_postcode,
				'lng'				=> $this->payu_language,
				'description'		=> $productinfo,
				'responseUrl' 		=> $redirect_url,
				'confirmationUrl'	=> $redirect_url,
				'tax' 				=> $this->taxes,
				'taxReturnBase'		=> $this->tax_return,
				'extra1'			=> $order->order_id,
				'discount' 			=> '0'
			);
			return $payulatam_args;
		}

		 /**
		 * Generate the PayU Latam button link
	     *
	     * @access public
	     * @param mixed $order_id
	     * @return string
	     */
	    function generate_payulatam_form( $order_id ) {
			global $woocommerce;

			$order = new WC_Order( $order_id );

			if ( $this->testmode == 'yes' ):
				$payulatam_adr = $this->testurl . '?test=1&ApiKey='.esc_attr( $this->testapikey ).'&';
			else :
				$payulatam_adr = $this->liveurl . '?';
			endif;

			$payulatam_args = $this->get_payulatam_args( $order_id );
			$payulatam_args_array = array();

			foreach ($payulatam_args as $key => $value) {
				$payulatam_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}

			$woocommerce->add_inline_js( '
				jQuery("body").block({
						message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to PayU Latam to make payment.', 'payu-latam-woocommerce' ) ) . '",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
					        padding:        "20px",
					        zindex:         "9999999",
					        textAlign:      "center",
					        color:          "#555",
					        border:         "3px solid #aaa",
					        backgroundColor:"#fff",
					        cursor:         "wait",
					        lineHeight:		"24px",
					    }
					});
				jQuery("#submit_payulatam_payment_form").click();
			' );

			return '<form action="'.esc_url( $payulatam_adr ).'" method="post" id="payulatam_payment_form" target="_top">
					' . implode( '', $payulatam_args_array) . '
					<input type="submit" class="button alt" id="submit_payulatam_payment_form" value="' . __( 'Pay via PayU Latam', 'payu-latam-woocommerce' ) . '" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancel order &amp; restore cart', 'woocommerce' ).'</a>
				</form>';

		}

		/**
	     * Process the payment and return the result
	     *
	     * @access public
	     * @param int $order_id
	     * @return array
	     */
		function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );
			if ( ! $this->form_submission_method ) {
				$payulatam_args = $this->get_payulatam_args( $order_id );
				$payulatam_args = http_build_query( $payulatam_args, '', '&' );
				if ( $this->testmode == 'yes' ):
					$payulatam_adr = $this->testurl . '?test=1&&ApiKey='.esc_attr( $this->testapikey ).'&';
				else :
					$payulatam_adr = $this->liveurl . '?';
				endif;

				return array(
					'result' 	=> 'success',
					'redirect'	=> $payulatam_adr . $payulatam_args
				);
			} else {
				return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
				);

			}

		}
		/**
		* Check for valid payu server callback
		*
		* @access public
		* @return void
		**/
		function check_payulatam_response(){
			@ob_clean();
	    	if ( ! empty( $_REQUEST ) ) {
	    		header( 'HTTP/1.1 200 OK' );
	    		print_r($_REQUEST);
	        	do_action( "payulatam_init", $_REQUEST );
			} else {
				wp_die( __("PayU Latam Request Failure", 'payu-latam-woocommerce') );
	   		}
		
		}

		/**
		 * Process Payu Response and update the order information
		 *
		 * @access public
		 * @param array $posted
		 * @return void
		 */
		function pauylatam_successful_request( $posted ) {
			global $woocommerce;
			// Custom holds post ID
		    if ( ! empty( $posted['referenceCode'] ) && ! empty( $posted['order_id'] ) ) {

			    $order = $this->get_payulatam_order( $posted );

			     if ( 'yes' == $this->debug )
		        	$this->log->add( 'payulatam', 'Found order #' . $order->id );

			    // Lowercase returned variables
		        //$posted['lapTransactionState'] 	= ( $posted['lapTransactionState'] );
		        //$posted['lapPaymentMethodType'] = ( $posted['lapPaymentMethodType'] );

		        // Sandbox fix
		        if ( $posted['authorizationCode'] == '123456' && $posted['lapTransactionState'] == 'PENDING' )
		        	$posted['lapTransactionState'] = 'APPROVED';

		        if ( 'yes' == $this->debug )
		        	$this->log->add( 'paypal', 'Payment status: ' . $posted['lapTransactionState'] );
		        if (!empty($posted['transactionState'])) {
			        // We are here so lets check status and do actions
			        switch ( $posted['lapTransactionState'] ) {
			            case 'APPROVED' :
			            case 'PENDING' :
			            case 'PENDING_TRANSACTION_CONFIRMATION' :

			            	// Check order not already completed
			            	if ( $order->status == 'completed' ) {
			            		 if ( 'yes' == $this->debug )
			            		 	$this->log->add( 'payulatam', 'Aborting, Order #' . $order->id . ' is already complete.' );
			            		 exit;
			            	}

							// Validate Amount
						    if ( $order->get_total() != $posted['TX_VALUE'] ) {

						    	if ( 'yes' == $this->debug )
						    		$this->log->add( 'payulatam', __('Payment error: Amounts do not match (Amount ' . $posted['TX_VALUE'] . ')', 'payu-latam-woocommerce') );

						    	// Put this order on-hold for manual checking
						    	$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayU Latam amounts do not match (gross %s).', 'payu-latam-woocommerce'), $posted['TX_VALUE'] ) );

								$this->msg['message'] = sprintf( __( 'Validation error: PayU Latam amounts do not match (gross %s).', 'payu-latam-woocommerce'), $posted['TX_VALUE'] );
								$this->msg['class'] = 'woocommerce-error';	

						    	exit;
						    }

						    // Validate Merchand id 
							if ( strcasecmp( trim( $posted['merchantId'] ), trim( $this->merchant_id ) ) != 0 ) {
								if ( 'yes' == $this->debug )
									$this->log->add( 'payulatam', __("Payment was made to another merchantId: {$posted['merchantId']} our merchantId is {$this->merchant_id }", 'payu-latam-woocommerce') );

								// Put this order on-hold for manual checking
						    	$order->update_status( 'on-hold', sprintf( __( 'Validation error: Payment in PayU Latam comes from another id (%s).', 'payu-latam-woocommerce'), $posted['merchantId'] ) );
								$this->msg['message'] = sprintf( __( 'Validation error: Payment in PayU Latam comes from another id (%s).', 'payu-latam-woocommerce'), $posted['merchantId'] );
								$this->msg['class'] = 'woocommerce-error';

						    	exit;
							}

							 // Store PP Details
			                if ( ! empty( $posted['buyerEmail'] ) )
			                	update_post_meta( $order->id, __('Payer PayU Latam email', 'payu-latam-woocommerce'), $posted['buyerEmail'] );
			                if ( ! empty( $posted['transactionId'] ) )
			                	update_post_meta( $order->id, __('Transaction ID', 'payu-latam-woocommerce'), $posted['transactionId'] );
			                if ( ! empty( $posted['trazabilityCode'] ) )
			                	update_post_meta( $order->id, __('Trasability Code', 'payu-latam-woocommerce'), $posted['trazabilityCode'] );
			                /*if ( ! empty( $posted['last_name'] ) )
			                	update_post_meta( $order->id, 'Payer last name', $posted['last_name'] );*/
			                if ( ! empty( $posted['lapPaymentMethodType'] ) )
			                	update_post_meta( $order->id, __('Payment type', 'payu-latam-woocommerce'), $posted['lapPaymentMethodType'] );

			                if ( $posted['lapTransactionState'] == 'APPROVED' ) {
			                	$order->add_order_note( __( 'PayU Latam payment approved', 'payu-latam-woocommerce') );
								$this->msg['message'] =  __( 'PayU Latam Payment Approved', 'payu-latam-woocommerce') ;
								$this->msg['class'] = 'woocommerce-message';
			                	$order->payment_complete();
			                	if ($this->empty_cart == "yes"){
									$cart = new WC_Cart();
									$cart->empty_cart(true);
			                	}
			                } else {
			                	$order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'payu-latam-woocommerce'), $posted['lapResponseCode'] ) );
								$this->msg['message'] = sprintf( __( 'Payment pending: %s', 'payu-latam-woocommerce'), $posted['lapResponseCode'] ) ;
								$this->msg['class'] = 'woocommerce-info';
			                }

			            	if ( 'yes' == $this->debug )
			                	$this->log->add( 'payulatam', __('Payment complete.', 'payu-latam-woocommerce') );

			            break;
			            case 'DECLINED' :
			            case 'ANTIFRAUD_REJECTED' :
			            case 'INSUFFICIENT_FUNDS' :
			            case 'PAYMENT_NETWORK_REJECTED' :
			            case 'INTERNAL_PAYMENT_PROVIDER_ERROR' :
			            case 'ERROR' :
			            case 'ENTITY_DECLINED' :
			            case 'ENTITY_MESSAGING_ERROR' :
			            case 'NOT_ACCEPTED_TRANSACTION' :
			            case 'BANK_UNREACHABLE' :
			            case 'INVALID_CARD' :
			            case 'INVALID_TRANSACTION' :
			            case 'EXPIRED_CARD' :
			            case 'RESTRICTED_CARD' :
			            case 'INTERNAL_PAYMENT_PROVIDER_ERROR' :
			            case 'INACTIVE_PAYMENT_PROVIDER' :
			            case 'DIGITAL_CERTIFICATE_NOT_FOUND' :
			            case 'INVALID_EXPIRATION_DATE_OR_SECURITY_CODE' :
			            case 'INSUFFICIENT_FUNDS' :
			            case 'CREDIT_CARD_NOT_AUTHORIZED_FOR_INTERNET_TRANSACTIONS' :
			                // Order failed
			                $order->update_status( 'failed', sprintf( __( 'Payment rejected via PayU Latam. Error type: %s', 'payu-latam-woocommerce'), ( $posted['lapTransactionState'] ) ) );
								$this->msg['message'] = sprintf( __( 'Payment rejected via PayU Latam. Error type: %s.', 'payu-latam-woocommerce'), ( $posted['lapTransactionState'] ) )  ;
								$this->msg['class'] = 'woocommerce-error';
			            break;
			            default :
			                $order->update_status( 'failed', sprintf( __( 'Payment rejected via PayU Latam. Error type: %s', 'payu-latam-woocommerce'), ( $posted['lapTransactionState'] ) ) );
								$this->msg['message'] = sprintf( __( 'Payment rejected via PayU Latam. Error type: %s', 'payu-latam-woocommerce'), ( $posted['lapTransactionState'] ) )  ;
								$this->msg['class'] = 'woocommerce-error';
			            break;
			        }
		        	
		        } else if(!empty($posted['transactionState'])) {
		        	$codes=array('1' => 'CAPTURING_DATA' ,'2' => 'NEW' ,'101' => 'FX_CONVERTED' ,'102' => 'VERIFIED' ,'103' => 'SUBMITTED' ,'4' => 'APPROVED' ,'6' => 'DECLINED' ,'104' => 'ERROR' ,'7' => 'PENDING' ,'5' => 'EXPIRED'  );
		        	$state=$posted['transactionState'];
		        	// We are here so lets check status and do actions
			        switch ( $codes[$state] ) {
			            case 'APPROVED' :
			            case 'PENDING' :
			            case 'NEW' :
			            case 'FX_CONVERTED' :
			            case 'VERIFIED' :
			            case 'SUBMITTED' :
			            case 'CAPTURING_DATA' :

			            	// Check order not already completed
			            	if ( $order->status == 'completed' ) {
			            		 if ( 'yes' == $this->debug )
			            		 	$this->log->add( 'payulatam', __('Aborting, Order #' . $order->id . ' is already complete.', 'payu-latam-woocommerce') );
			            		 exit;
			            	}

							// Validate Amount
						    if ( $order->get_total() != $posted['TX_VALUE'] ) {

						    	if ( 'yes' == $this->debug )
						    		$this->log->add( 'payulatam', __('Payment error: Amounts do not match (amount ' . $posted['TX_VALUE'] . ')', 'payu-latam-woocommerce') );

						    	// Put this order on-hold for manual checking
						    	$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayU Latam amounts do not match (gross %s).', 'payu-latam-woocommerce'), $posted['TX_VALUE'] ) );

								$this->msg['message'] = sprintf( __( 'Validation error: PayU Latam amounts do not match (gross %s).', 'payu-latam-woocommerce'), $posted['TX_VALUE'] );
								$this->msg['class'] = 'woocommerce-error';	

						    	exit;
						    }

						    // Validate Merchand id 
							if ( strcasecmp( trim( $posted['merchantId'] ), trim( $this->merchant_id ) ) != 0 ) {
								if ( 'yes' == $this->debug )
									$this->log->add( 'payulatam', __("Payment was made to another merchantId: {$posted['merchantId']} our merchantId is {$this->merchant_id }", 'payu-latam-woocommerce') );

								// Put this order on-hold for manual checking
						    	$order->update_status( 'on-hold', sprintf( __( 'Validation error: Payment in PayU Latam comes from another id (%s).', 'payu-latam-woocommerce'), $posted['merchantId'] ) );
								$this->msg['message'] = sprintf( __( 'Validation error: Payment in PayU Latam comes from another id (%s).', 'payu-latam-woocommerce'), $posted['merchantId'] );
								$this->msg['class'] = 'woocommerce-error';

						    	exit;
							}

							 // Store PP Details
			                if ( ! empty( $posted['buyerEmail'] ) )
			                	update_post_meta( $order->id, __('Payer PayU Latam email', 'payu-latam-woocommerce'), $posted['buyerEmail'] );
			                if ( ! empty( $posted['transactionId'] ) )
			                	update_post_meta( $order->id, __('Transaction ID', 'payu-latam-woocommerce'), $posted['transactionId'] );
			                if ( ! empty( $posted['trazabilityCode'] ) )
			                	update_post_meta( $order->id, __('Trasability Code', 'payu-latam-woocommerce'), $posted['trazabilityCode'] );
			                /*if ( ! empty( $posted['last_name'] ) )
			                	update_post_meta( $order->id, 'Payer last name', $posted['last_name'] );*/
			                if ( ! empty( $posted['lapPaymentMethodType'] ) )
			                	update_post_meta( $order->id, __('Payment type', 'payu-latam-woocommerce'), $posted['lapPaymentMethodType'] );

			                if ( $codes[$state] == 'APPROVED' ) {
			                	$order->add_order_note( __( 'PayU Latam payment approved', 'payu-latam-woocommerce') );
								$this->msg['message'] =  __( 'PayU Latam Payment Approved', 'payu-latam-woocommerce') ;
								$this->msg['class'] = 'woocommerce-message';
			                	$order->payment_complete();
			                } else {
			                	$order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'payu-latam-woocommerce'), $codes[$state] ) );
								$this->msg['message'] = sprintf( __( 'Payment pending: %s', 'payu-latam-woocommerce'), $codes[$state] ) ;
								$this->msg['class'] = 'woocommerce-info';
			                }

			            	if ( 'yes' == $this->debug )
			                	$this->log->add( 'payulatam', __('Payment complete.', 'payu-latam-woocommerce'));

			            break;
			            case 'DECLINED' :
			            case 'EXPIRED' :
			            case 'ERROR' :
			                // Order failed
			                $order->update_status( 'failed', sprintf( __( 'Payment rejected via PayU Latam. Error type: %s', 'payu-latam-woocommerce'), ( $codes[$state] ) ) );
								$this->msg['message'] = sprintf( __( 'Payment rejected via PayU Latam. Error type: %s.', 'payu-latam-woocommerce'), ( $codes[$state] ) )  ;
								$this->msg['class'] = 'woocommerce-error';
			            break;
			            default :
			                $order->update_status( 'failed', sprintf( __( 'Payment rejected via PayU Latam.', 'payu-latam-woocommerce'), ( $codes[$state] ) ) );
								$this->msg['message'] = sprintf( __( 'Payment rejected via PayU Latam.', 'payu-latam-woocommerce'), ( $codes[$state] ) )  ;
								$this->msg['class'] = 'woocommerce-error';
			            break;
			        }
		        }
		        

				$redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0)?get_site_url() . "/":get_permalink($this->redirect_page_id);
                //For wooCoomerce 2.0
                $redirect_url = add_query_arg( array('msg'=> urlencode($this->msg['message']), 'type'=>$this->msg['class']), $redirect_url );

                wp_redirect( $redirect_url );
                exit;
		    }

		}

		/**
		 *  Get order information
		 *
		 * @access public
		 * @param mixed $posted
		 * @return void
		 */
		function get_payulatam_order( $posted ) {
			$custom =  $posted['order_id'];

	    	// Backwards comp for IPN requests
	    	
		    	$order_id = (int) $custom;
		    	$order_key = $posted['referenceCode'];
	    	
			$order = new WC_Order( $order_id );

			if ( ! isset( $order->id ) ) {
				// We have an invalid $order_id, probably because invoice_prefix has changed
				$order_id 	= woocommerce_get_order_id_by_order_key( $order_key );
				$order 		= new WC_Order( $order_id );
			}

			// Validate key
			if ( $order->order_key !== $order_key ) {
	        	if ( $this->debug=='yes' )
	        		$this->log->add( 'paypal', __('Error: Order Key does not match invoice.', 'payu-latam-woocommerce') );
	        	exit;
	        }

	        return $order;
		}

		/**
		 * Check the tax rates active in woocommerce.
		 *
		 * @access public
		 * @return bool
		 */
		/*function get_tax_rates() {
			$taxes = new WC_Tax();
			$countries = new WC_Countries();
			$countrybase=$countries->get_base_country();
			$tax_rates=$taxes->get_shop_base_rate('Standard');
			print_r($tax_rates);
			return $tax_rates;
		}*/

		/**
		 * Check if current currency is valid for PayU Latam
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_currency() {
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_payulatam_supported_currencies', array( 'ARS', 'BRL', 'COP', 'MXN', 'PEN', 'USD' ) ) ) ) return false;

			return true;
		}
		
		/**
		 * Get pages for return page setting
		 *
		 * @access public
		 * @return bool
		 */
		function get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
                	$has_parent = $page->post_parent;
                	while($has_parent) {
                    	$prefix .=  ' - ';
                    	$next_page = get_page($has_parent);
                    	$has_parent = $next_page->post_parent;
                	}
            	}
            	// add to page list array array
            	$page_list[$page->ID] = $prefix . $page->post_title;
        	}
        	return $page_list;
    		}
		}
		/**
		 * Add all currencys supported by PayU Latem so it can be display 
		 * in the woocommerce settings
		 *
		 * @access public
		 * @return bool
		 */
		function add_all_currency( $currencies ) {
			$currencies['ARS'] = __( 'Argentine Peso', 'payu-latam-woocommerce');
			$currencies['BRL'] = __( 'Brasilian Real', 'payu-latam-woocommerce');
			$currencies['COP'] = __( 'Colombian Peso', 'payu-latam-woocommerce');
			$currencies['MXN'] = __( 'Mexican Peso', 'payu-latam-woocommerce');
			$currencies['PEN'] = __( 'Perubian New Sol', 'payu-latam-woocommerce');
			return $currencies;
		}
		/**
		 * Add simbols for all currencys in payu latam so it can be display 
		 * in the woocommerce settings
		 *
		 * @access public
		 * @return bool
		 */
		function add_all_symbol( $currency_symbol, $currency ) {
			switch( $currency ) {
			case 'ARS': $currency_symbol = '$'; break;
			case 'BRL': $currency_symbol = 'R$'; break;
			case 'COP': $currency_symbol = '$'; break;
			case 'MXN': $currency_symbol = '$'; break;
			case 'PEN': $currency_symbol = 'S/.'; break;
			}
			return $currency_symbol;
		}
		/**
		* Add the Gateway to WooCommerce
		**/
		function woocommerce_add_payulatam_gateway($methods) {
			$methods[] = 'WC_payulatam';
			return $methods;
		}

		add_filter('woocommerce_payment_gateways', 'woocommerce_add_payulatam_gateway' );
	}

	/**
	 * Filter simbol for currency currently active so it can be display 
	 * in the front end
     *
     * @access public
     * @param (string) $currency_symbol, (string) $currency
     * @return (string) filtered currency simbol
     */
	function frontend_filter_currency_symbol( $currency_symbol, $currency ) {
		switch( $currency ) {
		case 'ARS': $currency_symbol = '$'; break;
		case 'BRL': $currency_symbol = 'R$'; break;
		case 'COP': $currency_symbol = '$'; break;
		case 'MXN': $currency_symbol = '$'; break;
		case 'PEN': $currency_symbol = 'S/.'; break;
		}
		return $currency_symbol;
	}
	add_filter( 'woocommerce_currency_symbol', 'frontend_filter_currency_symbol', 1, 2);