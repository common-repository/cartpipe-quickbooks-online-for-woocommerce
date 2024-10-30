<?php 


if(!class_exists('CP_Messages')){
	class CP_Messages{
		protected static $_instance = null;
		
		private static $cp_messages = array();
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			
			return self::$_instance;
			
		}
		
		function __construct(){
				
			add_action( 'admin_notices', array( $this, 'output_messages' ), 9999 );
			add_action( 'admin_init', array($this, 'add_default_messages'));
			//add_action( 'shutdown', array( $this, 'save_messages' ) );
			//add_action( 'cp_messages', array( $this, 'output_messages' ) );
		}
		function add_default_messages(){
			
			
		}
		/**
		 * Add an error message
		 * @param string $text
		 */
		public static function add_message( $text, $ref_id = false ) {
			self::$cp_messages[] = $text;
			self::save_messages( $ref_id );
			
		}
		/**
		 * Save errors to an option
		 */
		public static function save_messages($ref_id ) {
			$messages[] = self::$cp_messages; 
			update_option( 'cp_responses', self::$cp_messages );
			if($ref_id){
				update_post_meta($ref_id, '_cp_messages', self::$cp_messages);
			}
		}
		/**
		 * Show any stored error messages.
		 */
		public function output_messages() {
			global $post;
			$screen = get_current_screen();
			
			$show 	= array('edit-cp_queue', 'cp_queue', 'edit-cp_fallout', 'cp_fallout', 'cartpipe_page_qbo-settings', 'edit-shop_order', 'shop_order');
			
			if(CP()->qbo->notifications == 'yes'){
				
				if(in_array($screen->id, $show)){
					$errors = maybe_unserialize( get_option( 'cp_responses' ) );
					
					if ( ! empty( $errors ) ) {
						echo '<div class="updated">';
						foreach ( $errors as $error ) {
							if(is_array($error)){
								echo '<ul>';
								foreach($error as $e){
									if(is_object($e)){
										echo '<li>' . (  $e->message ) . '</li>';
									}else{
										echo '<li>' . (  $e ) . '</li>';
									}
								}
								echo '</ul>';
							}else{
								echo  '<ul><li><span class="text">' .( $error ) . '</span></li></ul>';
							}
						}
						echo '</div>';
			
						// Clear
						delete_option( 'cp_responses' );
					}
				}
			}
		}
	}
	
	
}
function CPM(){
		return CP_Messages::instance();
}
$GLOBALS['CPM'] = CPM();
//CPM()->add_message('This is my message');

