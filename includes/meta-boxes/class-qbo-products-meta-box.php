<?php
/**
 * CP QBO Order Data
 *
 * Functions for displaying the qbo order data meta box.
 *
 * @author 		CartPipe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Data Class
 */
class CP_QBO_Product_Meta_Box extends CP_Meta_Boxes{

		
	public static function display_costs_field(){
		global $post, $thepostid;
		$cost 		= get_post_meta($thepostid, '_qb_cost', true);
		$_income 	= get_post_meta($thepostid, '_qb_product_income_accout', true);
		$_asset 	= get_post_meta($thepostid, '_qb_product_asset_accout', true);
		$_expense 	= get_post_meta($thepostid, '_qb_product_expense_accout', true);
		$income 	= !empty( $_income ) ? $_income : CP()->qbo->income_account;
		$asset 		= !empty( $_asset ) ? $_asset : CP()->qbo->asset_account;
		$expense	= !empty( $_expense ) ? $_expense : CP()->qbo->expense_account;
		include( 'views/cp-qb-cost.php' );
	}
	
	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $post;
		$product 	= wc_get_product($post->ID );
		$qbo_box 	= ''; 
		$cp_options = apply_filters( 'cp_product_options', array(
			'sync' => array(
				'id'            => '_qbo_sync',
				'wrapper_class' => '',
				'label'         => __( 'QuickBooks Sync', 'cartpipe' ),
				'description'   => __( 'Check this box to enable syncing with QuickBooks Online.', 'cartpipe' ),
				'default'       => 'yes'
			),
		) );
		foreach ( $cp_options as $key => $option ) {
			$selected_value = get_post_meta( $post->ID, '_' . $key, true );

			if ( '' == $selected_value && isset( $option['default'] ) ) {
				$selected_value = $option['default'];
			}

			$qbo_box .= '<label for="' . esc_attr( $option['id'] ) . '" class="'. esc_attr( $option['wrapper_class'] ) . ' tips" data-tip="' . esc_attr( $option['description'] ) . '">' . esc_html( $option['label'] ) . ': <input type="checkbox" name="' . esc_attr( $option['id'] ) . '" id="' . esc_attr( $option['id'] ) . '" ' . checked( $selected_value, 'yes', false ) .' /></label>';
		}
		$cartpipe_data_tabs = apply_filters( 'cartpipe_product_data_tabs', array(
						'quickbooks' => array(
							'label'  => __( 'QuickBooks Online', 'cartpipe' ),
							'target' => 'quickbooks_product_data',
						),
						// 'queue' => array(
							// 'label'  => __( 'Cartpipe Queue Activity', 'cartpipe' ),
							// 'target' => 'queue_product_data',
						// ),
						// 'sync' => array(
							// 'label'  => __( 'Sync Options', 'cartpipe' ),
							// 'target' => 'sync_product_data',
						// ),
						
					));
		
		$queue = CP()->cp_lookup_queue_items( $post->ID );?>
		<div class="panel-wrap">
			<span class="sync_box"> <?php echo $qbo_box; ?></span>
			<ul class="cartpipe_data_tabs wc-tabs" style="display:block;">
			<?php 
				foreach ( $cartpipe_data_tabs as $key => $tab ) {
					?><li class="<?php echo $key; ?>_options <?php echo $key; ?>_tab ">
						<a href="#<?php echo $tab['target']; ?>"><?php echo esc_html( $tab['label'] ); ?></a>
					</li><?php
				}	do_action( 'cartpipe_product_write_panel_tabs' );
			?>
			</ul>
			<div id="quickbooks_product_data" class="cp_data panel" style="display:block;">
				
					<!-- <div class="live-edit">
						<label for="qb_live_edit"><?php _e('Enable live QB data editing?', 'cartpipe');?></label>
						<input type="checkbox" name="qb_live_edit" id="qb_live_edit"/>
						<img class="help_tip" data-tip="<?php esc_attr_e( 'Enabling Live Edit will allow you to make changes here that will update the item in QuickBooks Online', 'cartpipe' ); ?>" src="<?php echo esc_url( CP()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />
					</div> -->
					<div class="qb_content">
					<?php 
					if($product->get_sku() != ''){
						$data = get_post_meta( $post->ID, 'qbo_data', true );
						
						$properties = array(
							'id'=>array(
									'can_edit'=>false,
									'type'		=> 'input'
								),
							'name'=>array(
									'can_edit'	=>	false,
									'type'		=> 'input'
								),
							// 'description'=>array(
									// 'can_edit'	=> false,
									// 'type'		=> 'textarea'
								// ),
							// 'active'=>array(
									// 'can_edit'=>false,
									// 'options'	=> array('True', 'False'),
									// 'type'		=> 'select'
								// ),
							'full_name'=>array(
									'can_edit'	=> false,
									'type'		=> 'input'
							),
							'taxable'=>array(
									'can_edit'	=> false,
									'options'	=> array('True', 'False'),
									'type'		=> 'select'
							),
							'price'=>array(
									'can_edit'	=> false,
									'type'		=> 'input'
							),
							'cost'=>array(
									'can_edit'	=> false,
									'type'		=> 'input'
							),
							// 'type'=>array(
									// 'can_edit'	=> true,
									// 'options'	=> array('Service', 'Inventory', 'NonInventory'),
									// 'type'		=> 'select'
// 									
							// ),
						);
							foreach($properties as $prop=>$prop_data){?>
							<p class="form-field <?php echo $prop;?>_field">
								
								<label for="qbo_product_<?php echo $prop;?>"><?php printf('%s %s', __('Product', 'cartpipe'), ucwords(str_replace('_', ' ', $prop ) ) );?></label>
								<?php switch ($prop_data['type']) {
									case 'input':?>
										<input type="text" 
											class="short <?php echo $prop_data['can_edit'] ? 'can_edit' : '';?>" 
											disabled="disabled" 
											name="qbo_product_<?php echo $prop;?>" 
											id="qbo_product_<?php echo $prop;?>" 
											value="<?php echo  cptexturize( wp_kses_post( ucwords(str_replace('_', ' ', isset($data->$prop) ?  $data->$prop : '') )  ) );?>"
										></input>
										<?php break;
									case 'select':?>
										<select 
											class="short <?php echo $prop_data['can_edit'] ? 'can_edit' : '';?>" 
											disabled="disabled" 
											name="qbo_product_<?php echo $prop;?>" 
											id="qbo_product_<?php echo $prop;?>">
											<?php foreach($prop_data['options'] as $option){?> 
												<option value="<?php echo  cptexturize( wp_kses_post( ucwords(str_replace('_', ' ', $option) )  ) );?>"><?php echo  cptexturize( wp_kses_post( ucwords(str_replace('_', ' ',$option) )  ) );?></option>
											<?php }?>
										</select>
										<?php break;
									case 'textarea':?>
										<input type="textarea" 
											class="short <?php echo $prop_data['can_edit'] ? 'can_edit' : '';?>" 
											disabled="disabled" 
											name="qbo_product_<?php echo $prop;?>" 
											id="qbo_product_<?php echo $prop;?>" 
											value="<?php echo  cptexturize( wp_kses_post( ucwords(str_replace('_', ' ', isset($data->$prop) ?  $data->$prop : '') )  ) );?>"
										></input>
										<?php break;
								}?>
								
							</p>
						<?php }
						}else{?>
							<p class="cp-notice"><?php _e('Please enter a sku for any woocommerce product to sync it with cartpipe', 'cartpipe');?></p>
						<?php }?>	
						<?php
							if($product->is_type('variable')){?>
								<h3 class="heading variations"><?php _e('QBO Data for Variations', 'cartpipe');?></h3>
								<?php self::output_variations();
							} 	
						?>
						<p class="form-field">
							<label for="sync">
								<?php _e( 'Sync Options', 'cartpipe' ); ?> 
								<img class="help_tip" data-tip='<?php esc_attr_e( 'Clicking the "Un-sync" button will delete the stored QBO data for this product', 'cartpipe' ); ?>' src="<?php echo CP()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
							</label>
							<a href="#" name="sync-from" class="button" style="display:none">
								<?php _e( 'Update WooCommerce Data', 'cartpipe' ); ?>
							</a>
							<a href="#" name="break-sync" class="button">
								<?php _e( 'Un-sync', 'cartpipe' ); ?>
							</a>
							<a href="#" name="sync-to" class="button" style="display:none">
								<?php _e( 'Update QuickBooks Item', 'cartpipe' ); ?>
							</a>
						</p>					
					</div>
					
			</div>
			
			
		</div>
		<?php
	}
	public static function output_variations() {
		global $post;

		$attributes = maybe_unserialize( get_post_meta( $post->ID, '_product_attributes', true ) );

		// See if any are set
		$variation_attribute_found = false;

		if ( $attributes ) {

			foreach ( $attributes as $attribute ) {
				
				if ( isset( $attribute['is_variation'] ) ) {
					$variation_attribute_found = true;
					break;
				}
			}
		}

		// Get tax classes
		

		?>
		<div id="cp_product_options" class="wc-metaboxes-wrapper cp-variations-table"><div id="variable_product_options_inner">

			<?php if ( ! $variation_attribute_found ) : ?>

				<div id="message" class="inline woocommerce-message">
					<p><?php _e( 'Before adding variations, add and save some attributes on the <strong>Attributes</strong> tab.', 'woocommerce' ); ?></p>
				</div>

			<?php else : ?>

				<div class="woocommerce_variations wc-metaboxes">
					<?php
					// Get parent data
					$parent_data = array(
						'id'                   => $post->ID,
						'attributes'           => $attributes,
						'sku'                  => get_post_meta( $post->ID, '_sku', true ),
					);

					
					// Get variations
					$args = array(
						'post_type'   => 'product_variation',
						'post_status' => array( 'private', 'publish' ),
						'numberposts' => -1,
						'orderby'     => 'menu_order',
						'order'       => 'asc',
						'post_parent' => $post->ID
					);

					$variations = get_posts( $args );
					$loop = 0;
					
					if ( $variations ) {

						foreach ( $variations as $variation ) {
								
							$variation_id       = absint( $variation->ID );
							$data 				= get_post_meta( $variation_id, 'qbo_data', true );
							$variation_data     = get_post_meta( $variation_id );
							include( 'views/cp-variation-data.php' );
						$loop++;
						}
					}
					?>
				</div>

				<p class="toolbar">
				</p>
					

			<?php endif; ?>
		</div></div>
		<?php
	}
	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		
	}
}
