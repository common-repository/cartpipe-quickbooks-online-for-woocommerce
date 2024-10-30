<?php 
	$data 					= array();
	$refund 				= wc_get_order( $ref_id );
	$order 					= wc_get_order( $refund->post->post_parent );
	$items 					= $refund->get_items();
	$taxes 					= $refund->get_taxes();
	$shipping				= $refund->get_shipping_methods(); 
	$discount 				= $WC_Order->get_total_discount();
	$qbo_tax_codes 			= CP()->qbo->tax_codes;
	$qbo_id 				= false;
	
	foreach($items as $key=>$value){
		if($value['tax_class'] == ''){
			$value['tax_class'] = 'standard';
		}
		if(sizeof($qbo_tax_codes) > 0){
			$qbo_tax_code = $qbo_tax_codes[$value['tax_class']];
		}
		if(isset( $value['variation_id'] ) && absint($value['variation_id']) > 0 ){
			$qbo_id = get_post_meta( $value['variation_id'], 'qbo_product_id', true );
		}elseif(( $value['product_id'] ) ){
			$qbo_id = get_post_meta( $value['product_id'], 'qbo_product_id', true );
		}; 
		$new_items[]= array(
			'name' 			=> isset($value['variation_id']) && ($value['variation_id'] > 0 ) ? get_post_meta( $value['variation_id'], '_sku', true ) : $value['name'],
			'qty'			=> $value['qty'],
			'sku'			=> isset($value['variation_id']) && ($value['variation_id'] > 0 ) ? get_post_meta( $value['variation_id'], '_sku', true ) : get_post_meta( $value['product_id'], '_sku', true ) ,
			'tax_class'		=> $value['tax_class'],
			'web_id'		=> isset($value['variation_id']) && ($value['variation_id'] > 0 ) ? $value['variation_id'] : $value['product_id'],
			'subtotal' 		=> $value['line_total'],
			'qbo_product_id'=> $qbo_id ? $qbo_id : '',
			'qbo_tax_code'	=> $qbo_tax_code
		);
		
	};
	$order_items				= json_encode( $new_items );
	
	$reference_items				= json_encode( $new_items );
	$data = array(
			'order_id'				=> $ref_id,
			'refRenumber' 			=> $refund->get_order_number(),
			'txnTime' 				=> $refund->post->post_date,
			'billing_first_name'	=> $order->billing_first_name,
			'billing_last_name'		=> $order->billing_last_name,
			'billing_address_1'		=> $order->billing_address_1,
			'billing_address_2'		=> $order->billing_address_2,
			'billing_city'			=> $order->billing_city,			
			'billing_state'			=> $order->billing_state,
			'billing_postcode'		=> $order->billing_postcode,
			'shipping_first_name'	=> $order->shipping_first_name,
			'shipping_last_name'	=> $order->shipping_last_name,
			'shipping_address_1'	=> $order->shipping_address_1,
			'shipping_address_2'	=> $order->shipping_address_2,
			'shipping_city'			=> $order->shipping_city,
			'shipping_state'		=> $order->shipping_state,
			'shipping_postcode'		=> $order->shipping_postcode,
			'qbo_cust_id'			=> get_post_meta( $ref_id, '_qbo_cust_id', true),
			'order_items' 			=> $new_items,
			'order_total'			=> $refund->get_total(),
			'order_subtotal'		=> $refund->get_subtotal(),
			'order_discount'		=> $discount,
			'discount_account'		=> CP()->qbo->discount_account,
			'taxes'					=> $taxes,
			'deposit_account'		=> CP()->qbo->deposit_account,
			'posting_type'			=> 'refund'
			
			
	);
	
	if(sizeof($taxes) > 0){
		$data['taxes'] = $taxes;
	}
	if(sizeof($shipping) > 0){
		$shipping_amount	= 0;
		foreach($shipping as $method){
			$shipping_amount += (float) $method['cost'];	
			$data['shipping_method'] = $method['name'];
		}
		$data['shipping_amount'] = $shipping_amount;
		$data['shipping_taxcode'] = CP()->qbo->shipping_item_taxcode;
		$data['foreign_shipping_taxcode'] = CP()->qbo->foreign_shipping_item_taxcode;
	}
	
	$qbo 			= CP()->client->qbo_add_order( $ref_id, cpencode( $data ), CP()->qbo->license );
	if($qbo){
		update_post_meta( $ref_id , 'qbo_refund_number', $qbo);
		wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );
	}

	return $qbo;