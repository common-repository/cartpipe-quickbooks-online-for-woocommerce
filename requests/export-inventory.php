<?php 
	global $wpdb;
	$number_to_send = 20;
	$product 	= wp_count_posts( 'product' )->publish;
	$variations = wp_count_posts( 'product_variation' )->publish;
	$sum 		= $product + $variations;
	$num_pages 	= ceil($sum / $number_to_send);
	$i 			= 0;
	$wc_identifier = isset( CP()->qbo->wc_identifier ) ? CP()->qbo->wc_identifier : 'sku';
	$qbo_identifier = isset( CP()->qbo->qbo_identifier ) ? CP()->qbo->qbo_identifier : 'name';
	while ($i <= $num_pages ) {
		$prods = array('prods'=>array());
		$args = array( 
			'post_type' => 
			array(
				'product', 
				'product_variation'
			),
			'posts_per_page' 	=> $number_to_send,
			'post_status' 		=> array('publish') ,
			'paged'				=> $i,
		);
		$prod_query = new WP_Query( $args );
		if( $prod_query->have_posts() ):
			while ( $prod_query->have_posts() ) : $prod_query->the_post();
				$prod 		= get_product( get_the_ID() );
				$cost 		= get_post_meta( get_the_ID(), '_qb_cost',  true);
				$expense	= get_post_meta( get_the_ID(), '_qb_product_expense_accout',  true);
				$income		= get_post_meta( get_the_ID(), '_qb_product_income_accout',  true);
				$asset 		= get_post_meta( get_the_ID(), '_qb_product_asset_accout',  true);
				switch ($wc_identifier) {
					case 'name':
						$sku 		=  wc_clean(substr( get_the_title(), 0, 100) );
						break;
					case 'sku':
						$sku 		= $prod->get_sku();		
						break;
				}
				$sku 		= $prod->get_sku();
				$managing_stock = $prod->managing_stock();
				if($prod->get_type() != 'variation'){
					$taxable = $prod->is_taxable();
				}else{
					$taxable = false;
				}
				$prods['prods'][$sku] = array(
							'id' 				=>get_the_ID(),
							'price'				=> $prod->get_price(),
							'managing_stock'	=> $managing_stock,
							'stock'				=> isset($managing_stock) && $managing_stock ? $prod->get_stock_quantity() : false,
							'sku'				=> wc_clean($prod->get_sku()),
							'description'		=> wc_clean(substr( get_the_content(), 0, 1000 ) ),
							'name'				=> wc_clean(substr( get_the_title(), 0, 100) ),
							'taxable'			=> $taxable,
							'active'			=> true,
							'cost' 				=> $cost,
							
						);
				if(isset( CP()->qbo->asset_account) ){
					if(isset($asset) && $asset != CP()->qbo->asset_account){
						$prods['prods'][$sku]['asset_account'] = $asset;
					}
				}else{
					if(isset($asset)){
						$prods['prods'][$sku]['asset_account'] = $asset;
					}
				}
				if(isset( CP()->qbo->income_account)){
					if(isset($income) && $income != CP()->qbo->income_account){
						$prods['prods'][$sku]['income_account'] = $income;
					}
				}else{
					if(isset($income)){
						$prods['prods'][$sku]['income_account'] = $income;
					}
				}
				if(isset( CP()->qbo->expense_account )){
					if(isset($expense) && $expense != CP()->qbo->expense_account){
						$prods['prods'][$sku]['expense_account'] = $expense;
					}
				}else{
					if(isset($expense)){
						$prods['prods'][$sku]['expense_account'] = $expense;
					}
				}
		    endwhile;
		endif;
		$prods['prods']['export_mapping'] 						= 	isset( CP()->qbo->export_fields ) ? CP()->qbo->export_fields : '';
		$prods['prods']['export_mapping']['income_account'] 	=  	isset( CP()->qbo->income_account ) ? CP()->qbo->income_account : '';
		$prods['prods']['export_mapping']['asset_account'] 		=  	isset( CP()->qbo->asset_account ) ? CP()->qbo->asset_account : '';
		$prods['prods']['export_mapping']['expense_account'] 	=  	isset( CP()->qbo->expense_account ) ? CP()->qbo->expense_account : '';
		$prods['prods']['wc_identifier'] 						= 	$wc_identifier;
		$prods['prods']['qbo_identifier'] 						= 	$qbo_identifier;
		$prods['prods']['import'] 								= 	isset( CP()->qbo->import_products ) ? 'yes' : '';
		$prods['prods']['export'] 								= 	isset( CP()->qbo->export_products ) ? 'yes' : '';
		
		if(!CP()->client){
			CP()->init_client();
		}
		$qbo = maybe_unserialize(  CP()->client->qbo_export_items( cpencode( $prods ), CP()->qbo->license ) );
		if($qbo){
			foreach($qbo as $product_id=>$qbo_id){
				update_post_meta( $product_id, 'qbo_product_id',$qbo_id );
				update_post_meta( $product_id, 'qbo_last_updated', current_time('timestamp') );
				wp_set_object_terms( $product_id , 'in-quickbooks', 'qb_status'. false );
			}
		}
		$i++;		
	}
	wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );