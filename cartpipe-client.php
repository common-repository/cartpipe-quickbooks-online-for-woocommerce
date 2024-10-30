<?php
/*Plugin Name: Cartpipe QuickBooks Online Integration for WooCommerce
Plugin URI: Cartpipe.com
Description: Cartpipe Client for WooCommerce / QuickBooks Online Integration
Author: Cartpipe.com
Version: 1.1.7
Author URI: Cartpipe.com
*/

/* comment added in a branch */
if(!class_exists('CP_QBO_Client')){
	define("CP_API", "https://api.cartpipe.com");
	define("CP_URL", "https://www.cartpipe.com");
	define("CP_VERSION", '1.1.7');
	Class CP_QBO_Client{
		/*
		 * Instance
		 */
		protected static $_instance = null;
		
		/*
		 * CartPipe Consumber Key
		 */
		protected $cp_consumer_key = null;
		
		/*
		 * CartPipe Consumber Secret
		 */
		protected $cp_consumer_secret = null;
		
		/*
		 * CartPipe Service URL
		 */
		protected $cp_url = null;
		
		protected $trigger = null;
		/*
		 * API Client
		 */
		public $client = null;
		
		public $needs = array(
							'tax_rates'			=>false, 
							'tax_codes'			=>false, 
							'payment_methods' 	=> false
						);
		
		public $qbo		= null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			
			return self::$_instance;
			
		}
		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'cartpipe' ), '1.0.1' );
		}
	
		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'cartpipe' ), '1.0.1' );
		}
		/**
		 * Constructor
		 *
		 * @since 1.0
		 */
		function __construct(){
			$this->includes();
			$this->init();
			//add_action( 'woocommerce_api_edit_customer', array( $this, 'cp_qbo_update_customer_info'), 10, 2);
			add_action(	'init', array( $this,'qbo_init'));
			add_action( 'admin_init', array($this, 'debug'));
			add_action(	'admin_menu', array($this,'menu_items'));
			add_action( 'admin_menu', array( $this, 'settings_menu' ), 10 );
			add_action( 'add_meta_boxes', array($this, 'cp_add_meta_boxes') );
			add_action( 'qbo_settings_saved', array( $this, 'cp_reset_transients'));
			add_action(	'woocommerce_product_options_pricing', array('CP_QBO_Product_Meta_Box', 'display_costs_field'));
			add_action(	'woocommerce_process_product_meta', array($this, 'save_costs_field'));
			add_filter( 'woocommerce_order_get_items', array($this, 'cp_add_qbo_tax_id'), 0, 2 );
			add_action( 'manage_cp_queue_posts_custom_column' , array( $this, 'cp_queue_custom_columns' ) );
			add_filter(	'manage_edit-quickbooks_queue_columns', array( $this, 'cp_queue_page_columns' ) );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'cp_qbo_check_customer_exists' ) , 12, 2 );
			if(	isset($this->qbo->order_trigger) ){
				add_action( 'woocommerce_order_status_'.str_replace('wc-','',$this->qbo->order_trigger), array( $this, 'sod_qbo_send_order') , 12 );
			}
			add_action( 'woocommerce_order_status_completed', array( $this, 'cp_qbo_conditional_send_payment') , 13 );
			add_action( 'admin_enqueue_scripts', array( &$this,'cp_load_admin_js' )) ;
			add_filter( 'woocommerce_admin_order_actions', array(&$this, 'cp_add_qb_transfer'), 10, 2 );
			add_action( 'woocommerce_refund_created', array($this, 'cp_add_refund'));
			add_action(	'admin_footer-edit.php', array( $this, 'bulk_admin_footer' ));
			add_filter(	'post_row_actions', array( $this, 'imported_items_action_row'), 10, 2);
			//add_action('load-edit.php', array( $this, 'merge_bulk_action' ) );
			add_filter(	'bulk_actions-edit-imported_item',array($this, 'imported_items_bulk_actions' ) );
			add_action(	'admin_notices', array($this, 'imported_items_admin_notices') );
			add_action(	'load-edit.php', array($this, 'imported_items_bulk_process') );
			add_filter( 'http_request_timeout', array($this, 'cp_timeout_time') );
		}
		
		function cp_timeout_time( $time ){
			$time = 60;
			return $time;
		}
		function debug(){
		   // var_dump( CP()->qbo->license_info->status );
		   // CP()->cp_queue_inventory();
		}
		function imported_items_admin_notices(){
			global $post_type, $pagenow;
 
			  if($pagenow == 'edit.php' && $post_type == 'imported_item' &&
			     isset($_REQUEST['merged']) && (int) $_REQUEST['merged']) {
			    $message = sprintf( _n( 'Imported Items converted into products.', '%s imported items converted into products.', $_REQUEST['merged'] ), number_format_i18n( $_REQUEST['merged'] ) );
			    echo '<div class="updated"><p>' .$message . '</p></div>';
			  }
		}
		function imported_items_bulk_process() {
			if ( ! isset( $_REQUEST['action'] ) )
        		return;
			if (  $_REQUEST['action'] == 'merge' ){
				$wp_list_table = _get_list_table('WP_Posts_List_Table');
  				$action = $wp_list_table->current_action();
				//var_dump(check_admin_referer('merge')  );
				check_admin_referer('bulk-posts');
				switch($action) {
				    case 'merge':
				    $merged = 0;
				 	$post_ids = $_REQUEST['post'];
				    foreach( $post_ids as $post_id ) {
				       if ( !$this->merge($post_id) )
				          wp_die( __('Error converting product.') );
				        $merged++;
				    }
				    $sendback = add_query_arg( array('merged' => $merged, 'ids' => join(',', $post_ids) ), $_REQUEST['_wp_http_referer'] );
				 	break;
				    default: return;
				  }
				wp_redirect($sendback);
				exit();
			}
			//var_dump($sendback);
    		
		}

		function imported_items_bulk_actions($actions){
			unset( $actions['delete'] );
			//unset( $actions['edit'] );
			//unset( $actions['trash'] );
			$actions['merge'] = 'Convert to Product';
		    return $actions;
		}
		
		function cp_add_qbo_tax_id( $items, $order_abstract){
			if($items){
				foreach ($items as $key => $item){
					if($item['type'] == 'tax'){
						$taxes = CP()->qbo->taxes;
						if($taxes){
							if(isset($taxes[$item['rate_id']])){
								$items[$key]['qbo_id'] = $taxes[$item['rate_id']];
							}
						}
						
					}
				}
			}
			return $items;
		}
		function cp_reset_transients(){
			delete_transient('cp_last_sync');
		}
		public static function save_costs_field( $post_id ){
			$cost 				= $_POST['_qb_cost'];
			$asset_account 		= $_POST['_qb_product_asset_accout'];
			$income_account		= $_POST['_qb_product_income_accout'];
			$expense_account	= $_POST['_qb_product_expense_accout'];
			if( !empty( $cost ) ){
				update_post_meta( $post_id, '_qb_cost', esc_attr( $cost ) );
			}
			if( !empty( $asset_account ) ){
				update_post_meta( $post_id, '_qb_product_asset_accout', esc_attr( $asset_account ) );
			}
			if( !empty( $income_account ) ){
				update_post_meta( $post_id, '_qb_product_income_accout', esc_attr( $income_account ) );
			}
			if( !empty( $expense_account ) ){
				update_post_meta( $post_id, '_qb_product_expense_accout', esc_attr( $expense_account ) );
			}
		}
		function cp_admin_notices(){
			$notices = get_option( 'cp_admin_notices' );
			if($notices){
				foreach ($notices as $notice) {
			      echo "<div class='updated'><p>$notice</p></div>";
			    }
			}
			delete_option( 'cp_admin_notices' );
		}
		function cp_add_meta_boxes(){
			
			include(plugin_dir_path( __FILE__ ).'/includes/admin-meta-boxes.php');
			include(plugin_dir_path( __FILE__ ).'/includes/meta-boxes/class-qbo-orders-meta-box.php');
			include(plugin_dir_path( __FILE__ ).'/includes/meta-boxes/class-qbo-products-meta-box.php');
			include(plugin_dir_path( __FILE__ ).'/includes/meta-boxes/class-qbo-fallout-meta-box.php');
		}
		function plugin_url(){
			return plugins_url('', __FILE__);
		}
		function cp_add_qb_transfer($actions, $order){
		    global $post;
			$qb_data 	= get_post_meta( $post->ID, '_quickbooks_data', true);
			$queued		= get_post_meta( $post->ID,'_cp_is_queued', true);
			if($queued ==  'yes' ){
				$actions['queued'] = array(
						'url' 		=> '',//wp_nonce_url( admin_url( 'admin-ajax.php?action=cp_transfer_order_qbo&order_id=' . $post->ID ), 'cp-transfer-order-qbo' ),
						'name' 		=> __( 'Queued to send to QuickBooks', 'cartpipe' ),
						'action' 	=> "transfer queued view"
					);
			} elseif ($queued == 'success') {
				$actions['success'] = array(
						'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=cp_add_message&&sent=yes&order_id=' . $post->ID ), 'cp-transfer-order-qbo' ),
						'name' 		=> __( 'Successfully sent to QuickBooks', 'cartpipe' ),
						'action' 	=> "transfer resend success view"
					);
			}else {
				$actions['transfer'] = array(
							'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=cp_transfer_order_qbo&order_id=' . $post->ID ), 'cp-transfer-order-qbo' ),
							'name' 		=> __( 'Transfer', 'cartpipe' ),
							'action' 	=> "transfer view"
						);
			}		
			
		    return $actions;
		    //stop editing
		}
		function cp_load_admin_js($hook){
			
			global $post;
			wp_enqueue_script( 'jquery' );
            wp_enqueue_script('heartbeat');
			wp_register_style( 'cp-admin-css', plugins_url('/assets/css/cp.css', __FILE__), false, CP_VERSION );
			wp_register_style( 'cp-font-css', plugins_url('/assets/css/cp-font.css', __FILE__), false, CP_VERSION );
			wp_register_style( 'cp-font-awesome', plugins_url('/assets/css/font-awesome.min.css', __FILE__), false, CP_VERSION );
			$order_nonce 	= wp_create_nonce( "transfer-order" );
			$product_nonce 	= wp_create_nonce( "sync-product" );
			$options_nonce	= wp_create_nonce( "cp-options-nonce" );
			// Register plugin Scripts
			wp_register_script( 'cp-charts-js', plugins_url('/assets/js/cp.chart.min.js', __FILE__) );
			wp_register_script( 'cp-chart-functions', plugins_url('/assets/js/cp.chart.functions.js', __FILE__),'jquery',CP_VERSION, true );
			wp_register_script( 'cp-metabox-orders', plugins_url('/assets/js/cp.order.metabox.js', __FILE__),'jquery',CP_VERSION, true );
			wp_register_script( 'cp-metabox-products', plugins_url('/assets/js/cp.product.metabox.js', __FILE__),'jquery',CP_VERSION, true );
 			wp_enqueue_style( 'cp-admin-css' );
			wp_enqueue_style( 'cp-font-css' );
			wp_enqueue_style( 'cp-font-awesome' );
			// Enqeue those suckers
			wp_enqueue_script( 'cp-charts-js' );
	        wp_enqueue_script( 'cp-chart-functions' );
			
			//if(isset( $hook ) && $hook == 'cart-pipe_page_qbo-settings'){
				
				wp_register_script( 'cp-options', plugins_url('/assets/js/cp.options.js', __FILE__),array('jquery', 'jquery-blockui'),CP_VERSION, true );
				$options_metabox_data = array(
					'refresh_nonce'			=>	$options_nonce,
					'ajax_url'				=> 	admin_url('admin-ajax.php'),
					'plugin_url'			=> 	plugins_url('', __FILE__)
					
				);
				wp_enqueue_script( 'cp-options' );
				wp_localize_script( 'cp-options', 'cp_options', $options_metabox_data );
			//}
			if(isset($post)){
				$order_metabox_data = array(
					'post_id'				=>	$post->ID, 
					'transfer_order_nonce'	=>	$order_nonce,
					'ajax_url'				=> 	admin_url('admin-ajax.php'),
					'plugin_url'			=> 	plugins_url('', __FILE__)
					
				);
                wp_enqueue_script( 'cp-metabox-orders' );
            	wp_localize_script( 'cp-metabox-orders', 'cp_order_meta_box', $order_metabox_data );
			}
			if(isset($post)){
				$product_metabox_data = array(
					'post_id'				=>	$post->ID, 
					'sync_item_nonce'		=>	$product_nonce,
					'ajax_url'				=> 	admin_url('admin-ajax.php'),
					'plugin_url'			=> 	plugins_url('', __FILE__)
				);
                wp_enqueue_script( 'cp-metabox-products' );
            	wp_localize_script( 'cp-metabox-products', 'cp_product_meta_box', $product_metabox_data );
			
			}
			
		}
		function imported_items_action_row($actions, $post){
		    //check for your post type
		    if ($post->post_type =="imported_item"){
		    	if(isset($actions['edit'])){
		    		unset( $actions['edit'] );
		    	};
				// $actions = array(
		    		// 'merge'=>sprintf('<a href="%s">Merge</a>', add_query_arg('action', 'merge', admin_url( 'edit.php?post_type=imported_item&post=' . $post->ID) ) )
		    	// );
				
		    }
		    return $actions;
		}
		function bulk_admin_footer() {
 		  	global $post_type;
			  if($post_type == 'imported_item') {
			    ?>
			    <script type="text/javascript">
			      jQuery(document).ready(function() {
			        jQuery('<option>').val('merge').text('<?php _e('Convert to Product')?>').appendTo("select[name='action']");
			        jQuery('<option>').val('merge').text('<?php _e('Convert to Product')?>').appendTo("select[name='action2']");
			      });
			    </script>
			    <?php
			  }
		}
		function includes(){
			include_once(plugin_dir_path( __FILE__ ).'cartpipe-functions.php');
			include_once(plugin_dir_path( __FILE__ ).'cartpipe-help.php');
			//include(plugin_dir_path( __FILE__ ).'/test-scripts.php');
			include_once(plugin_dir_path( __FILE__ ). 'includes/admin-settings.php' );
			include_once(plugin_dir_path( __FILE__ ).'includes/cp-messages.php');
			include_once(plugin_dir_path( __FILE__ ).'includes/cp-ajax.php');
			include_once(plugin_dir_path( __FILE__ ).'includes/cp-heartbeat.php');
			include_once(plugin_dir_path( __FILE__ ).'includes/cp-api-client.php');
			include_once(plugin_dir_path( __FILE__ ).'includes/cp-pdf-routes.php');
			if ( ! empty( $_GET['page'] ) ) {
				switch ( $_GET['page'] ) {
					case 'cp-setup' :
						include_once( plugin_dir_path( __FILE__ ).'/includes/cp-setup-wizard.php');
						break;
				}
			};
			
			
		}
		function init(){
			
			$options 	= get_option('qbo') ? maybe_unserialize( get_option('qbo') ) : array();
			
			$this->qbo 	= new stdClass;
			$defaults	= array(
				'consumer_key' 		=> NULL,
				'consumer_secret'	=> NULL,
				'license'			=> NULL,
				'license_info'		=> NULL,
				'sync_frequency'	=> NULL,
				'sync_price'		=> NULL,
				'export_products'	=> NULL,
				'import_products'	=> NULL,
				'sync_stock'		=> NULL,
				'order_type'		=> NULL,
				'order_trigger'		=> NULL,
				'create_payment'	=> NULL,
				'taxes'				=> NULL,
				'tax_codes'			=> NULL,
				'payment_methods'	=> NULL,
				'asset_account'		=> NULL,
				'income_account'	=> NULL,
				'expense_account'	=> NULL,
				'delete_uninstall'	=> NULL, 
			
			);
			$options = array_merge($defaults, $options);
			
			if(sizeof($options) > 0 ){
				foreach($options as $key=>$value){
					if(!empty($key) && !empty($value)){
						$this->qbo->$key = $value;
					}
				}
			}
			
			if(isset($this->qbo->consumer_key)){
				$this->cp_consumer_key 		= $this->qbo->consumer_key; 	
			}
			if(isset($this->qbo->consumer_secret)){
				$this->cp_consumer_secret 	= $this->qbo->consumer_secret; 	
			}
			if(isset($this->qbo->api)){
				$this->cp_url 	= $this->qbo->api; 	
			}
			
 			if( $this->cp_url && $this->cp_consumer_key && $this->cp_consumer_secret ){
				$accounts 				 	= get_option('qbo_accounts', false);
				$this->qbo->accounts 		= isset( $accounts ) && $accounts != '' && $accounts ? $accounts : false;
				$this->client 				= new CP_Client( $this->cp_consumer_key, $this->cp_consumer_secret, $this->cp_url );
				//$license 					= get_transient( 'cartpipe_license_status' );
				$notices 					= get_transient( 'cartpipe_notices' );
				// if ( false === $license ) {
					// $license = $this->client->check_service( $this->qbo->license, get_home_url());
					// set_transient( 'cartpipe_license_status', $license, 2400 );
					 // $this->qbo->license_info = $license;
				 // }else{
					 // $this->qbo->license_info = $license;
				 // }
				
			} 
			
		}	
		
		
		
		function init_client(){
			include_once(plugin_dir_path( __FILE__ ).'includes/cp-api-client.php');
			if(isset($this->qbo->consumer_key)){
				$this->cp_consumer_key 		= $this->qbo->consumer_key; 	
			}
			if(isset($this->qbo->consumer_secret)){
				$this->cp_consumer_secret 	= $this->qbo->consumer_secret; 	
			}
			if( CP_API && $this->cp_consumer_key && $this->cp_consumer_secret ){
				$this->client 				= new CP_Client( $this->cp_consumer_key, $this->cp_consumer_secret, CP_API );
			}else{
				$this->client 				= new CP_Client( '', '', CP_API );
			}
			
		}
		function qbo_init() {
			include(plugin_dir_path( __FILE__ ).'cartpipe-post-types.php');
		}
		function menu_items(){
			$settings_page 	= add_submenu_page( 'cartpipe', __( 'Cartpipe Install Wizard', 'cartpipe' ),  __( 'Setup Wizard', 'cartpipe' ) , 'administrator', 'index.php?page=cp-setup'  );
			$main_page = add_menu_page( __( 'Cartpipe', 'cartpipe' ), __( 'Cartpipe', 'cartpipe' ), 'manage_woocommerce', 'cartpipe', null, null, '50' );
		}
		function settings_menu(){
			$settings_page 	= add_submenu_page( 'cartpipe', __( 'Settings', 'cartpipe' ),  __( 'Settings', 'cartpipe' ) , 'manage_woocommerce', 'qbo-settings', array( $this, 'settings_page' ) );
		}
		public function settings_page() {
			QBO_Admin_Settings::output();
		}
		function cp_qbo_import_item( $product ){
			if(isset($product->name) && !in_array($product->name, array('wc_identifier', 'qbo_identifier'))){
				global $wpdb;
				
				$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title='%s' AND post_type IN ('imported_item', 'product', 'product_variation') LIMIT 1", $product->name	 ) );
				if(!$product_id){
				
					$export_mappings = isset( CP()->qbo->import_fields ) ? CP()->qbo->import_fields : null;
					if(isset($export_mappings)){
						$new_product = array(
							'post_title'   => wc_clean( $product->$export_mappings['name'] ),
							'post_status'  => ( isset( $this->qbo->product_status ) ? wc_clean( $this->qbo->product_status  ) : 'pending' ),
							'post_type'    => 'imported_item',
							'post_excerpt' => ( isset( $product->description ) ? wc_clean( $product->description ) : '' ),
							'post_content' => ( isset( $product->description ) ? wc_clean( $product->description ) : '' ),
							'post_author'  => get_current_user_id(),
							
						);
						
					}else{
						$new_product = array(
							'post_title'   => wc_clean( $product->name ),
							'post_status'  => ( isset( $this->qbo->product_status  ) ? wc_clean( $this->qbo->product_status  ) : 'pending' ),
							'post_type'    => 'imported_item',
							'post_excerpt' => ( isset( $product->description ) ? wc_clean( $product->description ) : '' ),
							'post_content' => ( isset( $product->description ) ? wc_clean( $product->description ) : '' ),
							'post_author'  => get_current_user_id(),
						);
					}
			
					// Attempts to create the new product
					$id = wp_insert_post( $new_product, true );
					wp_set_object_terms( $id , 'Import', 'import_status'. false );
					// Checks for an error in the product creation
					if ( is_wp_error( $id ) ) {
						return new WP_Error( 'cp_api_cannot_create_product', $id->get_error_message(), array( 'status' => 400 ) );
					}
					// Save product meta fields
					//$meta = $this->save_product_meta( $id, $product );
					// if(isset($product->image) && $product->image != ''){
						// $this->save_image( $product->image, $id );
					// }
					if($product->id && $product->id !=''){
						update_post_meta( $id, 'qbo_product_id', cptexturize($product->id) );
					}
					$meta = update_post_meta( $id, 'product_meta_object', maybe_serialize( $product ) );
					$meta = update_post_meta( $id, 'product_meta_object', maybe_serialize( $product ) );
					//wc_delete_product_transients( $id );
					if ( is_wp_error( $meta ) ) {
						return $meta;
					}
				}
			}
		}
		function save_image( $url, $post_id ){
			$tmp = download_url( $url );
			
			// fix filename for query strings
			preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', urldecode($url), $matches );
			
			$file_array = array(
			    'name'     => $post_id .  '_' . basename( $matches[0] ),
			    'tmp_name' => $tmp
			);
			
			// Check for download errors
			if ( is_wp_error( $tmp ) ) {
			    @unlink( $file_array['tmp_name'] );
			    return false;
			}
			
			$id = media_handle_sideload( $file_array, $post_id );
			
			// Check for handle sideload errors.
			if ( is_wp_error( $id ) ) {
			    @unlink( $file_array['tmp_name'] );
			    return false;
			}
			
			// Set post thumbnail.
			set_post_thumbnail( $post_id, $id );
		}
		function merge( $post_id ){
			if( set_post_type( $post_id, 'product' ) ){
				$product = maybe_unserialize( get_post_meta( $post_id , 'product_meta_object', true ) );
				$this->save_product_meta( $post_id, $product );	
				wc_delete_product_transients( $post_id );
				return true;
			}else{
				return false;
			}
			
			//$meta = update_post_meta( $post_id, 'merged', 'yes' );
		}
		function save_product_meta($id, $product){
			$product_type = null;
			$export_mappings = CP()->qbo->import_fields;
			if ( isset( $product->type ) ) {
				switch ($product->type) {
					case 'Inventory':
						$type = 'simple';	
						break;
					case 'Noninventory':
						$type = 'simple';	
						break;
					case 'Service':
						$type = 'simple';
						$product->virtual = true;
						break;
				}
				$product_type = wc_clean( $product->type );
				wp_set_object_terms( $id, $type, 'product_type' );
			} 
			
	
			// Tax status
			if ( isset( $product->taxable ) ) {
				if($product->taxable == true){
					update_post_meta( $id, '_tax_status', wc_clean( 'taxable' ) );
				}else{
					update_post_meta( $id, '_tax_status', wc_clean( 'none' ) );
				}
			}
			if ( isset( $product->id ) ) {
				update_post_meta( $id, 'qbo_product_id', $product->id );
			}
			// Tax Class
			if ( isset( $product->tax_class ) ) {
				update_post_meta( $id, '_tax_class', wc_clean( $product->tax_class ) );
			}
	
			if ( isset( $product->name ) ) {
				if(isset($product->sku) && $product->sku !='' ){
					$new_sku 	= $product->sku;
				}elseif(isset($product->full_name) && $product->full_name !='' ){
					$new_sku 	= $product->full_name;	
				}else{
					$new_sku 	= $product->name;
				}
				
				$unique_sku = wc_product_has_unique_sku( $id, $new_sku );
				if ( ! $unique_sku ) {
					return new WP_Error( 'cp_api_product_sku_already_exists', __( 'The SKU already exists on another product', 'cartpipe' ), array( 'status' => 400 ) );
				} else {
					update_post_meta( $id, '_sku', $new_sku );
				}
			} 
			if ( isset( $product->price ) ) {
				$regular_price = wc_format_decimal(  $product->price );
				update_post_meta( $id, '_regular_price', $regular_price );
				update_post_meta( $id, '_price', $regular_price );
			}
			
			if ( $product->track_qty == 'true'  ) {
				$stock_status = ( ('true' === $product->track_qty) && (intval($product->qty) >  0 )) ? 'instock' : 'outofstock';
			} else {
				$stock_status = 'instock';
			}
	
			// Stock Data
			if ( 'yes' == get_option( 'woocommerce_manage_stock' ) ) {
				// Manage stock
				if ( $product->track_qty == 'true' ) {
					$managing_stock = 'yes';
					update_post_meta( $id, '_manage_stock', $managing_stock );
				} elseif ( intval($product->qty ) > 0 ) {
					$managing_stock = 'yes';
					update_post_meta( $id, '_manage_stock', $managing_stock );
				} else {
					$managing_stock = 'no';
				}
				if ( 'yes' == $managing_stock ) {
					wc_update_product_stock_status( $id, $stock_status );
	
					// Stock quantity
					if ( isset( $product->qty ) ) {
						wc_update_product_stock( $id, intval( $product->qty ) );
					}
				} else {
					update_post_meta( $id, '_manage_stock', 'no' );
					update_post_meta( $id, '_stock', '' );
					wc_update_product_stock_status( $id, $stock_status );
				}
	
			} else {
				wc_update_product_stock_status( $id, $stock_status );
			}
			update_post_meta( $id, 'qbo_product_id', $product->id );
			update_post_meta( $id, 'qbo_data', $product );
			update_post_meta( $id, 'qbo_last_updated', current_time('timestamp') );
			wp_set_object_terms( $id , 'in-quickbooks', 'qb_status'. false );	
		}
		function cp_qbo_update_customer_info($id, $data){
			if ( isset( $data['qbo_cust_id'] ) ) {
				update_user_meta( $id, 'qbo_cust_id', wc_clean( $data['qbo_cust_id'] ) );
			}
			if ( isset( $data['qbo_FullyQualifiedName'] ) ) {
				update_user_meta( $id, 'qbo_FullyQualifiedName', wc_clean( $data['qbo_FullyQualifiedName'] ) );
			}
		}
		function has_transferred( $data ){
			$has_transferred = false;
            if( $data ){
    			if( sizeof($data ) > 0){
        			foreach ( $data as $key=>$value ){
        				
        				switch ($key) {
        					case 'sales_recipt':
        					case 'sales_receipt':
        						if($value->has_transferred){
        							$has_transferred = true;			
        						}
        						break;
        					
        					case 'invoice':
        						if($value->has_transferred){
        							$has_transferred = true;			
        						}
        						break;
        					case 'payment':
        						if($value->has_transferred){
        							$has_transferred = true;			
        						}
        						break;
        				}
        			}
    			}
            }
			return $has_transferred;
		}
		function cp_qbo_check_customer_exists( $order_id, $posted ){
			$post = array(
			  'post_title'    	=> 'Order #'. $order_id . ' &#8658; check customer',
			  'post_content'  	=> '',
			  'post_status'   	=> 'publish',
			  'post_type'		=> 'cp_queue'
			);

			// Insert the post into the database
			$post_id = wp_insert_post( $post );
			if( $post_id ){
				wp_set_object_terms( $post_id , 'check-customer', 'queue_action' );
				wp_set_object_terms( $post_id , 'queued', 'queue_status' );
				update_post_meta( $post_id, 'reference_post_id', $order_id );
			}
		}
		function cp_add_refund( $refund_id ){
			
			$data = maybe_unserialize( get_post_meta( $refund_id, '_quickbooks_data', true ) );
			if(CP()->qbo->license_info->level != 'Basic'){
				if( !$data || ($data && $force) ){
					$post = array(
					  'post_title'    	=> 'Refund #'. $refund_id . '  &#8658;  Create Refund in QuickBooks',
					  'post_content'  	=> '',
					  'post_status'   	=> 'publish',
					  'post_type'		=> 'cp_queue'
					);
		
					// Insert the post into the database
					$post_id = wp_insert_post( $post );
					if( $post_id ){
						wp_set_object_terms( $post_id , 'create-refund', 'queue_action' );
						wp_set_object_terms( $post_id , 'queued', 'queue_status' );
						update_post_meta( $post_id, 'reference_post_id', $refund_id );
					}
				}else{
					CPM()->add_message( 'Refund #' . $refund_id . ' has already been sent.');
				}
			}
			return $post_id;
		} 
		function sod_qbo_send_order( $order_id, $force = false ){
			$data = maybe_unserialize( get_post_meta( $order_id, '_quickbooks_data', true ) );
			$queued		= get_post_meta( $order_id,'_cp_is_queued', true);
			//Hasn't been sent
			if(CP()->qbo->license_info->level != 'Basic'){
				if( ( !$data || ($data && $force) ) && !$queued){
					$post = array(
					  'post_title'    	=> 'Order #'. $order_id . ' &#8658; ' . str_replace( '-', '', $this->qbo->order_type ),
					  'post_content'  	=> '',
					  'post_status'   	=> 'publish',
					  'post_type'		=> 'cp_queue'
					);
					
					// Insert the post into the database
					$post_id = wp_insert_post( $post );
							
					if( $post_id ){
						wp_set_object_terms( $post_id , 'create-'.$this->qbo->order_type, 'queue_action' );
						wp_set_object_terms( $post_id , 'queued', 'queue_status' );
						update_post_meta( $post_id, 'reference_post_id', $order_id );
						update_post_meta( $order_id,'_cp_is_queued', 'yes');
					}
					if(CP()->qbo->enable_pdfs == 'yes'){
						$pdf = array(
						  'post_title'    	=> 'Order #'. $order_id . ' &#8658;' . str_replace( '-', '', $this->qbo->order_type ) . 'PDF',
						  'post_content'  	=> '',
						  'post_status'   	=> 'publish',
						  'post_type'		=> 'cp_queue'
						);
			
						// Insert the post into the database
						$pdf_id = wp_insert_post( $pdf );
						if( $pdf_id ){
							wp_set_object_terms( $pdf_id , 'create-'. str_replace( '-', '', $this->qbo->order_type ) .'-pdf', 'queue_action' );
							wp_set_object_terms( $pdf_id , 'queued', 'queue_status' );
							update_post_meta( $pdf_id, 'reference_post_id', $order_id );
						}
					}
					update_post_meta( $order_id, '_cp_is_queued', 'yes');
					
				}else{
					CPM()->add_message( 'Order #' . $order_id . ' has already been sent. Please try manually resending if you\'d like to recreate the order in QuickBooks.');
				}
			}
			return $post_id;
		}
	function cp_qbo_conditional_send_payment( $order_id ){
		$data 		= get_post_meta( $order_id, '_qbo_payment_number', true);
		$invoice	= get_post_meta( $order_id, '_qbo_invoice_number', true);
		$queued		= get_post_meta( $order_id,'_cp_is_queued', true);
		if( !$data ){
			if(CP()->qbo->license_info->level != 'Basic'){
				if($this->qbo->order_type == 'invoice' && $this->qbo->create_payment == 'yes'){
					if((!isset($invoice) || $invoice == '' ) && !$queued ){
						$post = array(
						  'post_title'    	=> 'Order #'. $order_id . ' &#8658; Invoice',
						  'post_content'  	=> '',
						  'post_status'   	=> 'publish',
						  'post_type'		=> 'cp_queue'
						);
			
						// Insert the post into the database
						$post_id = wp_insert_post( $post );
						if( $post_id ){
							wp_set_object_terms( $post_id , 'create-invoice', 'queue_action' );
							wp_set_object_terms( $post_id , 'queued', 'queue_status' );
							update_post_meta( $post_id, 'reference_post_id', $order_id );
						}
						update_post_meta( $order_id, '_cp_is_queued', 'yes');
						if(CP()->qbo->enable_pdfs == 'yes'){
							$pdf = array(
							  'post_title'    	=> 'Order #'. $order_id . ' &#8658; Invoice PDF',
							  'post_content'  	=> '',
							  'post_status'   	=> 'publish',
							  'post_type'		=> 'cp_queue'
							);
				
							// Insert the post into the database
							$pdf_id = wp_insert_post( $pdf );
							if( $pdf_id ){
								wp_set_object_terms( $pdf_id , 'create-invoice-pdf', 'queue_action' );
								wp_set_object_terms( $pdf_id , 'queued', 'queue_status' );
								update_post_meta( $pdf_id, 'reference_post_id', $order_id );
							}
						}
						
					}
					$post = array(
					  'post_title'    	=> 'Order #'. $order_id . ' &#8658; Receive Payment On Account',
					  'post_content'  	=> '',
					  'post_status'   	=> 'publish',
					  'post_type'		=> 'cp_queue'
					);
		
					// Insert the post into the database
					$post_id = wp_insert_post( $post );
					if( $post_id ){
						wp_set_object_terms( $post_id , 'create-payment', 'queue_action' );
						wp_set_object_terms( $post_id , 'queued', 'queue_status' );
						update_post_meta( $post_id, 'reference_post_id', $order_id );
					}
					return $post_id;
				}
			}
		}else{
			CPM()->add_message( 'Receive payment for order #' . $order_id . ' has already been recorded in QuickBooks under ');
		}
	}
	function cp_queue_product( $prod_id ){
		if(CP()->qbo->license_info->level != 'Basic'){
			$sku 				= get_post_meta( $prod_id , '_sku', true );
			$post = array(
			  'post_title'    	=> 'Product #'. $prod_id . ', sku ' . $sku  ,
			  'post_content'  	=> '',
			  'post_status'   	=> 'publish',
			  'post_type'		=> 'cp_queue'
			);

			// Insert the post into the database
			$post_id = wp_insert_post( $post );
			if( $post_id ){
				wp_set_object_terms( $post_id , 'sync-item', 'queue_action' );
				wp_set_object_terms( $post_id , 'queued', 'queue_status' );
				update_post_meta( $post_id, 'reference_post_id', $prod_id );
			}
			return $post_id;
			}
		}
	function cp_queue_inventory( ){
		global $wpdb;
		$number_to_send = apply_filters('cartpipe_number_to_send', 100 );
		$product 		= wp_count_posts( 'product' )->publish;
		$variations 	= wp_count_posts( 'product_variation' )->publish;
		$sum 			= $product + $variations;
		$num_pages 		= ceil($sum / $number_to_send);
		$i 				= 0;
		$wc_identifier 	= isset( CP()->qbo->wc_identifier ) ? CP()->qbo->wc_identifier : 'sku';
		$qbo_identifier = isset( CP()->qbo->qbo_identifier ) ? CP()->qbo->qbo_identifier : 'name';
		while ($i < $num_pages ) {
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
					$prod 		= wc_get_product( get_the_ID() );
                //error_log(print_r($prod, true), 3, plugin_dir_path(__FILE__) . "/log.log");
        
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
								'id' 				=>	get_the_ID(),
								'price'				=> 	$prod->get_price(),
								'managing_stock'	=> 	$managing_stock,
								'stock'				=> 	isset($managing_stock) && $managing_stock ? $prod->get_stock_quantity() : false,
								'sku'				=> 	$prod->get_sku(),
								'description'		=> 	wc_clean(substr( get_the_content(), 0, 1000 ) ),
								'name'				=> 	wc_clean(substr( get_the_title(), 0, 100) ),
								'taxable'			=> 	$taxable,
								'active'			=> 	true,
								'wc_product'		=> 	$prod
					);
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
			
			$post = array(
			  'post_title'    	=> 'Syncing Products Part ' . ( $i + 1),
			  'post_content'  	=> '',
			  'post_status'   	=> 'publish',
			  'post_type'		=> 'cp_queue'
			);

			// Insert the post into the database
			$post_id = wp_insert_post( $post );
			if( $post_id ){
				wp_set_object_terms( $post_id , 'sync-inventory', 'queue_action' );
				wp_set_object_terms( $post_id , 'queued', 'queue_status' );
			}
			update_post_meta( $post_id, '_cp_request_data', $prods);
			$i++;
		}
	}
	function cp_import_inventory( ){
		$post = array(
		  'post_title'    	=> 'Import QuickBooks Items into WooCommerce',
		  'post_content'  	=> '',
		  'post_status'   	=> 'publish',
		  'post_type'		=> 'cp_queue'
		);

		// Insert the post into the database
		$post_id = wp_insert_post( $post );
		if( $post_id ){
			wp_set_object_terms( $post_id , 'import-inventory', 'queue_action' );
			wp_set_object_terms( $post_id , 'queued', 'queue_status' );
		}
		return $post_id;
	}
	function import_images( ){
		$post = array(
		  'post_title'    	=> 'Import QuickBooks Item Images into WooCommerce',
		  'post_content'  	=> '',
		  'post_status'   	=> 'publish',
		  'post_type'		=> 'cp_queue'
		);

		// Insert the post into the database
		$post_id = wp_insert_post( $post );
		if( $post_id ){
			wp_set_object_terms( $post_id , 'import-images', 'queue_action' );
			wp_set_object_terms( $post_id , 'queued', 'queue_status' );
		}
		return $post_id;
	}
	function cp_export_inventory( ){
		$post = array(
		  'post_title'    	=> 'Export WooCommerce Products Into QuickBooks',
		  'post_content'  	=> '',
		  'post_status'   	=> 'publish',
		  'post_type'		=> 'cp_queue'
		);

		// Insert the post into the database
		$post_id = wp_insert_post( $post );
		if( $post_id ){
			wp_set_object_terms( $post_id , 'export-inventory', 'queue_action' );
			wp_set_object_terms( $post_id , 'queued', 'queue_status' );
		}
		return $post_id;
	}
	function get_pdfs(){
		$post = array(
		  'post_title'    	=> 'Back-fill missing PDFs for Orders sent to QuickBooks',
		  'post_content'  	=> '',
		  'post_status'   	=> 'publish',
		  'post_type'		=> 'cp_queue'
		);

		// Insert the post into the database
		$post_id = wp_insert_post( $post );
		if( $post_id ){
			wp_set_object_terms( $post_id , 'generate-pdfs', 'queue_action' );
			wp_set_object_terms( $post_id , 'queued', 'queue_status' );
		}
		return $post_id;
	}
	function check_license( ){
		
			
			$post = array(
			  'post_title'    	=> 'Check Service',
			  'post_content'  	=> '',
			  'post_status'   	=> 'publish',
			  'post_type'		=> 'cp_queue'
			);

			// Insert the post into the database
			$post_id = wp_insert_post( $post );
			if( $post_id ){
				wp_set_object_terms( $post_id , 'check-service', 'queue_action' );
				wp_set_object_terms( $post_id , 'queued', 'queue_status' );
			}
			return $post_id;
		}
	function cp_queue_page_columns($columns){
			$columns['reference_post_id'] 	= 'Reference Item';
			$columns['queue_message'] 		= 'Message';
			return ($columns);
		}
	function cp_lookup_queue_items( $post_id ){
		$post_type = get_post_type( $post_id );
		switch ($post_type) {
			case 'shop_order':
				$args = array(
					'post_type'  		=> 'cp_queue',
					'meta_key' 			=> 'reference_post_id',
					'meta_value'		=> $post_id,
					'posts_per_page'	=> -1,
					
				);
				$items = new WP_Query( $args );			
				break;
			
			case 'product':
				$args = array(
					'post_type'  		=> 'cp_queue',
					'posts_per_page'	=> -1,
					'order'				=> 'DESC',
					'orderby'			=> 'date',
					'tax_query' => array(
						array(
							'taxonomy' => 'queue_action',
							'field' => 'slug',
							'terms' => array('sync-inventory')
							)
						)
					);
				$sync = new WP_Query( $args ); 
				$args = array(
					'post_type'  		=> 'cp_queue',
					'meta_key' 			=> 'reference_post_id',
					'meta_value'		=> $post_id,
					'posts_per_page'	=> -1,
					'order'				=> 'DESC',
					'orderby'			=> 'date',
					'tax_query' => array(
						array(
							'taxonomy' => 'queue_action',
							'field' => 'slug',
							'terms' => array('sync-item')
							)
						)
					);
				$item 				= new WP_Query( $args );	
				$last_updated 		= get_post_meta( $post_id, 'qbo_last_updated', true);
				if(!$last_updated){
					$posts 			= $sync->posts;
					$last_updated	= $posts[0]->post_date;
					
				}
				if(is_numeric($last_updated)){
					$last_updated = date('D, d M Y H:i:s', $last_updated);
				}
				if($item->posts){
					$item->posts 	= array_merge( $sync->posts, $item->posts );
				}
				break;
		}
		
		if(isset($items->posts)):
			return $items->posts;
		else:
			return array();
		endif;
	}
	function cp_lookup_fallout_items( $post_id ){
		$post_type = get_post_type( $post_id );
		switch ($post_type) {
			case 'shop_order':
				$args = array(
					'post_type'  		=> 'cp_fallout',
					'meta_key' 			=> 'reference_post_id',
					'meta_value'		=> $post_id,
					'posts_per_page'	=> -1,
					
				);
				$items = new WP_Query( $args );			
				break;
			
			case 'product':
				$args = array(
					'post_type'  		=> 'cp_fallout',
					'posts_per_page'	=> -1,
					'order'				=> 'DESC',
					'orderby'			=> 'date',
					'tax_query' => array(
						array(
							'taxonomy' => 'queue_action',
							'field' => 'slug',
							'terms' => array('sync-inventory')
							)
						)
					);
				$sync = new WP_Query( $args ); 
				$args = array(
					'post_type'  		=> 'cp_queue',
					'meta_key' 			=> 'reference_post_id',
					'meta_value'		=> $post_id,
					'posts_per_page'	=> -1,
					'order'				=> 'DESC',
					'orderby'			=> 'date',
					'tax_query' => array(
						array(
							'taxonomy' => 'queue_action',
							'field' => 'slug',
							'terms' => array('sync-item')
							)
						)
					);
				$item 				= new WP_Query( $args );	
				$last_updated 		= get_post_meta( $post_id, 'qbo_last_updated', true);
				if(!$last_updated){
					$posts 			= $sync->posts;
					$last_updated	= $posts[0]->post_date;
					
				}
				if(is_numeric($last_updated)){
					$last_updated = date('D, d M Y H:i:s', $last_updated);
				}
				 
				if($item->posts){
					$item->posts 	= array_merge( $sync->posts, $item->posts );
				}
				break;
		}
		
		if(isset($items->posts)):
			return $items->posts;
		else:
			return array();
		endif;
	}
	function cp_insert_queue_item( $ref_id, $action, $status ){
		$post = array(
		  'post_title'    	=> 'Order #'. $ref_id . ' - ' . str_replace( '-', '', $action ) ,
		  'post_content'  	=> '',
		  'post_status'   	=> 'publish',
		  'post_type'		=> 'cp_queue'
		);

		// Insert the post into the database
		$post_id = wp_insert_post( $post );
		if( $post_id ){
			wp_set_object_terms( $post_id , $action, 'queue_action' );
			wp_set_object_terms( $post_id , $status, 'queue_status' );
			update_post_meta( $post_id, 'reference_post_id', $ref_id );
		}
	}
	function cp_insert_fallout( $title, $ref_id, $error, $action, $type ){
		$post = array(
		  'post_title'    	=> $title ,
		  'post_content'  	=> '',
		  'post_status'   	=> 'publish',
		  'post_type'		=> 'cp_fallout'
		);

		// Insert the post into the database
		$post_id = wp_insert_post( $post );
		if( $post_id ){
			wp_set_object_terms( $post_id , $error, 'error_code' );
			wp_set_object_terms( $post_id , $type, 'fallout_type' );
			wp_set_object_terms( $post_id , $action, 'fallout_action' );
			update_post_meta( $post_id, 'reference_post_id', $ref_id );
		}
		return $post_id;
	}
	function cp_queue_custom_columns($column, $post_id )	{
			global $post;
			if($column == 'reference_post_id'){
				
				$ref_id 		= get_post_meta($post->ID, 'reference_post_id', true);
				$post_type 		= get_post_type($ref_id);
				$post_type_obj 	= get_post_type_object( $post_type ); 
				echo $post_type_obj->labels->singular_name . ' ' . $ref_id;
			};
		}
		
	}

		
}
function CP() {

	return CP_QBO_Client::instance();
}
if(!CP()->client){
	CP()->init_client();
}

// Global for backwards compatibility.
$GLOBALS['CP_QBO_Client'] = CP();