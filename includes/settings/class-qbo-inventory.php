<?php
/**
 * QBO Product Settings
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'QBO_Settings_Products' ) ) :

/**
 * WC_Settings_Products
 */
class QBO_Settings_Products extends QBO_Settings_Page {

	/**
	 * Constructor.
	 */
	public $accounts;
	public function __construct() {

		$this->id    = 'products';
		$this->label = __( 'Products', 'cartpipe' );
		$types 				= array('income', 'expense', 'asset');
		$this->accounts	= new stdClass;
		foreach($types as $type){
			$type_accounts  		= get_option('qbo_accounts_'.$type, false); 
			
			$this->accounts->$type 	= is_object( $type_accounts ) ? $type_accounts : new stdClass();
			if(isset($this->accounts->$type->errors) || $this->accounts->$type == '1' || empty( $this->accounts->$type )){
				delete_option('qbo_accounts_'.$type, false);
				CP()->needs['accounts'][$type] = true;
			}
			
		}
		if(isset(CP()->needs['accounts'])){
			if(sizeof(CP()->needs['accounts']) > 0){
		    	foreach(CP()->needs['accounts'] as $key => $need){
		    		if($need){
		    			if( CP()->client ):
							$this->accounts->$key  = CP()->client->qbo_get_filtered_accounts( CP()->qbo->license, $key );
							
							if(!isset($this->accounts->$type->errors) || empty($this->accounts->$type->errors) ){
								update_option( 'qbo_accounts_'.$key, $this->accounts->$key );
								$need = false;
							}else{
								$this->accounts->$key = false;
							}
						endif;
					}
				}
			}
		}
		if(CP()->qbo->license_info->level == 'Basic'){
					CPM()->add_message(
								sprintf(
									'If you\'d like to sync inventory quantities and / or import and export your products, think about upgrading to the <a target="_blank" href="%s">Standard</a> or <a target="_blank" href="%s">Premium Service</a>.', 
									CP()->qbo->license_info->product_url,  
									CP()->qbo->license_info->product_url
								)
							);
				}
		add_filter( 'qbo_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'qbo_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'qbo_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'qbo_sections_' . $this->id, array( $this, 'output_sections' ) );
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = array(
			'sync'      		=> __( 'Syncing Options', 'cartpipe' ),
			'import_mappings'	=> __( 'Import Field Mappings', 'cartpipe' ),
			'export_mappings' 	=> __( 'Export Field Mappings', 'cartpipe' ),
		);

		return apply_filters( 'qbo_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

 		QBO_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		QBO_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
				
				$wc_fields = array(
					'name'			=> 'Product Name',
					'description'	=> 'Product Description',
				);
				$qbo_fields = array(
					'name'					=> 'Name',
					'sales_description'		=> 'Sales Description',
					'purchase_description'	=> 'Purchase Description',
				);
				if($current_section == 'sync' || $current_section == ''){
					$wc_product_fields 	= array('sku'=> 'SKU', 'name'=> 'Product Name'); 
					$qbo_product_field	= array('sku'=> 'SKU', 'name'=> 'Item Name');
					$settings = apply_filters( 'qbo_product_sync_settings', array(
					
					array( 
						'title' => __( 'Product Settings', 'cartpipe' ), 
						'type' => 'title', 
						'desc' => '', 
						'id' => 'qbo_product_sync' 
					),
					array(
						'title'			=> __( 'WooCommerce product identifier to use during syncing ?', 'cartpipe' ),
						'tip'          => __( '', 'cartpipe' ),
						'desc'          => __( 'Which WooCommerce product field do you want to use to match to your items in QuickBooks', 'cartpipe' ),
						'id'            => 'qbo[wc_identifier]',
						'default'       => 'sku',
						'type'          => 'select',
						'options'		=> $wc_product_fields,
						'autoload'      => false
					),
					array(
						'title'			=> __( 'QuickBooks product identifier to use during syncing ?', 'cartpipe' ),
						'tip'          => __( '', 'cartpipe' ),
						'desc'          => __( 'Which QuickBooks item field do you want to use to match to your items in QuickBooks', 'cartpipe' ),
						'id'            => 'qbo[qbo_identifier]',
						'default'       => 'name',
						'type'          => 'select',
						'options'		=> $qbo_product_field,
						'autoload'      => false
					),
					// array(
						// 'title'             => __( 'Enable product identifier fallback?', 'cartpipe' ),
						// 'desc'              => __( 'Would you like to fallback to the QuickBooks Item Name if the sku isn\'t set in QuickBooks?', 'cartpipe' ),
						// 'id'                => 'qbo[qbo_fallback]',
						// 'type'              => 'checkbox',
						// 'css'               => '',
						// 'default'           => '',
						// 'dependency'		=> array(
												// 'setting'	=> 'qbo[qbo_identifier]',
												// 'value'		=> 'sku'
											// ),
						// 'autoload'          => false
					// ),
					array(
						'title'			=> __( 'Sync Frequency?', 'cartpipe' ),
						'tip'          => __( 'Check this box to have the website product prices be updated with prices from QuickBooks Online', 'cartpipe' ),
						'desc'          => __( 'Please select how frequently the products on the website should be synced with their counterparts in QuickBooks', 'cartpipe' ),
						'id'            => 'qbo[frequency]',
						'default'       => '14400',
						'type'          => 'select',
						'options'		=> array(
											'14400' => 'Every 4 Hours',
											'43200' => 'Every 12 Hours',
											'86400' => 'Every 24 Hours'
											),
						'autoload'      => false
					),
					array(
						'title'			=> __( 'Sync Price?', 'cartpipe' ),
						'tip'          => __( 'Check this box to have the website product prices be updated with prices from QuickBooks Online', 'cartpipe' ),
						'desc'          => __( 'Check this box to have the website product prices be updated with prices from QuickBooks Online', 'cartpipe' ),
						'id'            => 'qbo[sync_price]',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),
	
					array(
						'title'			=> __( 'Sync Stock?', 'cartpipe' ),
						'tip'			=> __( 'Check this box to have the website product stock be updated with on-hand qty\'s from QuickBooks Online', 'cartpipe' ),
						'desc'          => __( 'Check this box to have the website product stock be updated with on-hand qty\'s from QuickBooks Online', 'cartpipe' ),
						'id'            => 'qbo[sync_stock]',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),
					array(
						'title'			=> __( 'Store Cost?', 'cartpipe' ),
						'tip'			=> __( 'Check this box to have the Cost of Goods from QuickBooks stored with the product in WooCommerce. This will create a new field below Sale Price on the product page.', 'cartpipe' ),
						'desc'          => __( 'Check this box to have the Cost of Goods from QuickBooks stored with the product in WooCommerce. This will create a new field below Sale Price on the product page.', 'cartpipe' ),
						'id'            => 'qbo[store_cost]',
						'default'       => 'no',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),
					array(
						'title'			=> __( 'Export Products to QuickBooks Online?', 'cartpipe' ),
						'tip'			=> __( 'Check this box to export your WooCommerce products to QuickBooks Online. This option is only available in the Premium Cartpipe Service.', 'cartpipe' ),
						'desc'          => __( 'Check this box to export your WooCommerce products to QuickBooks Online. This option is only available in the Premium Cartpipe Service.', 'cartpipe' ),
						'id'            => 'qbo[export_products]',
						'default'       => 'no',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),
					array(
						'title'			=> __( 'Import QuickBooks Online Items to WooCommerce?', 'cartpipe' ),
						'tip'			=> __( 'Check this box to import your QuickBooks Online catalog to WooCommerce. This option is only available in the Premium Cartpipe Service.', 'cartpipe' ),
						'desc'          => __( 'Check this box to import your QuickBooks Online catalog to WooCommerce. This option is only available in the Premium Cartpipe Service.', 'cartpipe' ),
						'id'            => 'qbo[import_products]',
						'default'       => 'no',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),
					array(
						'title'			=> __( 'Imported Product Status?', 'cartpipe' ),
						'tip'          	=> __( 'Select the product status to use for items imported from QuickBooks Online.', 'cartpipe' ),
						'desc'          => __( 'Select the product status to use for items imported from QuickBooks Online.', 'cartpipe' ),
						'id'            => 'qbo[product_status]',
						'default'       => 'pending',
						'type'          => 'select',
						'options'		=> array(
											'pending' => 'Pending',
											'publish' => 'Published',
											),
						'autoload'      => false
					),
					array(
						'title'			=> __( 'Start Sync?', 'cartpipe' ),
						'label'			=> __('Sync', 'cartpipe'),
						'desc'          => __( 'Click to manually update website products pricing and quantities', 'cartpipe' ),
						//'id'            => 'qbo[sync_stock]',
						'type'          => 'button',
						'url'			=> '#',
						'class'			=> 'button sync',
						'autoload'      => false
					),
					array( 'type' => 'sectionend', 'id' => 'qbo_product_sync'),
	
				));
				}elseif($current_section == 'import_mappings'){
				
				$settings = apply_filters( 'import_mapping_options_settings', array(
					array( 
						'title' => __( 'Import Mapping Options', 'cartpipe' ), 
						'type' => 'title', 
						'desc' => '<p class="desc">Here you can define how the fields for products from QuickBooks Online can be imported as as products into WooCommerce.</p>
									<p class="desc">The column on the left is the WooCommerce product data field. Map that column to the QuickBooks Online item field on the right. * Please note, importing of items from QuickBooks Online as variable products into WooCommerce isn\'t supported. </p>', 
						'id' => 'import_mapping_options' 
					),
					array(
						'title'             => __( 'Product Field Mappings', 'cartpipe' ),
						'desc'              => __( 'Please map a QuickBooks Online product field to a counterpart in WooCommerce.', 'cartpipe' ),
						'id'                => 'qbo[import_fields]',
						'type'              => 'mapping',
						'options'			=> $qbo_fields,//wc_get_order_statuses(),
						'labels'			=> $wc_fields,
						'auto_create'		=> true, 
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'			=> __( 'Import QuickBooks Items into WooCommerce', 'cartpipe' ),
						'label'			=> __('Import', 'cartpipe'),
						'desc'          => __( 'Click to import QuickBooks Items into WooCommerce', 'cartpipe' ),
						//'id'            => 'qbo[sync_stock]',
						'type'          => 'button',
						'url'			=> '#',
						'class'			=> 'button import',
						'autoload'      => false
					),
					array( 'type' => 'sectionend', 'id' => 'import_mapping_options'),
		
				));
			}elseif($current_section == 'export_mappings'){
				
				$settings = apply_filters( 'qbo_export_mappings_settings', array(
					array( 
						'title' => __( 'Export Product Settings', 'cartpipe' ), 
						'type' => 'title', 
						'desc' => '<p class="desc">Here you can define how the products from WooCommerce can be defined as Items in QuickBooks Online.</p>
									<p class="desc">The column on the left is the WooCommerce data field. Map that column to the QuickBooks product field on the right.</p>',
						'id' => 'export_mappings_options' 
					),
					array(
						'title'             => __( 'Product Field Mappings', 'cartpipe' ),
						'desc'              => __( 'Please map the a QuickBooks Online product field to a counterpart in WooCommerce.', 'cartpipe' ),
						'id'                => 'qbo[export_fields]',
						'type'              => 'mapping',
						'options'			=> $wc_fields,//wc_get_order_statuses(),
						'labels'			=> $qbo_fields ,
						'auto_create'		=> true, 
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'			=> __( 'Export  WooCommerce Products into QuickBooks', 'cartpipe' ),
						'label'			=> __('Export', 'cartpipe'),
						'desc'          => __( 'Click to export WooCommerce products into QuickBooks', 'cartpipe' ),
						//'id'            => 'qbo[sync_stock]',
						'type'          => 'button',
						'url'			=> '#',
						'class'			=> 'button export',
						'autoload'      => false
					),
				array( 'type' => 'sectionend', 'id' => 'export_mappings_options'),
				array( 
						'title' => __( 'Default Account Mappings', 'cartpipe' ), 
						'type' => 'title', 
						'desc' => '<p class="desc">Here you can define the default accounts to use if your plan includes exporting items to QuickBooks.</p>',
						'id' => 'export_account_mappings' 
				),	
				array(
					'title'             => __( 'Income Account', 'cartpipe' ),
					'desc'              => __( 'Please select the Income Account to use for auto-created items in QuickBooks Online.', 'cartpipe' ),
					'id'                => 'qbo[income_account]',
					'type'              => 'select',
					'account'			=> 'income',
					'options'			=> $this->accounts->income,
					'css'               => '',
					'default'           => '',
					'create_account'		=> true,
					'autoload'          => false
				),
				array(
					'title'             => __( 'Expense Account', 'cartpipe' ),
					'desc'              => __( 'Please select the Expense Account to use for auto-created items in QuickBooks Online.', 'cartpipe' ),
					'id'                => 'qbo[expense_account]',
					'type'              => 'select',
					'account'			=> 'expense',
					'options'			=> $this->accounts->expense,
					'css'               => '',
					'create_account'		=> true,
					'default'           => '',
					'autoload'          => false
				),
				array(
					'title'             => __( 'Asset Account', 'cartpipe' ),
					'desc'              => __( 'Please select the Asset Account to use for auto-created items in QuickBooks Online. Click to create a new account.', 'cartpipe' ),
					'id'                => 'qbo[asset_account]',
					'type'              => 'select',
					'account'			=> 'asset',
					'options'			=> $this->accounts->asset,
					'css'               => '',
					'default'           => '',
					'create_account'		=> true,
					'autoload'          => false
				),
				array(
					'title'			=> __( '', 'cartpipe' ),
					'desc'          => __( 'Refresh Accounts?', 'cartpipe' ),
					'label'          => __( 'Refresh', 'cartpipe' ),
					//'id'            => 'qbo[sync_stock]',
					'type'          => 'button',
					'url'			=> '#',
					'data-type'		=> 'accounts',
					'linked'		=> 'qbo[accounts]',
					'class'			=> 'button refresh accounts',
					'autoload'      => false
				),
					array( 'type' => 'sectionend', 'id' => 'export_account_mappings'),
	
				));
				}

		return apply_filters( 'qbo_get_settings_' . $this->id, $settings, $current_section );
	}
}

endif;

return new QBO_Settings_Products();