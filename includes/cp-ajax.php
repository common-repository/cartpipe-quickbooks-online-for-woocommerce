<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CP AJAX
 *
 * AJAX Event Handler
 *
 * @class 		CP_AJAX
  */
class CP_AJAX {
	public static function init() {
		$ajax_events = array(
			'cp_transfer_order' 		=> false, 
			'cp_transfer_single_order'	=> false,
			'cp_sync_product'			=> false,
			'cp_sync_start'				=> false,  
			'cp_update_product'			=> false,
			'cp_transfer_order_qbo'		=> false,
			'cp_refresh_taxrates'		=> false,
			'cp_refresh_taxcodes'		=> false,
			'cp_refresh_payments'		=> false,
			'cp_refresh_accounts'		=> false,
			'cp_release_queue'			=> false,
			'cp_resend_order_qbo'		=> false,
			'cp_break_sync'				=> false,
			'cp_add_message'			=> false,
			'cp_hide_messages'			=> false,
			'cp_deactivate_license'		=> false,
			'cp_activate_license'		=> false,
			'cp_recheck_license'		=> false,
			'cp_import'					=> false,
			'cp_export'					=> false,
			'cp_generate_pdf'			=> false,
			'cp_create_account'			=> false,
			'cp_signup_account'			=> false,
			'cp_signup_payment'			=> false,
			'cp_signup_license'			=> false,
			'cp_activate_free_trial'	=> false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}
	public static function cp_signup_account(){
		check_ajax_referer( 'cp-setup', 'security' );
		$data['email'] 		= $_POST['email'];
		$data['password']	= $_POST['password'];
		$user 				= CP()->client->get_account( $data );
		update_option('cp_create_account', $user);
		if( $user->errors){
			update_option('cp_create_account', 'yes');
		}else{
			if( $user->user ){
				$cp_signup_data 		= array(
					'user'	=> $user->user,
					'email'	=> $user->email,
				);
				update_option('cp_signup', $cp_signup_data);
				$cp_signup_data 		= array(
					'user'	=> $user->user,
					'email'	=> $user->email,
					'status'=> $user->status,
				);
			}
		}
		echo json_encode( $cp_signup_data );
		die(); 
	}
	public static function cp_signup_payment(){
		check_ajax_referer( 'cp-setup', 'security' );
		$cp_signup_data = maybe_unserialize( get_option('cp_signup' ) );
		$data 			= array(
							'user'	=>$cp_signup_data['user'],
							'email'	=>$cp_signup_data['email']
						);
		$payment 		= CP()->client->get_payment( $data );
		update_option('cp_create_payment',$payment );
		if( $payment->errors){
			
		}else{
			if( $payment->account ){
				$cp_signup_data = array(
					'user'		=> $payment->user,
					'email'		=> $payment->email,
					'account'	=> $payment->account
				);
				update_option('cp_signup', $cp_signup_data);
				$cp_signup_data = array(
					'user'		=> $payment->user,
					'email'		=> $payment->email,
					'status'	=> $payment->status,
					'account'	=> $payment->account
				);
			}
		}
		echo json_encode( $cp_signup_data );
		die(); 
	}
	public static function cp_signup_license(){
		check_ajax_referer( 'cp-setup', 'security' );
		$cp_signup_data = maybe_unserialize( get_option('cp_signup') );
		$qbo 			= maybe_unserialize( get_option('qbo') );
		$data 			= array(
							'user'		=>$cp_signup_data['user'],
							'email'		=>$cp_signup_data['email'],
							'account'	=> $cp_signup_data['account']
						);
		$license 		= CP()->client->get_license( $data );
		update_option('cp_create_license', $license );
		if( $license->errors){
			
		}else{
			if( $license->license ){
				$cp_signup_data = array(
					'license'		=> $license->license,
					'consumer_key'	=> $license->consumer_key,
					'consumer_secret'	=> $license->consumer_secret,
					'status'		=> $license->status,
				);
				$client 				= new CP_Client( $license->consumer_key, $license->consumer_secret, CP_API );
				$license_data 			= $client->cp_activate_license( $license->license, get_home_url() );
				$qbo['license_info']	= 	$license_data;
				$qbo['license']			=  $license->license;
				$qbo['consumer_key']	=  $license->consumer_key;
				$qbo['consumer_secret']	=  $license->consumer_secret;
				update_option('qbo', $qbo);
				delete_option('cp_signup');
			}
		}
		CP()->init();
		echo json_encode( $license );
		die(); 
	}
	public static function cp_hide_messages(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		update_option('cp_hide_messages', 'yes');
		die();
	}
	public static function cp_activate_free_trial(){
		check_ajax_referer( 'cp-setup', 'security' );
		CP()->init_client();
		//if( CP()->qbo->license_info->status=="valid" || CP()->qbo->license_info->status == 'site_inactive' || CP()->qbo->license_info->status == 'inactive' ):
			$client 				= CP()->client;
			$license_data 			= $client->cp_activate_license( CP()->qbo->license, get_home_url() );
			$qbo 					= maybe_unserialize( get_option('qbo') );
			$qbo['license_info']	= $license_data;
			update_option('qbo', $qbo);
			CP_Messages::add_message('Your license for Cartpipe.com has been activated.');
			
		//endif;
		
		echo ( json_encode(CP()->qbo->license_info ) );
		die();
	}
	public static function cp_activate_license(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		
		//if( CP()->qbo->license_info->status=="valid" || CP()->qbo->license_info->status == 'site_inactive' || CP()->qbo->license_info->status == 'inactive' ):
			$client 				= CP()->client;
			$license_data 			= $client->cp_activate_license( CP()->qbo->license, get_home_url() );
			$qbo 					= maybe_unserialize( get_option('qbo') );
			$qbo['license_info']	= $license_data;
			update_option('qbo', $qbo);
			CP_Messages::add_message('Your license for Cartpipe.com has been activated.');
			
		//endif;
		
		echo ( json_encode(CP()->qbo->license_info ) );
		die();
	}
	public static function cp_recheck_license(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		$client 				= CP()->client;
		$license_data 			= $client->check_service( CP()->qbo->license, get_home_url() );
		$qbo 					= maybe_unserialize( get_option('qbo') );
		$qbo['license_info']	= $license_data;
		update_option('qbo', $qbo);
		die();
	}
	public static function cp_deactivate_license(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		//if(CP()->qbo->license_info->status=="valid" ||  CP()->qbo->license_info->status=="site_inactive" || CP()->qbo->license_info->status == 'inactive' ):
			$client 				= CP()->client;
			$license_data 			= $client->cp_deactivate_license( CP()->qbo->license, get_home_url() );
			$qbo 					= maybe_unserialize( get_option('qbo') );
			$qbo['license_info']	= $license_data;
			update_option('qbo', $qbo);
			CP_Messages::add_message('Your license for Cartpipe.com has been deactivated.');
		//endif;
			echo ( json_encode($license_data ) );
		die();
	}
	public static function cp_add_message(){
		//check_ajax_referer( 'transfer-order-qbo', 'security' );
		$message   = $_POST['message'];
		CP_Messages::add_message($message);
		echo $message;
		die();
	}
	public static function cp_refresh_payments(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid" ):
			delete_option('qbo_payment_methods');
			$payment_methods = CP()->client->qbo_get_payment_methods( CP()->qbo->license  );
			update_option('qbo_payment_methods', $payment_methods);
		endif;
		die();
	}
	public static function cp_create_account( ){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid" ):
			$data			= array();
			$data['type']  	= $_POST['type'];
			$data['name'] 	= $_POST['name'];
			if($data['name']!='' && $data['type'] !=''){
				delete_option('qbo_accounts_'.$data['type'], false);
				$new_account = CP()->client->qbo_add_account( CP()->qbo->license, $data  );
				if(!$new_account->error){
						
					$accounts = CP()->client->qbo_get_filtered_accounts( CP()->qbo->license, $data['type']  );
					
					update_option('qbo_accounts_'.$data['type'], $accounts);
					
					$qbo = maybe_unserialize( get_option('qbo') );
					
					$qbo[$data['type'].'_account'] = key( (array) $new_account );
					
					update_option( 'qbo', $qbo ); 
					
					CP_Messages::add_message( sprintf( 'A <em>%s</em> account called <em>%s</em> was successfully created in QuickBooks', ucwords( $data['type'] ), $data['name'] ) );
				}else{
					CP_Messages::add_message( $new_account->error );	
				}
			}else{
				echo json_encode($accounts );
				CP_Messages::add_message('Please enter an account name.');
			}
			echo json_encode($accounts);
		endif;
		die();
	}
	public static function cp_refresh_accounts(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid" ):
			$types		= array('income', 'expense', 'asset', 'deposit', 'discount');
			foreach($types as $type){
				delete_option('qbo_accounts_'.$type, false);
				$accounts = CP()->client->qbo_get_filtered_accounts( CP()->qbo->license, $type  );
				update_option('qbo_accounts_'.$type, $accounts);
			}
		endif;
		
		die();
	}
	public static function cp_refresh_taxcodes(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$codes = CP()->client->qbo_get_sales_tax_codes( CP()->qbo->license  );
			update_option('qbo_sales_tax_codes', $codes);
		endif;
		die();
	}
	public static function cp_refresh_taxrates(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$info = CP()->client->qbo_get_sales_tax_info( CP()->qbo->license );
			update_option('qbo_sales_tax_info', $info);
		endif;
		die();
	}
	public static function cp_transfer_single_order(){
		check_ajax_referer( 'transfer-order', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$post_id   	= (int) $_POST['post_id'];
			$data 		= array();
			$order 		= wc_get_order( $post_id );
			$data		= array(
				'billing_first_name'	=> $order->billing_first_name,
				'billing_last_name'		=> $order->billing_last_name,
				'billing_company'		=> $order->billing_company,
				'billing_phone'			=> $order->billing_phone,
				'billing_city'			=> $order->billing_city,
				'billing_state'			=> $order->billing_state,
				'billing_postcode'		=> $order->billing_postcode,
				'billing_email'			=> $order->billing_email,
				'shipping_first_name'	=> $order->shipping_first_name,
				'shipping_last_name'	=> $order->shipping_last_name,
				'shipping_company'		=> $order->shipping_company,
				'shipping_address_1'	=> $order->shipping_address_1,
				'shipping_address_2'	=> $order->shipping_address_2,
				'shipping_city'			=> $order->shipping_city,
				'shipping_state'		=> $order->shipping_state,
				'shipping_postcode'		=> $order->shipping_postcode,
			);
			$customer_id 	= get_post_meta( $post_id, '_qbo_cust_id', true );
			if( !$customer_id ){
				$qbo  = CP()->client->qbo_add_customer( $post_id, $data, CP()->qbo->license );
				if($qbo->qbo_cust_id){
					update_post_meta( $post_id, '_qbo_cust_id', $qbo->qbo_cust_id );
					CP()->sod_qbo_send_order( $post_id );
				}
			}else{
				CP()->sod_qbo_send_order( $post_id );
			}
			$notices	= get_option('cp_admin_notices', array());
			$notices[]	= 'Order #' . $post_id . ' has been queued to send to QuickBooks.';
			update_post_meta( $post_id, '_cp_is_queued', 'yes');
			update_option('cp_admin_notices', $notices);
			// Quit out
		endif;
		die();
	}
	public static function cp_transfer_order(){
		check_ajax_referer( 'transfer-order', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$post_id   = (int) $_POST['post_id'];
			$customer_id 	= get_post_meta( $post_id, '_qbo_cust_id', true );
			if( !$customer_id ){
				$qbo  = CP()->client->qbo_add_customer( $post_id, $posted );
				if($qbo->qbo_cust_id){
					update_post_meta( $post_id, '_qbo_cust_id', $qbo->qbo_cust_id );
					CP()->sod_qbo_send_order( $post_id );
				}
			}else{
				CP()->sod_qbo_send_order( $post_id );
			}
			$notices	= get_option('cp_admin_notices', array());
			$notices[]	= 'Order #' . $post_id . ' has been queued to send to QuickBooks.';
			update_post_meta( $post_id, '_cp_is_queued', 'yes');
			update_option('cp_admin_notices', $notices);
			// Quit out
		endif;
		die();
	}
	public static function cp_resend_order_qbo(){
		check_ajax_referer( 'transfer-order', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$post_id   = (int) $_POST['post_id'];
			$customer_id 	= get_post_meta( $post_id, '_qbo_cust_id', true );
			if( !$customer_id ){
				$qbo  = CP()->client->qbo_add_customer( $post_id, $posted );
				if($qbo->qbo_cust_id){
					update_post_meta( $post_id, '_qbo_cust_id', $qbo->qbo_cust_id );
					CP()->sod_qbo_send_order( $post_id, true );
				}
			}else{
				CP()->sod_qbo_send_order( $post_id, true );
			}
			$notices	= get_option('cp_admin_notices', array());
			$notices[]	= 'Order #' . $post_id . ' has been queued to resend to QuickBooks.';
			update_post_meta( $post_id, '_cp_is_queued', 'yes');
			update_option('cp_admin_notices', $notices);
			CPM()->add_message( 'Order #' . $post_id . ' has been queued to resend to QuickBooks');
			// Quit out
		endif;
		
		die();
	}
	public static function cp_transfer_order_qbo() {
		if(CP()->qbo->license_info->status=="valid"):
			//check_ajax_referer( 'transfer_order_nonce', 'security' );
		//if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'cp-transfer-order-qbo' ) ) {
			$order_id = absint( $_POST['order_id'] );
			if($order_id > 0){
				CP_Messages::add_message('Order #' . $order_id . ' has been queued to send to QuickBooks.');
				CP()->sod_qbo_send_order( $order_id );
			}
			
		//}
		
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		endif;
		die();
	}
	public static function cp_sync_start(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			CP()->cp_queue_inventory( );
		endif;
		// Quit out
		die();
	}
	public static function cp_import(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$id = CP()->cp_import_inventory( );
			//echo $id;
		endif;
		die();
	}
	public static function cp_export(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$id = CP()->cp_export_inventory( );
			//echo $id;
		endif;
		die();
	}
	public static function cp_generate_pdf(){
		check_ajax_referer( 'cp-options-nonce', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$id = CP()->get_pdfs( );
			//echo $id;
		endif;
		die();
	}
	public static function cp_break_sync(){
		check_ajax_referer( 'sync-product', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$post_id   = (int) $_POST['post_id'];
			delete_post_meta( $post_id, 'qbo_data' );
		endif;
		die();
	}
	public static function cp_sync_product(){
		check_ajax_referer( 'sync-product', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$post_id   = (int) $_POST['post_id'];
			if ( $post_id > 0 ) {
				$queue_id = CP()->cp_queue_product( $post_id );
			};
		endif;
		//wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=product' ) );
	}
	public static function cp_update_product(){
		
		check_ajax_referer( 'sync-product', 'security' );
		if(CP()->qbo->license_info->status=="valid"):
			$data = array(
				'desc'	=> $_POST['qb_desc'],
				'id'	=> $_POST['qb_id'],
				'name'	=> $_POST['qb_name'],
				'price'	=> $_POST['qb_price'],
				'status'=> $_POST['qb_status'],
				'tax'	=> $_POST['qb_taxable'],
				'type'	=> $_POST['qb_type'],
			);
			//echo json_encode($data);
			$qbo = maybe_unserialize(  CP()->client->qbo_update_item( $data ) );
			echo json_encode($qbo);
		endif;
		die();
		
		
	}
	public static function cp_release_queue(){
		check_ajax_referer( 'transfer-order', 'security' );
		update_option('cp_is_working', 'no');
		echo 'is released';
		die();
	}
}
CP_AJAX::init();
