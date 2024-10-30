<?php 
	$data 					= array();
	$WC_Order 				= wc_get_order( $ref_id );
	$wc_payment_method 		= get_post_meta($ref_id, '_payment_method', true );
	$qbo_pay_methods 		= CP()->qbo->payment_methods;
	$discount 				= $WC_Order->get_total_discount();
	if(sizeof($qbo_pay_methods) > 0){
		$qbo_pay_method = $qbo_pay_methods[$wc_payment_method];
	}
	$data = array(
			'order_id'				=> $ref_id,
			'refRenumber' 			=> $WC_Order->get_order_number(),
			'txnTime' 				=> $WC_Order->post->post_date,
			'qbo_cust_id'			=> get_post_meta( $ref_id, '_qbo_cust_id', true),
			'qbo_invoice_number'	=> get_post_meta( $ref_id, '_qbo_invoice_number', true),
			'order_total'			=> $WC_Order->get_total(), //+ $WC_Order->get_total_tax(),
			'payment_method'		=> $qbo_pay_method,
			'deposit_account'		=> CP()->qbo->deposit_account,
			'posting_type'			=> 'payment',
			'order_discount'		=> $discount,
			'discount_account'		=> CP()->qbo->discount_account,
	);
	
	$qbo = CP()->client->qbo_add_order( $ref_id, cpencode( $data ), CP()->qbo->license );
	
	
	if($qbo->data){
		$qbo->has_transferred = true;
		$data 				= (array) maybe_unserialize( get_post_meta( $ref_id, '_quickbooks_data', true) );
		$data['payment'] 	= $qbo;
		update_post_meta( $ref_id , '_quickbooks_data', maybe_serialize( $data ) );
		update_post_meta( $ref_id , '_qbo_payment_number', $qbo->data );
		wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );
	}else{
		CP()->cp_insert_fallout('Order #'.$ref_id,$ref_id, $qbo->errors, 'create-payment', 'order');
		update_post_meta( $ref_id , '_cp_errors', $qbo->errors);
		wp_set_object_terms( $query->post->ID , 'failed', 'queue_status'. false );
	}