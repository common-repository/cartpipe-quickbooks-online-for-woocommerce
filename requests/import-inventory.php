<?php 
global $wpdb;
$qbo = maybe_unserialize(  CP()->client->qbo_import_items( CP()->qbo->license ) );

if($qbo){
	foreach($qbo as $key=>$product){
		if($key == 'cp_messages' || $key == 'messages'){
			CPM()->add_message($product);
		}
		CP()->cp_qbo_import_item( $product );
	}
	CP()->import_images();
	wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );
}else{
	wp_set_object_terms( $query->post->ID , 'failed', 'queue_status'. false );	
}