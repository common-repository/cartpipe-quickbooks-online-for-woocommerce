<div class="woocommerce_variation wc-metabox closed">
	<h3>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong>#<?php echo esc_html( $variation_id ); ?> &mdash; <?php esc_html( $variation_id ); ?></strong>
			<?php 
				foreach ( $parent_data['attributes'] as $attribute ) {
				
				// Only deal with attributes that are variations
				if ( ! $attribute['is_variation'] ) {
					continue;
				}
				// Get current value for variation (if set)
				$variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] : '';
				echo $variation_selected_value;
			}?>
	</h3>
	<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes cp wc-metabox-content">
		<tbody>
		<?php
		$properties = array(
					'id'=>array(
							'can_edit'	=> false,
							'type'		=> 'input'
						),
					'name'=>array(
							'can_edit'	=>	false,
							'type'		=> 'input'
						),
					'description'=>array(
							'can_edit'	=> true,
							'type'		=> 'textarea'
						),
					'active'=>array(
							'can_edit'	=> true,
							'options'	=> array('True', 'False'),
							'type'		=> 'select'
						),
					'full_name'=>array(
							'can_edit'	=> false,
							'type'		=> 'input'
					),
					'taxable'=>array(
							'can_edit'	=> true,
							'options'	=> array('True', 'False'),
							'type'		=> 'select'
					),
					'price'=>array(
							'can_edit'	=> true,
							'type'		=> 'input'
					),
					'type'=>array(
							'can_edit'	=> true,
							'options'	=> array('Service', 'Inventory', 'NonInventory'),
							'type'		=> 'select'
							
					),
				);			
			?>
			<tr>	
				<td>
					<div class="columns">		
			<?php foreach($properties as $prop=>$prop_data){ ?>
					
						<p class="form-field <?php echo $prop;?>_field">
							
							<label for="qbo_product_<?php echo $prop;?>"><?php printf('%s %s', __('Product', 'cartpipe'), ucwords(str_replace('_', ' ', $prop ) ) );?></label>
							<?php switch ($prop_data['type']) {
								case 'input':?>
									<input type="text" 
										class="short <?php echo $prop_data['can_edit'] ? 'can_edit' : '';?>" 
										disabled="disabled" 
										name="qbo_product_<?php echo $prop;?>" 
										id="qbo_product_<?php echo $prop;?>" 
										value="<?php echo  cptexturize( wp_kses_post( ucwords(str_replace('_', ' ', $data->$prop) )  ) );?>"
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
										value="<?php echo  cptexturize( wp_kses_post( ucwords(str_replace('_', ' ', $data->$prop) )  ) );?>"
									></input>
									<?php break;
							}?>
							
						</p>
				
		<?php }; ?>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>