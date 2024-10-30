<?php
/**
 * QuickBooks Online Integration Setup Wizard
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Setup_Wizard class
 */
 
class CP_Setup_Wizard {
	
	/** @var string Currenct Step */
	private $step   = '';

	/** @var array Steps for the setup wizard */
	private $steps  	= array();
	private $settings 	= array(
		
	);
	private $already_signed_up = false;
	
	
	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'cp_start_setup_wizard', true ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
			
		}
	}
	
	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'cp-setup', '' );
	}

	/**
	 * Show the setup wizard
	 */
	public function setup_wizard() {
		
		if ( empty( $_GET['page'] ) || 'cp-setup' !== $_GET['page'] ) {
			return;
		}
		$this->steps = array(
			'introduction' => array(
				'name'    =>  __( 'Introduction', 'cartpipe' ),
				'view'    => array( $this, 'cp_intro' ),
				'handler' => ''
			),
			'credentials' => array(
				'name'    =>  __( 'Credentials', 'cartpipe' ),
				'view'    => array( $this, 'cp_credentials' ),
				'handler' => array( $this, 'cp_credentials_save' )
			),
			'quickbooks' => array(
				'name'    =>  __( 'QuickBooks Account', 'cartpipe' ),
				'view'    => array( $this, 'cp_quickbooks' ),
				'handler' => array( $this, 'cp_quickbooks_save' )
			),
			'sync_options' => array(
				'name'    =>  __( 'Sync Options', 'cartpipe' ),
				'view'    => array( $this, 'cp_sync_mappings' ),
				'handler' => array( $this, 'cp_sync_mappings_save' ),
			),
			'taxes' => array(
				'name'    =>  __( 'Taxes', 'cartpipe' ),
				'view'    => array( $this, 'cp_tax_mappings' ),
				'handler' => array( $this, 'cp_tax_mappings_save' )
			),
			'payments' => array(
				'name'    =>  __( 'Payments', 'cartpipe' ),
				'view'    => array( $this, 'cp_setup_payments' ),
				'handler' => array( $this, 'cp_setup_payments_save' ),
			),
			'next_steps' => array(
				'name'    =>  __( 'Ready!', 'cartpipe' ),
				'view'    => array( $this, 'cp_setup_ready' ),
				'handler' => ''
			)
		);
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );
		$this->get_settings( $this->step );
		wp_enqueue_style( 'cp-admin-css', CP()->plugin_url() . '/assets/css/cp.css', array(), CP_VERSION );
		wp_enqueue_style( 'cp-setup', CP()->plugin_url() . '/assets/css/cp-setup.css', array( 'dashicons', 'install' ), CP_VERSION );
		wp_register_script( 'cp-setup', CP()->plugin_url() . '/assets/js/cp.order.metabox.js', array( 'jquery'), CP_VERSION );
		wp_enqueue_script( 'ipp-anywhere', '//appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js', array(), CP_VERSION, false );
		wp_enqueue_style( 'cp-font-awesome',CP()->plugin_url() . '/assets/css/font-awesome.min.css', false, CP_VERSION );
		$setup_nonce 	= wp_create_nonce( "cp-setup" );
		$setup_data = array(
					'ajax_url'				=> admin_url('admin-ajax.php'),
					'next_step'				=> $this->get_next_step_link(),
					'setup_nonce'			=> $setup_nonce,
					'email_alert'			=> __('Check your email and make sure it\'s valid','cartpipe'),
					'password_alert'			=> __('Check your password and make sure it\'s set','cartpipe')
				);
		wp_localize_script( 'cp-setup', 'cp_setup', $setup_data );
		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this->settings);
		}
		$this->already_signed_up = get_option('cp_already_activate');
		ob_start();
		$this->cp_wizard_header();
		$this->cp_wizard_steps();
		$this->cp_wizard_content();
		$this->cp_wizard_footer();
		exit;
	}

	public function get_next_step_link() {
		$keys = array_keys( $this->steps );
		return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ) ) + 1 ] );
	}
	public function get_step_link() {
		$keys = array_keys( $this->steps );
		return add_query_arg( 'step', $this->step );
	}
	/**
	 * Setup Wizard Header
	 */
	public function cp_wizard_header() {
		?>
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php _e( 'Cartpipe  &#8658; Setup Wizard', 'cartpipe' ); ?></title>
			<?php wp_print_scripts( array('cp-setup', 'jquery', 'ipp-anywhere') ); ?>
			<?php do_action( 'admin_print_styles' );  ?>
		</head>
		<body class="cp-setup wp-core-ui">
			<h1 id="cp-logo"><a href="<?php echo esc_url(admin_url('admin.php?page=qbo-settings'));?>"><img src="<?php echo CP()->plugin_url(); ?>/assets/images/cp_logo.png" alt="Cartpipe QuickBooks Online" /></a></h1>
		<?php
	}

	/**
	 * Setup Wizard Footer
	 */
	public function cp_wizard_footer() {
		
		?>
			<?php if ( 'next_steps' === $this->step ) : ?>
				<a class="cp-return-to-dashboard" href="<?php echo esc_url( admin_url('admin.php?page=qbo-settings') ); ?>"><?php _e( 'Return to the WordPress Dashboard', 'cartpipe' ); ?></a>
			<?php endif; ?>
			<script type="text/javascript">
				intuit.ipp.anywhere.setup({
					menuProxy: '<?php echo CP_URL . '/qbo-menu/' . CP()->qbo->license .'/';?>',
					grantUrl: 	'<?php echo CP_API . '/oauth/' . CP()->qbo->license . '/';?>',
					quickbooks : true,
            		payments : false
				});
			</script>
			</body>
		</html>
		<?php
	}

	/**
	 * Output the steps
	 */
	public function cp_wizard_steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="cp-setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) :
				
			 ?>
				<li class="<?php
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
						echo 'done';
					}
				?>"><a href="<?php echo add_query_arg( 'step', $step_key );?>"><?php echo esc_html( $step['name'] ); ?></a></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step
	 */
	public function cp_wizard_content() {
		
		echo '<div class="cp-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'] );
		echo '</div>';
	}

	/**
	 * Introduction step
	 */
	public function cp_intro() {
		?>
		<h1><?php _e( 'Welcome to Cartpipe!', 'cartpipe' ); ?></h1>
		<p><?php _e( 'You\'re about to take control of your website accounting. We\'ll walk you through the basic steps to get WooCommerce connected to QuickBooks. <strong>Don\'t worry. It\'s completely optional and shouldn\'t take more than a couple of minutes.</strong>', 'cartpipe' ); ?></p>
		<p><?php _e( 'In a rush? You can always skip this step and return to the Wordpress Dashboard.', 'cartpipe' ); ?></p>
		<p class="cp-setup-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large"><?php _e( 'Start', 'cartpipe' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=qbo-settings' ) ); ?>" class="button button-large"><?php _e( 'Not right now', 'cartpipe' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Enter Credentials
	 */
	public function cp_credentials() {
		?>
		<h1><?php _e( 'Cartpipe Credentials', 'cartpipe' ); ?></h1>
		<p class="status hidden">
		</p>
		<form method="post">
			<div class="table-wrapper">
				<div class="status-updates">
					<h3><?php _e('Great! Let\'s Get You Signed Up!', 'cartpipe');?></h3>
					<div class="status-wrapper">
						<h4><?php _e('Completed Steps', 'cartpipe');?></h4>
						<ul class="completed-steps"></ul>
					</div>
					<div class="current-step">
						<h4><?php _e('Current Step', 'cartpipe');?></h4>
						<i class="fa fa-cog fa-fw fa fa-cog fa-spin"></i><span class="desc"></span>
					</div>
				</div>
			
				<p><?php  _e( 'We need a couple of pieces of info to get things started. You received some credentials when signing up for Cartpipe. Let\'s enter them below' ); ?></p>
			
				<table class="form-table">
					<tbody>
						<?php	QBO_Admin_Settings::output_fields($this->settings);	?>				
					</tbody>
				</table>
			</div>
			<p class="cp-setup-actions step">
				<input type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Continue', 'cartpipe' ); ?>" name="save_step" />
				<?php wp_nonce_field( 'cp-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save Page Settings
	 */
	public function cp_credentials_save($settings) {
		check_admin_referer( 'cp-setup' );
		//var_dump($settings);
		
		if( isset( $_POST['cp']['free_trial']) && $_POST['cp']['free_trial'] =='1' ){
			
			if(isset($_POST['cp']['email_address']) && $_POST['cp']['email_address'] != ''){
				$email = $_POST['cp']['email_address'];
				unset($_POST['cp']['email_address']);	
			}
			if(isset($_POST['cp']['password']) && $_POST['cp']['password'] != ''){
				$password = $_POST['cp']['password'];
				unset($_POST['cp']['password']);	
			}
			
			unset($_POST['cp']['free_trial']);
				
			if($email && $password){
				
				$data = array(
					'email'		=> $email,
					'password'	=> $password
				);
				if(!CP()->client){
					CP()->init_client();
				}
				$license_info = CP()->client->free_trial( $data );
				
				if( $license_info->errors){
					update_option('cp_already_activate', 'yes');
					wp_redirect( $this->get_step_link());
					exit;
					
				}else{
					if( $license_info->consumer_key ){
						$_POST['qbo']['consumer_key'] = $license_info->consumer_key;
					}
					if( $license_info->consumer_secret ) {
						$_POST['qbo']['consumer_secret'] = $license_info->consumer_secret;
					}
					if( $license_info->license ){
						$_POST['qbo']['license'] = $license_info->license;
						$client 				= new CP_Client( $license_info->consumer_key, $license_info->consumer_secret, CP_API );
						$license_data 			= $client->cp_activate_license( $license_info->license, get_home_url() );
						$qbo 					= maybe_unserialize( get_option('qbo') );
						$qbo['license_info']	= $license_data;
						update_option('qbo', $qbo);
					}
				}	
			}
		}elseif( (isset( $_POST['qbo']['consumer_key']) && $_POST['qbo']['consumer_key'] !='') && (isset( $_POST['qbo']['consumer_secret']) && $_POST['qbo']['consumer_secret'] !='')){
			$client 				= new CP_Client( $_POST['qbo']['consumer_key'], $_POST['qbo']['consumer_secret'], CP_API );
			$license_data 			= $client->cp_activate_license( $license_info->license, get_home_url() );
			$qbo 					= maybe_unserialize( get_option('qbo') );
			$qbo['license_info']	= $license_data;
			update_option('qbo', $qbo);
		}
		QBO_Admin_Settings::save_fields($settings);
		
		wp_redirect( $this->get_next_step_link() );
		exit;
	}
	public function cp_quickbooks(){
		if(CP()->qbo->license){
			$company_info	= CP()->client->company_info( CP()->qbo->license );
			if($company_info->company_name ){
				
			?>
			<h3><?php _e('QuickBooks Company Info', 'cartpipe');?></h3>
			<ul>
				<li><?php printf('%s : %s', __('Company', 'cartpipe'), $company_info->company_name ); ?></li>
				<li><?php printf('%s : %s', __('Email', 'cartpipe'),  $company_info->company_email ); ?> </li>
				<li><?php printf('%s : %s', __('Fiscal Start Month', 'cartpipe'), $company_info->company_fiscal_start ); ?> </li>
				<li><?php printf('%s : %s', __('Country', 'cartpipe'), $company_info->company_country ); ?> </li>
			</ul>
			<form method="post">
			<p class="cp-setup-actions step">
				<input type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Continue', 'cartpipe' ); ?>" name="save_step" />
				<?php wp_nonce_field( 'cp-setup' ); ?>
			</p>
			</form>
		<?php 
			}else{?>
				<h3 class="uk-panel-title"><?php _e('QuickBooks Online Connection', 'cartpipe');?></h3>
				<p><?php _e('Click the button below to link your QuickBooks Online Account to your website', 'cartpipe');?></p>
				<ipp:connectToIntuit></ipp:connectToIntuit>
				<form method="post">
					<p class="cp-setup-actions step">
						<input type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Continue', 'cartpipe' ); ?>" name="save_step" />
						<?php wp_nonce_field( 'cp-setup' ); ?>
					</p>
				</form>
			<?php 
			}
			}else{
		?>
		<h3 class="uk-panel-title"><?php _e('QuickBooks Online Connection', 'cartpipe');?></h3>
		<p><?php _e('Click the button below to link your QuickBooks Online Account to your website', 'cartpipe');?></p>
		<ipp:connectToIntuit></ipp:connectToIntuit>
				<form method="post">
					<p class="cp-setup-actions step">
						<input type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Continue', 'cartpipe' ); ?>" name="save_step" />
						<?php wp_nonce_field( 'cp-setup' ); ?>
					</p>
				</form>
	<?php 
		}
	}
	public function cp_quickbooks_save(){
		wp_redirect( $this->get_next_step_link() );
	}
	public function get_settings( $step ){
		if(!CP()->client){
			CP()->init_client();
		}
		switch ($step) {
			case 'credentials':
				$this->settings = array(
					array(
						'title'             => __( 'Consumer Key', 'cartpipe' ),
						'desc'              => __( 'Enter the consumer key you received when signing up for the QuickBooks Online Integration.', 'cartpipe' ),
						'id'                => 'qbo[consumer_key]',
						'type'              => 'text',
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'             => __( 'Consumer Secret', 'cartpipe' ),
						'desc'              => __( 'Enter the consumer secret you received when signing up for the QuickBooks Online Integration.', 'cartpipe' ),
						'id'                => 'qbo[consumer_secret]',
						'type'              => 'text',
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'             => __( 'CartPipe License Key', 'cartpipe' ),
						'desc'              => __( 'Enter your license key for the CartPipe / QuickBooks Online Integration. Entering your license key will activate the cartpipe service for this site.', 'cartpipe' ),
						'id'                => 'qbo[license]',
						'type'              => 'text',
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					
				);
				if( !CP()->qbo->license && !CP()->qbo->consumer_key && !CP()->qbo->consumer_secret ){
					$this->settings[]  = array(
						'title'			=> __( 'Don\'t have a Cartpipe.com Account?', 'cartpipe' ),
						'tip'          => __( 'Do you need to create a Cartpipe.com Account? Check this box to create an account and retrieve your consumer key, consumer secret and license', 'cartpipe' ),
						'desc'          => __( 'Do you need to create a Cartpipe.com Account? Check this box to create an account and retrieve your consumer key, consumer secret and license', 'cartpipe' ),
						'id'            => 'cp[free_trial]',
						'default'       => 'no',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					);
					$this->settings[]  = array(
						'title'             => __( 'CartPipe.com Account Email', 'cartpipe' ),
						'desc'              => __( 'Enter the email address you\'d like to use for your Cartpipe.com Account', 'cartpipe' ),
						'id'                => 'cp[email_address]',
						'type'              => 'text',
						'class'				=> 'hidden',
						'css'               => '',
						'default'           => get_option('admin_email'),
						'autoload'          => false
					);
					$this->settings[]  = array(
						'title'             => __( 'CartPipe.com Account Password', 'cartpipe' ),
						'desc'              => __( 'Enter the password you\'d like to use for your Cartpipe.com Account', 'cartpipe' ),
						'id'                => 'cp[password]',
						'type'              => 'password',
						'css'               => '',
						'default'           => '',
						'class'				=> 'hidden',
						'autoload'          => false
					);
				}
				break;
			case 'sync_options':
				$this->settings = array(
					array(
						'title'			=> __( 'Sync Price?', 'cartpipe' ),
						'tip'          => __( 'Check this box to have the website product prices be updated with prices from QuickBooks Online', 'cartpipe' ),
						'desc'          => __( 'Do you want to update the product prices on the website with the prices from QuickBooks?', 'cartpipe' ),
						'id'            => 'qbo[sync_price]',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),
	
					array(
						'title'			=> __( 'Sync Stock?', 'cartpipe' ),
						'tip'			=> __( 'Check this box to have the website product stock be updated with on-hand qty\'s from QuickBooks Online', 'cartpipe' ),
						'desc'          => __( 'Do you want to update the website quantities with on-hand qty\'s from QuickBooks Online?', 'cartpipe' ),
						'id'            => 'qbo[sync_stock]',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),
					array(
						'title'			=> __( 'Store Cost?', 'cartpipe' ),
						'tip'			=> __( 'Check this box to have the Cost of Goods from QuickBooks stored with the product in WooCommerce. This will create a new field below Sale Price on the product page.', 'cartpipe' ),
						'desc'          => __( 'Do you want to store the cost from QuickBooks on the website?', 'cartpipe' ),
						'id'            => 'qbo[store_cost]',
						'default'       => 'no',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
						'autoload'      => false
					),array(
						'title'			=> __( 'Sync Frequency?', 'cartpipe' ),
						'tip'          => __( 'Check this box to have the website product prices be updated with prices from QuickBooks Online', 'cartpipe' ),
						'desc'          => __( 'How often should the invenotry syncing run?', 'cartpipe' ),
						'id'            => 'qbo[frequency]',
						'default'       => 'yes',
						'type'          => 'select',
						'options'		=> array(
											'14400' => 'Every 4 Hours',
											'43200' => 'Every 12 Hours',
											'86400' => 'Every 24 Hours'
											),
						'autoload'      => false
					),
				);
				
				break;
				
			case 'taxes':
				$tax_rates 		= $this->get_tax_rates();
				
				$taxes 			= CP()->client->qbo_get_sales_tax_info( CP()->qbo->license ) ;
				
				if($taxes->errors ){
					$taxes = array();
				}
				$tax_classes 	= $this->get_tax_classes();
				$tax_codes		= CP()->client->qbo_get_sales_tax_codes( CP()->qbo->license );
				if($tax_codes->errors ){
					$tax_codes = array();
				}
				$use_taxes 		= get_option('woocommerce_calc_taxes', true);
				$country 		= WC_Countries::get_base_country();
				if($use_taxes){
					switch ($country) {
						case 'US':
							$this->settings = array(
								array(
									'title'             => __( 'Tax Rate Mappings', 'cartpipe' ),
									'desc'              => __( 'Please map your website tax rates to the corresponding tax rate in QuickBooks Online.', 'cartpipe' ),
									'id'                => 'qbo[taxes]',
									'type'              => 'mapping',
									'options'			=> $taxes,//wc_get_order_statuses(),
									'labels'			=> $tax_rates,
									'auto_create'		=> true, 
									'css'               => '',
									'default'           => '',
									'autoload'          => false
								),
								array(
									'title'             => __( 'Tax Code Mappings', 'cartpipe' ),
									'desc'              => __( 'Please map your website tax classes to the corresponding tax code in QuickBooks Online.', 'cartpipe' ),
									'id'                => 'qbo[tax_codes]',
									'type'              => 'mapping',
									'options'			=> $tax_codes,//wc_get_order_statuses(),
									'labels'			=> $tax_classes,
									'css'               => '',
									'default'           => '',
									'autoload'          => false
								),
							);
							break;
						case 'CA':
							$this->settings = array(
								array(
									'title'             => __( 'Tax Rate Mappings', 'cartpipe' ),
									'desc'              => __( 'Please map your website tax rates to the corresponding tax rate in QuickBooks Online.', 'cartpipe' ),
									'id'                => 'qbo[taxes]',
									'type'              => 'mapping',
									'options'			=> $taxes,//wc_get_order_statuses(),
									'labels'			=> $tax_rates,
									'auto_create'		=> true, 
									'css'               => '',
									'default'           => '',
									'autoload'          => false
								),
								array(
										'title'             => __( 'Exempt Sales Tax Rate Mapping', 'cartpipe' ),
										'desc'              => __( 'Please map the QuickBooks Sales Tax Rate to use when tax wasn\'t collected, i.e. for foreign orders.', 'cartpipe' ),
										'id'                => 'qbo[zero_tax_code]',
										'type'              => 'select',
										'options'			=> $taxes,//wc_get_order_statuses(),
										'css'               => '',
										'default'           => '',
										'autoload'          => false
								),
								array(
										'title'             => __( 'In-Country Shipping Item Tax Code Mapping', 'cartpipe' ),
										'desc'              => __( 'Please map your shipping item tax code to the corresponding tax code in QuickBooks Online.', 'cartpipe' ),
										'id'                => 'qbo[shipping_item_taxcode]',
										'type'              => 'select',
										'options'			=> $tax_codes,//wc_get_order_statuses(),
										'css'               => '',
										'default'           => '',
										'autoload'          => false
								),
								array(
										'title'             => __( 'Foreign Country Shipping Item Tax Code Mapping', 'cartpipe' ),
										'desc'              => __( 'Please map your shipping item tax code to the corresponding tax code in QuickBooks Online.', 'cartpipe' ),
										'id'                => 'qbo[foreign_shipping_item_taxcode]',
										'type'              => 'select',
										'options'			=> $tax_codes,//wc_get_order_statuses(),
										'css'               => '',
										'default'           => '',
										'autoload'          => false
								),
							);	
							break;
						
					}
					
				}else{
					$this->settings = array(
						array(
								'title'             => __( 'Taxes are disabled in WooCommerce. ', 'cartpipe' ),
								'desc'              => __( 'Looks like you currently have taxes disabled in WooCommerce. Go ahead and proceed to the next step as there\'s nothing to configure.', 'cartpipe' ),
								'type'              => 'title',
								'css'               => '',
							)
					);
				}		
				break;
			
			case 'payments':
				$accounts 			= CP()->client->qbo_get_filtered_accounts( CP()->qbo->license, 'deposit' );// CP()->client->qbo_get_accounts( CP()->qbo->license );
				$payment_methods 	= CP()->client->qbo_get_payment_methods( CP()->qbo->license );
				$wc_payment_methods= $this->get_payment_methods();
				if($accounts->errors ){
					$accounts = array();
				}
				if($payment_methods->errors ){
					$payment_methods = array();
				}
				$this->settings = array(
						array(
								'title'             => __( 'Payment Status Trigger', 'cartpipe' ),
								'desc'              => __( 'When do you want your payments to transfer to QuickBooks? Select the payment status below.', 'cartpipe' ),
								'id'                => 'qbo[order_trigger]',
								'type'              => 'select',
								'options'			=> wc_get_order_statuses(),
								'css'               => '',
								'default'           => 'completed',
								'autoload'          => false
							),
							array(
								'title'             => __( 'Payment Posting Type', 'cartpipe' ),
								'desc'              => __( 'How do you want your payments to transfer to QuickBooks?', 'cartpipe' ),
								'id'                => 'qbo[order_type]',
								'type'              => 'select',
								'options'			=> array(
															'sales-receipt'	=>	'Sales Receipt',
															'invoice'		=>	'Invoice'
														),
								'css'               => '',
								'default'           => 'invoice',
								'autoload'          => false
							),
							array(
								'title'             => __( 'Create Payment in QuickBooks?', 'cartpipe' ),
								'desc'              => __( 'Would you like to receive a payment on account in QuickBooks once the payment has reached a \'completed\' status on the website', 'cartpipe' ),
								'id'                => 'qbo[create_payment]',
								'type'              => 'checkbox',
								'css'               => '',
								'default'           => '',
								'dependency'		=> array(
														'setting'	=> 'qbo[order_type]',
														'value'		=> 'invoice'
													),
								'autoload'          => false
							),
							array(
								'title'             => __( 'Deposit Account', 'cartpipe' ),
								'desc'              => __( 'Which account in QuickBooks do you want to use as the deposit account?', 'cartpipe' ),
								'id'                => 'qbo[deposit_account]',
								'type'              => 'select',
								'options'			=> $accounts,
								'css'               => '',
								'default'           => '',
								'autoload'          => false
							),
							array(
								'title'             => __( 'Payment Method Mappings', 'cartpipe' ),
								'desc'              => __( 'Please map your website payment methods to the corresponding payment methods in QuickBooks Online.', 'cartpipe' ),
								'id'                => 'qbo[payment_methods]',
								'type'              => 'mapping',
								'options'			=> $payment_methods,//wc_get_order_statuses(),
								'labels'			=> $wc_payment_methods,
								'css'               => '',
								'default'           => '',
								'autoload'          => false
							),
					);		
				break;
			default:
				
				break;
		}
	}
	/**
	 * Product Sync Settings
	 */
	public function cp_sync_mappings() {
		
		?>
		<h1><?php _e( 'Product Sync Options', 'cartpipe' ); ?></h1>
		
		<form method="post">
			<table class="form-table">
				<?php QBO_Admin_Settings::output_fields($this->settings);?>
			</table>
			<p class="cp-setup-actions step">
				<input type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Continue', 'cartpipe' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large"><?php _e( 'Skip this step', 'cartpipe' ); ?></a>
				<?php wp_nonce_field( 'cp-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save Locale Settings
	 */
	public function cp_sync_mappings_save($settings) {
		check_admin_referer( 'cp-setup' );

		QBO_Admin_Settings::save_fields($settings);
		
		wp_redirect( $this->get_next_step_link() );
		exit;
	}

	/**
	 * Shipping and taxes
	 */
	public function cp_tax_mappings() {
		
		?>
		<h1><?php _e( 'QuickBooks Online Tax Mappings', 'cartpipe' ); ?></h1>
		<form method="post">
			<p><?php _e( 'If you\'re charging sales tax, setup the sales tax mappings below. This allows for the sales tax collected on the website to be mapped correctly in QuickBooks Online', 'cartpipe'); ?></p>
			<table class="form-table">
				<?php 
					
					QBO_Admin_Settings::output_fields($this->settings)
				?>
			</table>
			<p class="cp-setup-actions step">
				<input type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Continue', 'cartpipe' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large"><?php _e( 'Skip this step', 'cartpipe' ); ?></a>
				<?php wp_nonce_field( 'cp-setup' ); ?>
			</p>
		</form>
		<?php
	}
	function get_tax_rates(){
		global $wpdb;
		$return = null;
		$tax_rates = $wpdb->get_results( 
			"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
			ORDER BY tax_rate_order
			"  );
		if($tax_rates){
			foreach($tax_rates as $rate){
				$return[$rate->tax_rate_id] = sprintf('%s - %s - %s (%s)', $rate->tax_rate_country, $rate->tax_rate_state, $rate->tax_rate_name, $rate->tax_rate.'%');
			}
		}
		return $return;
	}
	/**
	 * Save shipping and tax options
	 */
	public function cp_tax_mappings_save( $settings ) {
		check_admin_referer( 'cp-setup' );
		QBO_Admin_Settings::save_fields($settings);
		wp_redirect( $this->get_next_step_link() );
		exit;
	}

	/**
	 * Payments Step
	 */
	public function cp_setup_payments() {
		
		?>
		<h1><?php _e( 'Payments', 'cartpipe' ); ?></h1>
		<form method="post">
			<p><?php _e( 'Let\'s setup how and when Orders transfer to QuickBooks ', 'cartpipe' ); ?></p>
			<table class="form-table">
				<?php QBO_Admin_Settings::output_fields($this->settings);?>
			</table>
			<p class="cp-setup-actions step">
				<input type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Continue', 'cartpipe' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large"><?php _e( 'Skip this step', 'cartpipe' ); ?></a>
				<?php wp_nonce_field( 'cp-setup' ); ?>
			</p>
		</form>
		<?php
	}
	public function get_tax_classes(){
		$return = array();
		if(class_exists('WC_Tax') ){
			$classes = WC_Tax::get_tax_classes();
			if($classes){
				foreach($classes as $class){
					$return[str_replace(' ', '-', strtolower($class))] = $class;
				}
			}
		}
		$return = array_merge( $return, array('standard'=>'Standard'));
		return $return;
	}
	public function get_payment_methods(){
		$return 	= array();			
		if ( is_plugin_active('woocommerce/woocommerce.php') ) {
			$gateways 	= WC()->payment_gateways->payment_gateways();
			
			if($gateways){
			 	foreach ($gateways as $key=>$value){
					$return[$key] = $value->title;
				} 
			}
		}
		return $return;
	}
	/**
	 * Payments Step save
	 */
	public function cp_setup_payments_save($settings) {
		check_admin_referer( 'cp-setup' );

		QBO_Admin_Settings::save_fields($settings);

		wp_redirect( $this->get_next_step_link() );
		exit;
	}

	/**
	 * Setup
	 */
	public function cp_setup_ready() {
	?>
		<h1><?php _e( 'Cartpipe\'s ready to go!!', 'cartpipe' ); ?></h1>
		<div class="cp-setup-next-steps">
			<div class="cp-setup">
				<p><?php _e('If you need more info about how things work, I\'d suggest checking out the FAQs below');?></p>
				<h2><?php _e( 'FAQs', 'cartpipe' ); ?></h2>
				<?php 
					if( false == ($faqs = get_transient('_cp_qbo_faqs'))){
						$response 	= CP()->client->check_service( CP()->qbo->license, get_site_url() );
						$faqs 		= $response->faqs;
						set_transient( '_cp_qbo_faqs', $faqs );  
					}
					echo $faqs;
				?>
			</div>
		</div>
		<?php
	}
}

new CP_Setup_Wizard();
