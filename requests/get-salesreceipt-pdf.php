<?php 
	$qbo_sr_id = get_post_meta( $ref_id, '_qbo_salesreceipt_number', true);
	
	if($qbo_sr_id){
		$data = array(
				'qbo_salesreceipt_number'	=> cptexturize( $qbo_sr_id ),//get_post_meta( $ref_id, '_qbo_invoice_number', true),
				'posting_type'			=> 'salesreceipt'
		);
	
		CP()->client->set_return_as_object( false );
	
		$pdf = CP()->client->get_pdf( $data, CP()->qbo->license);
		
		if($pdf){
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
			
		}else{
			CP()->cp_insert_fallout('Order #'.$ref_id, $ref_id, '', 'create-salesreceipt-pdf', 'order');
			//update_post_meta( $ref_id , '_cp_errors', $qbo->errors);
			wp_set_object_terms( $query->post->ID , 'failed', 'queue_status'. false );
		}
	}else{
		CP()->cp_insert_fallout('Order #'.$ref_id, $ref_id, 'Order not found in QuickBooks', 'create-salesreceipt-pdf', 'order');
		wp_set_object_terms( $query->post->ID , 'failed', 'queue_status'. false );
	}
	//return ;
	