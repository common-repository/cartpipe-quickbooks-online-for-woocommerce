<?php
define( 'CP_PDF_KEY', hash( 'md5', AUTH_KEY ) );

class CP_PDF_Routes {

	private $prefix = 'cp';
	protected static $_instance = null;
	function __construct() {

		

		add_filter( 'wp_get_attachment_url', array( $this, 'private_file_url' ), 10, 2 );

		// Is Private Checkbox
		add_action( 'attachment_submitbox_misc_actions', array( $this, 'private_attachment_field' ) , 11 );
		add_filter( 'attachment_fields_to_save', array( $this, 'private_attachment_field_save' ), 10, 2 );
		add_action( 'parse_request', array( $this, 'get_invoice' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'init', array( $this, 'invoice_urls' ), 100 );
		add_action(	'init', array( $this, 'invoice_tags' ), 0, 0);
		// Display private posts filter & query filter.
		add_filter( 'pre_get_posts', array( $this, 'hide_private_from_query' ) );
		add_filter( 'woocommerce_my_account_my_orders_actions', array($this, 'show_invoice_link'), 10, 2 );
		add_action( 'cp_after_order_details', array($this, 'show_admin_pdf_link'));
		//add_filter( 'restrict_manage_posts', array( $this, 'filter_posts_toggle' ) );

		

		// Styles
		//add_action( 'admin_head', array( $this, 'post_edit_style' ) );

		

	}
	function show_invoice_link( $actions , $order ){
		if(CP()->qbo->enable_pdfs == 'yes' && CP()->qbo->enable_account_link == 'yes'){
			$attachments_ids = array_keys( get_attached_media( 'application/pdf', $order->id ) );
			if(sizeof($attachments_ids) > 0 ){
				foreach($attachments_ids as $attachment_id){
					$actions['invoice_'.$attachment_id] = array(
						'url'=>$this->get_invoice_url( $order, $attachment_id ),
						'name'=> __(CP()->qbo->account_link_label, 'cartpipe')
					);
				}
			}
		}
		return $actions;
	}
	function show_admin_pdf_link( $post ){
		if(CP()->qbo->enable_pdfs == 'yes'){
			$attachments_ids = array_keys( get_attached_media( 'application/pdf', $post->ID ) );
			if(sizeof($attachments_ids) > 0 ){
				foreach($attachments_ids as $attachment_id){?>
					<p class="form-field">
						<label for="view-pdf">
							<?php _e('View PDF');?>
						</label>
						<a href="<?php echo $this->show_admin_invoice_link( $post->ID, $attachment_id );?>" target="_blank" class="button">
							<?php _e( 'View', 'cartpipe' ); ?>
						</a>
					</p>
				<?php	
				}
			}
		}
	}
	function show_admin_invoice_link( $order_id ){
		$order = wc_get_order( $order_id );
		$attachments_ids = array_keys( get_attached_media( 'application/pdf', $order_id ) );
		if(sizeof($attachments_ids) > 0 ){
			foreach($attachments_ids as $attachment_id){
				$url = $this->get_invoice_url( $order, $attachment_id );
			}
		}
		return $url;
	}
	function encrypt_key( $key ){
		return $this->base64_url_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( AUTH_KEY ), $key, MCRYPT_MODE_CBC, md5( md5( AUTH_KEY ) ) ) );
	}
	function decrypt_key( $key ){
		return rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( AUTH_KEY ), $this->base64_url_decode( $key ), MCRYPT_MODE_CBC, md5( md5( AUTH_KEY ) ) ), "\0");
	}
	function get_invoice_url( $order, $attachment_id ){
		return sprintf( '%s/invoice/%s/%s', get_site_url(), $this->encrypt_key(str_replace('wc_order_', '', $order->order_key)), $this->encrypt_key($attachment_id) );
	}
	function base64_url_encode($input){
    	return strtr(base64_encode($input), '+/=', '-_,');
	}
	function base64_url_decode($input){
	    return base64_decode(strtr($input, '-_,', '+/='));
	}
	function get_invoice( $wp ){
		
		if( is_user_logged_in() && !is_admin() && !current_user_can('manage_options')) {
			$user_id = get_current_user_id();
			$valid_actions = array(
								'invoice',
							);
			$hash				= false;
			$attachment_id		= false;
			
			if( !empty($wp->query_vars['hash'] )){
				$hash 		= sprintf('wc_order_%s', $this->decrypt_key($wp->query_vars['hash']));
				$order_id 	= wc_get_order_id_by_order_key( $hash );
				$attachments_ids = array_keys( get_attached_media( 'application/pdf', $order_id ) );
			}
			if( !empty($wp->query_vars['attachment_id'] )){
				$attachment_id 			= $this->decrypt_key($wp->query_vars['attachment_id']);
				$attachment_order_id 	= get_post_meta( $attachment_id, 'cp_order_id' , true);
				$attachment_user_id		= get_post_meta( $attachment_id, 'cp_order_user_id' , true);
			}
			//check to see if it matches the cp_id stored with the passed tenant
			if( $attachment_order_id && $order_id ){
				
				if( $attachment_order_id == $order_id ){
					
					if( $attachment_user_id && $user_id ){
					
						if( $attachment_user_id == $user_id ){
							
							if(in_array($attachment_id, $attachments_ids)){
								if( !empty( $wp->query_vars['cp_action']) && in_array($wp->query_vars['cp_action'], $valid_actions)) {
						 			$action = $wp->query_vars['cp_action'];
									switch ( $action ){
										case 'invoice':
											header("Content-type:application/pdf");
											//header("Content-Disposition:attachment;filename='invoice_". $order_id .".pdf'");
											//)
											echo file_get_contents(get_attached_file( $attachment_id ));
											//readfile(wp_get_attachment_url( $attachment_id ));
											die();
										break;
									}
								}	
							}
						}
					}
				}
			};
		};
		if(is_user_logged_in() && current_user_can( 'manage_options' ) ){
			$valid_actions = array(
								'invoice',
							);
			$hash				= false;
			$attachment_id		= false;
			$attachment_order_id = false;
			if( !empty($wp->query_vars['hash'] )){
				//$hash 		= sprintf('wc_order_%s', $wp->query_vars['hash']);
				$hash 		= sprintf('wc_order_%s', $this->decrypt_key($wp->query_vars['hash']));
				$order_id 	= wc_get_order_id_by_order_key( $hash );
				$attachments_ids = array_keys( get_attached_media( 'application/pdf', $order_id ) );
			}
			if( !empty($wp->query_vars['attachment_id'] )){
				$attachment_id 			= $this->decrypt_key($wp->query_vars['attachment_id']);
				$attachment_order_id 	= get_post_meta( $attachment_id, 'cp_order_id' , true);
			}
			
			//check to see if it matches the cp_id stored with the passed tenant
			if( $attachment_order_id && $order_id ){
				
				if( $attachment_order_id == $order_id ){
							
					if(in_array($attachment_id, $attachments_ids)){
						if( !empty( $wp->query_vars['cp_action']) && in_array($wp->query_vars['cp_action'], $valid_actions)) {
				 			$action = $wp->query_vars['cp_action'];
							switch ( $action ){
								case 'invoice':
									header("Content-type:application/pdf");
									//header("Content-Disposition:attachment;filename='invoice_". $order_id .".pdf'");
									//)
									echo file_get_contents(get_attached_file( $attachment_id ));
									//readfile(wp_get_attachment_url( $attachment_id ));
									die();
								break;
							}
						}
					}
				}
			};
		};
	}
	function query_vars($vars){
		  	$vars[] = 'hash';
			$vars[] = 'attachment_id';
			$vars[] = 'cp_action';
			return $vars;
		}
	function invoice_tags() {
		//order key
	  	add_rewrite_tag('%hash%', 			'([^&]+)');
		//attachment_id to lookup
		add_rewrite_tag('%attachment_id%', 	'([^&]+)');
		
	 }
	function invoice_urls() {
		
		add_rewrite_rule(
	    	'^invoice/([^/]*)/([^/]*)/?',
	    	'index.php?cp_action=invoice&hash=$matches[1]&attachment_id=$matches[2]',
	    	'top'
	  	);
	}
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
			
	}
	/**
	 * Check if attachment is private
	 *
	 * @param  int $attachment_id
	 * @return boolean
	 */
	function is_attachment_private( $attachment_id ) {

		return get_post_meta( $attachment_id, 'cp_is_private', true );

	}	
	function is_attachment_invoice( $attachment_id ) {

		return get_post_meta( $attachment_id, 'cp_is_invoice', true );

	}
	function is_users_attachment( $user_id, $attachment_id ){
		if($user_id == $this->get_attachment_user_id( $attachment_id )){
			return true;
		}else{
			return false;
		}
		
	}
	function get_attachment_user_id( $attachment_id ) {

		return get_post_meta( $attachment_id, 'cp_order_user_id', true );

	}
	/**
	 * Check if current user can view attachment
	 *
	 * @todo  allow this to be filtered for more advanced use.
	 *
	 * @param  int $attachment_id
	 * @param  int $user_id (if not passed, assumed current user)
	 * @return boolean
	 */
	function can_user_view( $attachment_id, $user_id = null ) {

		$user_id = ( $user_id ) ? $user_id : get_current_user_id();

		if ( ! $attachment_id )
			return false;
		if(is_admin()){
			$private_status = $this->is_attachment_private( $attachment_id );
			if ( ! empty( $private_status ) &&! is_user_logged_in()	):
					return false;
			endif;
		}else{
			$private_status = $this->is_attachment_private( $attachment_id );
			$order_user_id 	= $this->get_attachment_user_id( $attachment_id );
			if ( ! empty( $private_status ) &&! is_user_logged_in() && ( $user_id != $order_user_id )	):
					return false;
			endif;
		}
		return true;

	}

	/**
	 * Get attachment id from attachment name
	 *
	 * @todo  surely this isn't the best way to do this?
	 * @param  [type] $attachment [description]
	 * @return [type]             [description]
	 */
	function get_attachment_id_from_name( $attachment ) {

		$attachment_post = new WP_Query( array(
			'post_type' => 'attachment',
			'showposts' => 1,
			'post_status' => 'inherit',
			'name' => $attachment,
			'show_private' => true
		) );

		if ( empty( $attachment_post->posts ) )
			return;

		return reset( $attachment_post->posts )->ID;

	}

	
	/**
	 * Get Private Directory URL
	 *
	 * If $path is true return path not url.
	 *
	 * @param  boolean $path return path not url.
	 * @return string path or url
	 */
	function get_private_dir( $path = false ) {

		$dirname = 'qbo-' . CP_PDF_KEY;
		$upload_dir = wp_upload_dir();

		// Maybe create the directory.
		if ( ! is_dir( trailingslashit( $upload_dir['basedir'] ) . $dirname ) )
			wp_mkdir_p( trailingslashit( $upload_dir['basedir'] ) . $dirname );

		$htaccess = trailingslashit( $upload_dir['basedir'] ) . $dirname . '/.htaccess';
		if(is_writable( dirname( $htaccess ) ) ){
			if ( ! file_exists( $htaccess ) && function_exists( 'insert_with_markers' ) && is_writable( dirname( $htaccess ) ) ) {
	
				$contents[]	= "# This .htaccess file ensures that other people cannot download your files.\n\n";
				$contents[] = "Deny from all";
				$contents[] = "Options All -Indexes";
				insert_with_markers( $htaccess, 'cartpipe', $contents );
	
			}
		}else{
			
		}
		if ( $path )
			return trailingslashit( $upload_dir['basedir'] ) . $dirname;

		return trailingslashit( $upload_dir['baseurl'] ) . $dirname;

	}

	
	
	/**
	 * Save private attachment field settings.
	 *
	 * On save - update settings and move files.
	 * Uses WP_Filesystem
	 *
	 * @todo check this out. Might need to handle edge cases.
	 */
	function make_private(  $attachment_id ) {

		$uploads = wp_upload_dir();
		$creds   = request_filesystem_credentials( add_query_arg( null, null ) );

		$this->get_private_dir( true );

		if ( ! $creds ) {
			return false;
			// Handle Error.
			// We can't actually display the form here because this is a filter and the page redirects and it will not be shown.
			//$message = '<strong>Private Media Error</strong> WordPress is not able to write files';
			//$this->admin_notices->add_notice( $message, false, 'error' );
			//return $post;
		}

		if ( $creds && WP_Filesystem( $creds ) ) {

			global $wp_filesystem;
			$new_location = null;
			$old_location = get_post_meta( $attachment_id, '_wp_attached_file', true );
			
			if ( $old_location && false === strpos( $old_location, 'qbo-' . CP_PDF_KEY ) ):
				$new_location = 'qbo-' . CP_PDF_KEY . '/' . $old_location;
			endif;
			
			$metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
			$old_path = trailingslashit( $uploads['basedir'] ) . $old_location;
			$new_path = trailingslashit( $uploads['basedir'] ) . $new_location;

			// Create destination
			if ( ! is_dir( dirname( $new_path ) ) )
				wp_mkdir_p( dirname( $new_path ) );

			$move = $wp_filesystem->move( $old_path, $new_path );

			if ( isset( $metadata['sizes'] ) )
				foreach ( $metadata['sizes'] as $key => $size ) {
					$old_image_size_path = trailingslashit( dirname( $old_path ) ) . $size['file'];
					$new_image_size_path = trailingslashit( dirname( $new_path ) ) . $size['file'];
					$move = $wp_filesystem->move( $old_image_size_path, $new_image_size_path );
				}


			if ( ! $move ) {
				// @todo handle errors.
			}

			update_post_meta( $attachment_id, 'cp_is_private', 'yes' );
			update_post_meta( $attachment_id, 'cp_is_invoice', 'yes' );
			update_post_meta( $attachment_id, '_wp_attached_file', $new_location );

			$metadata['file' ] = $new_location;
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $metadata );

		}

		return true;

	}

	/**
	 * Filter query to hide private posts.
	 *
	 * Set 404 for attachments in front end if user does not have permission to view file.
	 * Hide from any attachment query by default.
	 * If the 'show_private' query var is set, show only private.
	 *
	 * @param  object $query
	 * @return object $query
	 */
	function hide_private_from_query( $query ) {
		
		if ( ! is_admin() && !current_user_can('manage_options') ) {
			$attachment = ( $query->get( 'attachment_id') ) ? $query->get( 'attachment_id') : $query->get( 'attachment');
			
			if ( $attachment && ! is_numeric( $attachment ) )
				$attachment = $this->get_attachment_id_from_name( $attachment );

			if ( $attachment && ! $this->can_user_view( $attachment ) ) {

				$query->set_404();
				return $query;

			}

			if ( 'attachment' == $query->get('post_type') && ! $query->get('show_private') ) {
				
				if ( isset( $_GET['private_posts'] ) && 'private' == $_GET['private_posts']  )
					$query->set( 'meta_query', array(
						array(
							'key'   => 'cp_is_private',
							'compare' => 'EXISTS'
						)
					));
				else
					if(is_user_logged_in()){
						
						$current_user = get_current_user_id();
						$query->set( 'meta_query', array(
							array(
								'key'   => 'cp_is_private',
								'compare' => 'EXISTS'
							), 
							array(
								'key'   => 'cp_order_user_id',
								'value'	=> $current_user,
								'compare' => '='
							),
						));
					}
			}	
		}

		return $query;

	}

	/**
	 * Filter attachment url.
	 * If private return the 'public' private file url
	 * Rewrite rule used to serve file content and 'Real' file location is obscured.
	 *
	 * @param  string $url
	 * @param  int $attachment_id
	 * @return string file url.
	 */
	function private_file_url( $url, $attachment_id ) {
		
		if ( $this->is_attachment_private( $attachment_id ) ) {

			$uploads = wp_upload_dir();
			//return trailingslashit( $uploads['baseurl'] ) . 'private-files-'. MPHPF_KEY.'/' .  basename($url);

		}

		return $url;

	}

	/**
	 * Shortcode Field
	 *
	 * Add a readonly input field containing the current file shortcode to the submitbox of the edit attachment page.
	 *
	 * @return null
	 */
	function shortcode_field() {

		$shortcode = '[file id="' . get_the_ID() . '" ]';

		?>
		<div class="misc-pub-section">
			<label for="attachment_url"><?php _e( 'File Shortcode:' ); ?></label>
			<input type="text" class="widefat urlfield" readonly="readonly" name="attachment_url" value="<?php echo esc_attr($shortcode); ?>" />
		</div>
		<?php
	}

	/**
	 * Output link to file if user is logged in.
	 * Else output a message.
	 *
	 * @param  array $atts shortcode attributes
	 * @return string shortcode output.
	 */
	function shortcode_function($atts) {

		if ( ! isset( $atts['id'] ) )
			return;

		if ( $this->is_attachment_private( $atts['id'] ) && ! is_user_logged_in()):
			$link = 'You must be logged in to access this file.';
		elseif ( isset( $atts['attachment_page'] ) ):
			$user_id = get_current_user_id();
			if(!$this->is_users_attachment( $user_id, $atts['id']) ):
				$link = 'This is not your file';
			else:
				$link = wp_get_attachment_link( $atts['id'] );
			endif;
		else:
			$user_id = get_current_user_id();
			if(!$this->is_users_attachment( $user_id, $atts['id']) ):
				$link = 'This is not your file';
			else:
				$link = sprintf( '<a href="%s">%s</a>', esc_url( wp_get_attachment_url( $atts['id'] ) ), esc_html( basename( wp_get_attachment_url( $atts['id'] ) ) ) );
			endif;
		endif;
		return $link;

	}

}
function CP_PDF(){
		return CP_PDF_Routes::instance();
}
$GLOBALS['CP_PDF'] = CP_PDF();