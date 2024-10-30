<?php 
	woocommerce_wp_text_input( array( 
									'id' 		=> '_qb_cost', 
									'label' 	=> __( 'Cost', 'cartpipe' ) , 
									'data_type' => 'price',
									'value'		=> $cost,
									)
							);
	
	// woocommerce_wp_select( array( 
									// 'id' 		=> '_qb_product_asset_accout', 
									// 'label' 	=> __( 'Asset Account', 'cartpipe' ) , 
									// 'value'		=> $asset,
									// 'options'	=> CP()->qbo->accounts,
									// 'desc_tip'	=> 'Select an Asset Account to ovevrride the default value'
									// )
							// );
	// woocommerce_wp_select( array( 
									// 'id' 		=> '_qb_product_income_accout', 
									// 'label' 	=> __( 'Income Account', 'cartpipe' ) , 
									// 'value'		=> $income,
									// 'options'	=> CP()->qbo->accounts,
									// 'desc_tip'	=> 'Select an Income Account to ovevrride the default value'
									// )
							// );				
	// woocommerce_wp_select( array( 
									// 'id' 		=> '_qb_product_expense_accout', 
									// 'label' 	=> __( 'Expense Account', 'cartpipe' ) , 
									// 'value'		=> $expense,
									// 'options'	=> CP()->qbo->accounts,
									// 'desc_tip'	=> 'Select an Expense Account to ovevrride the default value'
									// )
							// );					