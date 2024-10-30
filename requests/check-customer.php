<?php 
	$order 					= wc_get_order( $ref_id );
	$posted 				= array(
		'billing_first_name'	=> $order->billing_first_name,
		'billing_last_name'		=> $order->billing_last_name,
		'billing_company'		=> $order->billing_company,
		'billing_address_1'		=> $order->billing_address_1,
		'billing_address_2'		=> $order->billing_address_2,
		'billing_city'			=> $order->billing_city,
		'billing_state'			=> $order->billing_state,
		'billing_postcode'		=> $order->billing_postcode,
		'billing_country'		=> $order->billing_country,
		'billing_email'			=> $order->billing_email,
		'billing_phone'			=> $order->billing_phone,
		'shipping_first_name'	=> $order->shipping_first_name,
		'shipping_last_name'	=> $order->shipping_last_name,
		'shipping_company'		=> $order->shipping_company,
		'shipping_address_1'	=> $order->shipping_address_1,
		'shipping_address_2'	=> $order->shipping_address_2,
		'shipping_city'			=> $order->shipping_city,
		'shipping_state'		=> $order->shipping_state,
		'shipping_postcode'		=> $order->shipping_postcode,
		'shipping_country'		=> $order->shipping_country
		
	);
	
	$qbo  = CP()->client->qbo_add_customer( $ref_id, cpencode( $posted ), CP()->qbo->license );
	if($qbo->qbo_cust_id){
		update_post_meta($ref_id, '_qbo_cust_id',  $qbo->qbo_cust_id );
		wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );
		//CP()->sod_qbo_send_order( $ref_id );
	}else{
		$errors = explode(':', $qbo->errors);
		CP()->cp_insert_fallout('Order #'.$ref_id,$ref_id, $qbo->errors, 'check-customer', 'order');
		update_post_meta( $ref_id , '_cp_errors', $qbo->errors);
		wp_set_object_terms( $query->post->ID , 'failed', 'queue_status'. false );
		
	}