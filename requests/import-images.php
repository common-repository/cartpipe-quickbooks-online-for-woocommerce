<?php 
	global $wpdb;
	$number_to_send = 10;
	$product 		= wp_count_posts( 'product' )->publish;
	$variations 	= wp_count_posts( 'product_variation' )->publish;
	$imported_items	= wp_count_posts( 'imported_item' )->publish;
	$sum 			= $product + $variations + $imported_items;
	$num_pages 		= ceil($sum / $number_to_send);
	$i 				= 0;
	while ($i <= $num_pages ) {
		$prods = array('prods'=>array());
		//should probably only be 'imported_items' post type, but this will also catch some already synced entities. 
		$args = array( 
			'post_type' => 
			array(
				'imported_item',
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
				$qbo_id 	= cptexturize(get_post_meta( get_the_ID(), 'qbo_product_id', true ));
				if($qbo_id){
					$prods['prods'][$qbo_id] = get_the_ID();
				}
			endwhile;
		endif;
		if(!CP()->client){
			CP()->init_client();
		}
		$qbo = maybe_unserialize(  CP()->client->qbo_get_images( cpencode( $prods ), CP()->qbo->license ) );
		if($qbo){
			foreach($qbo as $post_id=>$image_url){
				CP()->save_image($image_url, $post_id);
			}	
		}
		$i++;		
	}
	wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );