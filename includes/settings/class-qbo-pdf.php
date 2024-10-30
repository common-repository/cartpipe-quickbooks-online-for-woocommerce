<?php
/**
 * QBO Product Settings
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'QBO_Settings_PDFs' ) ) :

/**
 * WC_Settings_Products
 */
class QBO_Settings_PDFs extends QBO_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'pdfs';
		$this->label = __( 'Order PDFs', 'cartpipe' );

		add_filter( 'qbo_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'qbo_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'qbo_settings_save_' . $this->id, array( $this, 'save' ) );
		
		//add_action( 'qbo_sections_' . $this->id, array( $this, 'output_sections' ) );
		if(!CP()->qbo->license_info->status):
			//CP()->qbo->license_info = var_dump( CP()->client->check_service( $this->qbo->license, get_home_url()) );
			
		endif;
		
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
			$client 				= CP()->client;
						
			$settings = apply_filters( 'qbo_pdf_settings', array(

					array( 
						'title' => __( 'QuickBooks PDFs', 'cartpipe' ), 
						'type' => 'title',
				 		'desc' => 'These settings let you leverage the power of QuickBooks Online PDFs within your WooComemrce site. Cartpipe will collect a QuickBooks generated pdf and store it with the order. PDFs are securely stored on your server and can only be accessed by the site owner and person who placed the order.  ', 
				 		'id' => 'qbo_pdfs' 
					),
					array(
						'title'             => __( 'Enabled Order PDFs?', 'cartpipe' ),
						'tip'              => __( 'Check here to enable / disable retrieving pdfs from QuickBooks Online', 'cartpipe' ),
						'desc'              => __( 'Check here to enable / disable retrieving pdfs from QuickBooks Online. If enabled, Cartpipe will retrieve and store a pdf for each order from QuickBooks Online.', 'cartpipe' ),
						'id'                => 'qbo[enable_pdfs]',
						'type'              => 'checkbox',
						'css'               => '',
						'checkboxgroup' => 'end',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'             => __( 'Enabled My Account link?', 'cartpipe' ),
						'tip'              => __( 'Check here to enable / disable the link in the pdf link in the customer\'s account.', 'cartpipe' ),
						'desc'              => __( 'Check here to enable / disable the link in the pdf link in the customer\'s account.', 'cartpipe' ),
						'id'                => 'qbo[enable_account_link]',
						'type'              => 'checkbox',
						'css'               => '',
						'checkboxgroup' => 'end',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'             => __( 'My Account link label?', 'cartpipe' ),
						'tip'              => __( 'Enter the My Account link label text.', 'cartpipe' ),
						'desc'              => __( 'Enter the My Account link label text.', 'cartpipe' ),
						'id'                => 'qbo[account_link_label]',
						'type'              => 'text',
						'css'               => '',
						'checkboxgroup' => 'end',
						'default'           => 'PDF',
						'autoload'          => false
					),
					array( 
						'title' => __( 'Where are my pdf\'s stored?', 'cartpipe' ), 
						'type' => 'title',
				 		'desc' => sprintf('<p>We\'ve taken great care to make sure your pdf\'s can\'t be accessed by anyone other than the intended recipient. The pdf url the customer sees is is an encrypted / hashed url unique to that order that only admins and the logged in account holder can see. Likewise, the file folder name on your webserver is hashed and restricted by a .htaccess file to prevent directory snooping. You can view / manage the pdfs from the Media section of Wordpress or on the file system at</p> <code>%s</code>', CP_PDF()->get_private_dir()), 
				 		'id' => 'qbo_pdfs' 
					),
					//get_private_dir
					// array(
						// 'title'			=> __( 'Back-fill PDFs for existing Orders?', 'cartpipe' ),
						// 'label'			=> __('Generate PDFs', 'cartpipe'),
						// 'desc'          => __( 'Click to generate pdfs for existing orders that have been created in QuickBooks. Only orders that have been sent to QuickBooks will have a pdf created.', 'cartpipe' ),
						// //'id'            => 'qbo[sync_stock]',
						// 'type'          => 'button',
						// 'url'			=> '#',
						// 'class'			=> 'button generate',
						// 'autoload'      => false
					// ),
				array( 'type' => 'sectionend', 'id' => 'qbo_pdfs'),
				
			));
			
		//}

		return apply_filters( 'qbo_get_settings_' . $this->id, $settings, $current_section );
	}
}

endif;

return new QBO_Settings_PDFs();
