<?php
	$prods 	= get_post_meta( $query->post->ID, '_cp_request_data', true );  
	$qbo 	= maybe_unserialize(  CP()->client->qbo_get_items( cpencode( $prods ), CP()->qbo->license ) );
	        // error_log(print_r($qbo, true), 3, plugin_dir_path(__FILE__) . "/products.log");
	if($qbo){
		$variable_prods = array();
		foreach($qbo as $key=>$product){
			
			if($key == 'cp_messages' || $key == 'messages'){
				CPM()->add_message($product);
			}elseif($key == 'not_in' ) {
				if(sizeof($product) > 0 ){
					foreach ($product as $value) {
						wp_set_object_terms( $value , 'not-in-quickbooks', 'qb_status'. false );	
					}
				}	
			}else{
				if(isset($product->web_item->id)){
					$product_id = isset($product->web_item->wc_product->variation_id) ? $product->web_item->wc_product->variation_id :$product->web_item->wc_product->id;
					$wc_prod = wc_get_product( $product_id );
                    
					if( $wc_prod ){
						if($wc_prod->is_type('variable')){
							$variable_prods[] = $product_id;
						}
						if(CP()->qbo->sync_stock == 'yes'){
							if( (!empty($product->qty) && $product->qty !='' ) || $product->qty ==  0):
								$wc_prod->set_stock( $product->qty );
							endif;
						}
						
						if(CP()->qbo->sync_price == 'yes'){
							if($product->price &&  $product->price != ''):
								update_post_meta( $product_id, '_price', $product->price );
								update_post_meta( $product_id, '_regular_price', $product->price );
								if($wc_prod->is_type('variable')){
									WC_Product_Variable::sync( $product_id );
								}
							endif;
						}
						if(CP()->qbo->store_cost == 'yes'){
							if($product->cost && $product->cost != ''):
								update_post_meta($product_id, '_qb_cost', $product->cost);
							endif;
						}
						update_post_meta( $product_id, 'qbo_product_id', $product->id );
						update_post_meta( $product_id, 'qbo_data', $product );
						update_post_meta( $product_id, 'qbo_last_updated', current_time('timestamp') );
						wp_set_object_terms( $product_id , 'in-quickbooks', 'qb_status'. false );
					}	
				}else{
				   //If the subscription supports it, non-website items are returned to be imported.
				   if(CP()->qbo->import_products == 'yes'){
		    		  if( $product_id == false || $product_id =='' ){
		    		   //	CP()->cp_qbo_import_item( $product );
					  }	
				   }
				}
			}
		}
		if(sizeof($variable_prods) > 0){
				foreach($variable_prods as $variable_id){
					WC_Product_Variable::sync( $variable_id );
					WC_Product_Variable::sync_stock_status( $variable_id );
				}
			}
	}
	wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );
	
	return $prods;