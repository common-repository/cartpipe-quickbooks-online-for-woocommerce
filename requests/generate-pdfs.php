<?php 
global $wpdb;
$number_to_send = 10;
$orders 		= wp_count_posts( 'shop_order' )->publish;
$num_pages 		= ceil($orders / $number_to_send);
$i 				= 0;
while ($i <= $num_pages ) {
	$args = array( 
		'post_type' => array(
			'shop_order'
		),
		'posts_per_page' 	=> $number_to_send,
		'post_status' 		=> array_keys( wc_get_order_statuses()),
		'paged'				=> $i,
		'meta_query'		=> array(
			'relation' => 'OR', 
			array(
				'key'=>'_qbo_salesreceipt_number',
				'compare'=>'EXISTS'
			),
			array(
				'key'=>'_qbo_invoice_number',
				'compare'=>'EXISTS'
			)
		),
	);
	$orders_query = new WP_Query( $args );
	//Build the batch data
	if( $orders_query->have_posts() ):
		while ( $orders_query->have_posts() ) : 
			$orders_query->the_post();
			$inv_id	= cptexturize( get_post_meta( get_the_ID(), '_qbo_invoice_number', true) );
			$sr_id	= cptexturize( get_post_meta( get_the_ID(), '_qbo_salesreceipt_number', true) );
			if($inv_id && $inv_id !=''){
				$data[get_the_ID()] = array(
					'qbo_invoice_number'		=> $inv_id ,//get_post_meta( $ref_id, '_qbo_invoice_number', true),
					//'qbo_salesreceipt_number'	=> $sr_id ,//get_post_meta( $ref_id, '_qbo_invoice_number', true),
					'posting_type'				=> 'invoice' 
				);
			}
			if($sr_id && $sr_id !=''){
				$data[get_the_ID()] = array(
					'qbo_salesreceipt_number'		=> $sr_id ,//get_post_meta( $ref_id, '_qbo_invoice_number', true),
					//'qbo_salesreceipt_number'	=> $sr_id ,//get_post_meta( $ref_id, '_qbo_invoice_number', true),
					'posting_type'				=> 'sales_receipt' 
				);		
			}
			
		endwhile;
	endif;
	//Send to Cartpipe
	CP()->client->set_return_as_object( false );
	$pdfs = CP()->client->batch_pdfs( $data, CP()->qbo->license);
	//Parse Batch PDFS
	
	if($pdfs){
		foreach($pdfs as $ref_id => $pdf){
			$filename = "order_". $ref_id .".pdf";
			$upload_file = wp_upload_bits( $filename, null, $pdf);
			if (!$upload_file['error']) {
				$wp_filetype = wp_check_filetype($filename, null );
				$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_parent' => $ref_id,
							'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
							'post_content' => '',
							'post_status' => 'inherit'
						);
						$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $ref_id );
						if (!is_wp_error($attachment_id)) {
							require_once(ABSPATH . "wp-admin" . '/includes/image.php');
							$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
							wp_update_attachment_metadata( $attachment_id,  $attachment_data );
							$order 			= wc_get_order( $ref_id );
							update_post_meta( $attachment_id,  'cp_order_user_id',  $order->get_user_id() );
							update_post_meta( $attachment_id,  'cp_order_id', $ref_id );
							CP_PDF()->make_private( $attachment_id );
						}
						wp_set_object_terms( $query->post->ID , 'success', 'queue_status'. false );
					}else{
						CP()->cp_insert_fallout('Order #'.$ref_id, $ref_id, '', 'create-invoice-pdf', 'order');
						wp_set_object_terms( $query->post->ID , 'failed', 'queue_status'. false );
					}
				}
			}else{
				CP()->cp_insert_fallout('Batch Fetch PDFs Failed', $ref_id, '', 'create-invoice-pdf', 'order');
			//update_post_meta( $ref_id , '_cp_errors', $qbo->errors);
			}
		$i++;		
	}
//return ;
	