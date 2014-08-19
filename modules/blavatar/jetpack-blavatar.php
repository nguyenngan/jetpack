<?php

/*
Plugin Name: Blavatar
Plugin URL: http://wordpress.com/
Description:  Add an avatar for your blog. 
Version: 0.1
Author: Automattic

Released under the GPL v.2 license.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

class Jetpack_Blavatar {

	public $option_name = 'blavatar';
	public $module 		= 'blavatar';
	public $version 	= 1;

	public static $min_size = 512; //  the minimum size of the blvatar, 512 is the same as wp.com can be over writtern by BLAVATAR_MIN_SIZE
	public static $page_crop = 512; // the size to which to crop the image so that we can dispay it in the UI nicely

	public static $accepted_file_types = array( 
		'image/jpg', 
		'image/jpeg', 
		'image/gif', 
		'image/png' 
	);

	public static $blavatar_sizes = array( 256, 128, 64, 32, 16 );

	/**
	 * Singleton
	 */
	public static function init() {
		static $instance = false;

		if ( ! $instance )
			$instance = new Jetpack_Blavatar;

		return $instance;
	}

	function __construct() {
		self::$min_size = ( defined( 'BLAVATAR_MIN_SIZE' ) && is_int( BLAVATAR_MIN_SIZE ) ? BLAVATAR_MIN_SIZE : self::$min_size );
		
		add_action( 'jetpack_modules_loaded', array( $this, 'jetpack_modules_loaded' ) );
		add_action( 'jetpack_activate_module_blavatar', array( $this, 'jetpack_module_activated' ) );
		add_action( 'jetpack_deactivate_module_blavatar', array( $this, 'jetpack_module_deactivated' ) );
		add_action( 'admin_menu',            array( $this, 'admin_menu_upload_blavatar' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_print_styles-options-general.php', array( $this, 'add_admin_styles' ) );

		add_action( 'wp_head', array( $this, 'blavatar_add_meta' ) );
		add_action( 'admin_head', array( $this, 'blavatar_add_meta' ) );
		add_action( 'delete_option', array( 'Jetpack_Blavatar', 'delete_temp_data' ), 10, 1); // used to clean up after itself. 
		add_action( 'delete_attachment', array( 'Jetpack_Blavatar', 'delete_attachment_data' ), 10, 1); // in case user deletes the attachment via 
		add_filter( "get_post_metadata", array( 'Jetpack_Blavatar', 'delete_attachment_images'), 10, 4 );
	}

	/**
	 * Add meta elements to a blog header to light up Blavatar icons recognized by user agents.
	 * @link http://www.whatwg.org/specs/web-apps/current-work/multipage/links.html#rel-icon HTML5 specification link icon
	 * @todo change Blavatar ico sizes once all Blavatars have been backfilled to new code
	 * @todo update apple-touch-icon to support retina display 114px and iPad 72px
	 */
	function blavatar_add_meta() {

		if ( apply_filters( 'blavatar_has_favicon', false ) )
			return;

		$url_114 = blavatar_url( null,  114 );
		echo '<link rel="icon" href="'.esc_url( blavatar_url( null,  32 ) ) .'" sizes="32x32" />' . "\n";
		echo '<link rel="apple-touch-icon-precomposed" href="'. esc_url( $url_114 ) .'">' . "\n";
		// windows tiles
		echo '<meta name="msapplication-TileImage" content="' . esc_url( $url_114 ) . '"/>' . "\n";

	}


	/**
	 * Add a hidden upload page for people that don't like modal windows
	 */
	function admin_menu_upload_blavatar() {
 		$page_hook = add_submenu_page( 
 			null, 
 			__( 'Blavatar Upload', 'jetpack' ), 
 			'', 
 			'manage_options', 
 			'jetpack-blavatar-upload', 
 			array( $this, 'upload_blavatar_page' ) 
 		);
 	
 		add_action( "admin_head-$page_hook", array( $this, 'upload_balavatar_head' ) );
	}

	/**
	 * After all modules have been loaded.
	 */
	function jetpack_modules_loaded() {

		Jetpack::enable_module_configurable( $this->module );
		Jetpack::module_configuration_load( $this->module, array( $this, 'jetpack_configuration_load' ) );

	}
	/**
	 * Add styles to the General Settings 
	 */
	function add_admin_styles(){
		wp_register_script( 'blavatar-admin', plugin_dir_url( __FILE__ ). "js/blavatar-admin.js" , array( 'jquery' ) , $this->version, true );
		wp_enqueue_style( 'blavatar-admin' );
	}
	/**
	 * Add Styles to the Upload UI Page
	 *
	 */
	function upload_balavatar_head(){

		wp_register_script( 'blavatar-crop',  plugin_dir_url( __FILE__ ). "js/blavatar-crop.js"  , array( 'jquery', 'jcrop' ) , $this->version, false);
		if( isset( $_REQUEST['step'] )  && $_REQUEST['step'] == 2 ){
			wp_enqueue_script( 'blavatar-crop' );
			wp_enqueue_style( 'jcrop' );
		}
		wp_enqueue_style( 'blavatar-admin' );
	}
	
	/**
	 * Runs when the Blavatar module is activated.
	 */
	function jetpack_module_activated() {
		// not sure yet what is suppoed to go here!
	}

	/**
	 * Runs when the Blavatar module is deactivated.
	 */
	function jetpack_module_deactivated() {
		// there are no options that need to be deleted One option that I see we could have is the default blavatar that is defined on per network
		// Jetpack_Options::delete_option( $this->option_name );
	}

	/**
	 * Direct the user to the Settings -> General
	 */
	function jetpack_configuration_load() {
		wp_safe_redirect( admin_url( 'options-general.php#blavatar' ) );
		exit;
	}

	/**
	 * Load on when the admin is initialized
	 * 
	 */
	function admin_init(){
		/* regsiter the styles and scripts */
		wp_register_style( 'blavatar-admin' , plugin_dir_url( __FILE__ ). "css/blavatar-admin.css", array(), $this->version );
		
		add_settings_section(
		  $this->module,
		  '',
		  array( $this, 'blavatar_settings'),
		  'general'
		);

		if( isset( $GLOBALS['plugin_page'] ) && 'jetpack-blavatar-upload' == $GLOBALS['plugin_page'] ) {
			if( isset( $_GET['action'] ) 
			&& 'remove' == $_GET['action'] 
			&& isset( $_GET['nonce'] ) 
			&&  wp_verify_nonce( $_GET['nonce'], 'remove_blavatar' ) ) {

				$blavatar_id = get_option( 'blavatar_id' );
				// Delete the previous Blavatar
        		self::delete_blavatar( $blavatar_id, true );
				wp_safe_redirect( admin_url( 'options-general.php#blavatar' ) );
			}
		}
	}
	/**
	 * Add HTML to the General Settings
	 * 
	 */
	function blavatar_settings() { 
		
		$upload_blovatar_url = admin_url( 'options-general.php?page=jetpack-blavatar-upload' );
		
		wp_enqueue_script( 'blavatar-admin' );

		// lets delete the temp data that we might he holding on to
		self::delete_temporay_data();
		
		?>
		<div id="blavatar" class="blavatar-shell">
			<h3><?php echo esc_html_e( 'Site Image', 'jetpack'  ); ?></h3>
			<div class="blavatar-content postbox">
				<div class="blavatar-image">
				<?php if( has_blavatar() ) { 
					echo get_blavatar( null, 128 ); 
					} ?>
				</div>
				<div class="blavatar-meta">

				<?php if( has_blavatar() ) { 
					$remove_blovatar_url = admin_url( 'options-general.php?page=jetpack-blavatar-upload' )."&action=remove&nonce=".wp_create_nonce( 'remove_blavatar' ); // this could be an ajax url 
					?>
					<p><a href="<?php echo esc_url( $upload_blovatar_url ); ?>" id="blavatar-update" class="button"><?php echo esc_html_e( 'Update Image', 'jetpack'  ); ?></a>
					<a href="<?php echo esc_url( $remove_blovatar_url ); ?>" id="blavatar-remove" ><?php echo esc_html_e( 'Remove Image', 'jetpack'  ); ?></a> </p>

				<?php } else { ?>
				
					<a href="<?php echo esc_url( $upload_blovatar_url ); ?>" id="blavatar-update" class="button"><?php echo esc_html_e( 'Add a Site Image', 'jetpack' ); ?></a>
				
				<?php } ?>
				
					<div class="blavatar-info">
					<p><?php echo esc_html_e( 'Site Image or Blavatar is used to create a icon for your site or blog.', 'jetpack' ); ?>
					</p>
					</div>

				</div>
			</div>
		</div>
		<?php 	
	}

	/**
	 * Hidden Upload Blavatar page for people that don't like modals
	 */
	function upload_blavatar_page() { ?>
		<div class="wrap">
			<?php require_once( dirname( __FILE__ ) . '/upload-blavatar.php' ); ?>
		</div>
		<?php
	}


	/**
	 * Select a file admin view
	 * 
	 */
	static function select_page() {

		//self::delete_temporay_data();
		// Display the blavatar form to upload the image
		 ?>
		<form action="<?php echo esc_url( admin_url( 'options-general.php?page=jetpack-blavatar-upload' ) ); ?>" method="post" enctype="multipart/form-data">

			<h2 class="blavatar-title"><?php esc_html_e( 'Update Site Image', 'jetpack'); ?> <span class="small"><?php esc_html_e( 'select a file', 'jetpack'); ?></span></h2>
			<p><?php esc_html_e( 'Upload a picture to be used as your site image. We will let you crop it after you upload.', 'jetpack' ); ?></p>

			
			<p><input name="blavatarfile" id="blavatarfile" type="file" /></p>
			<p><?php esc_html_e( 'The image needs to be at least', 'jetpack' ); ?> <strong><?php echo self::$min_size; ?>px</strong> <?php esc_html_e( 'in both width and height.', 'jetpack' ); ?></p>
			<p class="submit">
				<input name="submit" value="<?php esc_attr_e( 'Upload Image' , 'jetpack' ); ?>" type="submit" class="button button-primary button-large" /> or <a href="#">Cancel</a> and go back to the settings.
				<input name="step" value="2" type="hidden" />
			
				<?php wp_nonce_field( 'update-blavatar-2', '_nonce' ); ?>
			</p>
			</div>
		</form>
		<?php
	}

	/**
	 * Crop a the image admin view
	 */
	static function crop_page() { 
		
		// handle the uploaded image
		$image = self::handle_file_upload( $_FILES['blavatarfile'] );

		// display the image image croppping funcunality
		if( is_wp_error( $image ) ) { ?>
			<div id="message" class="updated error below-h2"><p> <?php echo esc_html( $image->get_error_message() ); ?> </p></div> 
			<?php
			// back to step one
			$_POST = array();
			self::delete_temporay_data(); 
			self::select_page();
			return;
		}
		
		$crop_data = get_option( 'blavatar_temp_data' );
		$crop_ration = $crop_data['large_image_data'][0] / $crop_data['resized_image_data'][0]; // always bigger then 1

		// lets make sure that the Javascript ia also loaded
		wp_localize_script( 'blavatar-crop', 'Blavatar_Crop_Data', self::initial_crop_data( $crop_data['large_image_data'][0] , $crop_data['large_image_data'][1], $crop_data['resized_image_data'][0], $crop_data['resized_image_data'][1] ) ); 
		?>

		<h2 class="blavatar-title"><?php esc_html_e( 'Update Site Image', 'jetpack'); ?> <span class="small"><?php esc_html_e( 'crop the image', 'jetpack' ); ?></span></h2>
		<div class="blavatar-crop-shell">
			<form action="" method="post" enctype="multipart/form-data">
			<p><input name="submit" value="<?php esc_attr_e( 'Crop Image', 'jetpack' ); ?>" type="submit" class="button button-primary button-large" /><?php printf( __( ' or <a href="%s">Cancel</a> and go back to the settings.' , 'jetpack' ), esc_url( admin_url( 'options-general.php' ) ) ); ?></p>
			<div class="blavatar-crop-preview-shell">

			<h3><?php esc_html_e( 'Preview', 'jetpack' ); ?></h3>

				<strong><?php esc_html_e( 'As your favicon', 'jetpack' ); ?></strong>
				<div class="blavatar-crop-favicon-preview-shell">
					<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ). "browser.png" ); ?>" class="blavatar-browser-preview" width="172" height="79" alt="<?php esc_attr_e( 'Browser Chrome' , 'jetpack'); ?>" />
					<div class="blavatar-crop-preview-favicon">
						<img src="<?php echo esc_url( $image[0] ); ?>" id="preview-favicon" alt="<?php esc_attr_e( 'Preview Favicon' , 'jetpack'); ?>" />
					</div>
					<span class="blavatar-browser-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				</div>

				<strong><?php esc_html_e( 'As a mobile icon', 'jetpack' ); ?></strong>
				<div class="blavatar-crop-preview-homeicon">
					<img src="<?php echo esc_url( $image[0] ); ?>" id="preview-homeicon" alt="<?php esc_attr_e( 'Preview Home Icon' , 'jetpack'); ?>" />
				</div>
			</div>
			<img src="<?php echo esc_url( $image[0] ); ?>" id="crop-image" class="blavatar-crop-image"
				width="<?php echo esc_attr( $crop_data['resized_image_data'][0] ); ?>" 
				height="<?php echo esc_attr( $crop_data['resized_image_data'][1] ); ?>" 
				alt="<?php esc_attr_e( 'Image to be cropped', 'jetpack' ); ?>" />
		
			<input name="step" value="3" type="hidden" />
			<input type="hidden" id="crop-x" name="crop-x" />
			<input type="hidden" id="crop-y" name="crop-y" />
			<input type="hidden" id="crop-width" name="crop-w" />
			<input type="hidden" id="crop-height" name="crop-h" />
		
			<?php wp_nonce_field( 'update-blavatar-3', '_nonce' ); ?>

			</form>
		</div>
		<?php
	}
	/**
	 * All done page admin view
	 * 
	 */
	static function all_done_page() { 

		$temp_image_data = get_option( 'blavatar_temp_data' );
		if( ! $temp_image_data ) {

			self::select_page();
			return;
		}
		$crop_ration = $temp_image_data['large_image_data'][0] / $temp_image_data['resized_image_data'][0]; // always bigger then 1
		
		$crop_data = self::convert_coodiantes_from_resized_to_full( $_POST['crop-x'], $_POST['crop-y'], $_POST['crop-w'], $_POST['crop-h'], $crop_ration );
	
		$image_edit =  wp_get_image_editor( _load_image_to_edit_path( $temp_image_data['large_image_attachment_id'] ) );

		if ( is_wp_error( $image_edit ) ) {
			return $image_edit;
		}

		// Delete the previous blavatar
    	$previous_blavatar_id =  get_option( 'blavatar_id' );
    	self::delete_blavatar( $previous_blavatar_id );

		// crop the image
		$image_edit->crop( $crop_data['crop_x'], $crop_data['crop_y'],$crop_data['crop_width'], $crop_data['crop_height'], self::$min_size, self::$min_size );
		
		$dir = wp_upload_dir();
    	$blavatar_filename = $image_edit->generate_filename( 'blavatar',  $dir['path'] , 'png' );
		$image_edit->save( $blavatar_filename );
		
		add_filter( 'intermediate_image_sizes_advanced', array( 'Jetpack_Blavatar', 'additional_sizes' ) );

		$blavatar_id = self::save_attachment( 
    		__( 'Large Blog Image', 'jetpack' ) , 
    		$blavatar_filename, 
    		'image/png'
    	); 

    	remove_filter( 'intermediate_image_sizes_advanced', array( 'Jetpack_Blavatar', 'additional_sizes' ) );
    	
    	

		// Save the blavatar data into option
		update_option( 'blavatar_id', $blavatar_id );
	
    	

		?>
		<h2 class="blavatar-title"><?php esc_html_e( 'Update Site Image', 'jetpack'); ?> <span class="small"><?php esc_html_e( 'All Done', 'jetpack' ); ?></span></h2>
		
		<?php echo get_blavatar( null, $size = '128' ); ?>
		<?php echo get_blavatar( null, $size = '48' ); ?> 
		<?php echo get_blavatar( null, $size = '16' ); ?> 
		<p><?php esc_html_e( 'Your blavatar image has been uploaded!', 'jetpack' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" ><?php esc_html_e( 'Back to General Settings' , 'jetpack' ); ?></a>
		<?php
	}

	/**
	 * This function is used to pass data to the localize scipt so that we can center the copper and also set the minimum cropper if we still want to show the 
	 * @param  int $large_width    
	 * @param  int $large_height   
	 * @param  int $resized_width  
	 * @param  int $resized_height 
	 * @return array                 
	 */
	static function initial_crop_data( $large_width, $large_height, $resized_width, $resized_height ) {
		
		$init_x = 0;
		$init_y = 0;
		
		$ration = $large_width / $resized_width;
		$min_crop_size = ( self::$min_size / $ration );

		// Landscape format ( width > height )
		if( $resized_width > $resized_height ) {
			$init_x = ( self::$page_crop - $resized_height ) / 2;
			$init_size = $resized_height;
			
		}

		// Portrate format ( height > width )
		if( $resized_width < $resized_height ) {
			$init_y = ( self::$page_crop - $resized_width ) / 2;
			$init_size = $resized_height;
		}

		// Squere height == width
		if( $resized_width = $resized_height ) {
			$init_size = $resized_height;
		}

		return array( 
			'init_x' => $init_x,
			'init_y' => $init_y,
			'init_size' => $init_size,
			'min_size' 	=> $min_crop_size
		);
	}

	/**
	 * Delete the temporary created data and attachments
	 * @return [type] [description]
	 */
	static function delete_temporay_data() {
		// This should autimatically delete the temporary files as well
		delete_option( 'blavatar_temp_data' ); 
	}
	/**
	 * Function gets fired when delete_option( 'blavatar_temp_data' ) is run.
	 * @param  string $option description of the 
	 * @return null;
	 */
 	static function delete_temp_data( $option ) {

		if( 'blavatar_temp_data' == $option ) {
			$temp_image_data = get_option( 'blavatar_temp_data' );
			
			remove_action( 'delete_attachment', array( 'Jetpack_Blavatar', 'delete_attachment_data' ), 10, 1);

			wp_delete_attachment( $temp_image_data['large_image_attachment_id'] , true );
	        wp_delete_attachment( $temp_image_data['resized_image_attacment_id'] , true );
		}
		return null;
	}

	static function delete_attachment_data( $post_id ) {
		
		// The user could be deleting the blavatar image
		$blavatar_id = get_option( 'blavatar_id' );
		if( $blavatar_id &&  $post_id == $blavatar_id ) {
			delete_option( 'blavatar_id' );
		}
		// The user could be deleteing the temporary images
	}
	/**
	 * 
	 * @param  [type] $check    [description]
	 * @param  [type] $post_id  [description]
	 * @param  [type] $meta_key [description]
	 * @param  [type] $single   [description]
	 * @return [type]           [description]
	 */
	static function delete_attachment_images( $check, $post_id, $meta_key, $single ) {
		$blavatar_id = get_option( 'blavatar_id' );
		if( $post_id == $blavatar_id && '_wp_attachment_backup_sizes' == $meta_key && true == $single ) 
			add_filter( 'intermediate_image_sizes', array( 'Jetpack_Blavatar', 'intermediate_image_sizes' ) );
		return $check;
	}

	/**
	 * Delete the balvatar and all the attacted data 
	 * @param  [type]  $id          [description]
	 * @param  boolean $delete_data [description]
	 * @return [type]               [description]
	 */
	static function delete_blavatar( $id ) {

		// We add the filter to make sure that we also delete all the added images
		add_filter( 'intermediate_image_sizes', 	array( 'Jetpack_Blavatar', 'intermediate_image_sizes' ) );
		wp_delete_attachment( $id , true );
		remove_filter( 'intermediate_image_sizes', 	array( 'Jetpack_Blavatar', 'intermediate_image_sizes' ) );
		// for good measure also 
		self::delete_temporay_data();
		delete_option( 'blavatar_id' );

	}
	
	static function convert_coodiantes_from_resized_to_full( $crop_x, $crop_y, $crop_width, $crop_height, $ratio ) {

		return array(  
			'crop_x' 	  => floor( $crop_x * $ratio ),
			'crop_y' 	  => floor( $crop_y * $ratio ), 
			'crop_width'  => floor($crop_width * $ratio), 
			'crop_height' => floor($crop_height * $ratio)
			);
	}

	/**
	 * Handle the uploaded image
	 */
	static function handle_file_upload( $uploaded_file ) {
		
		// check that the image accuallt is a file with size
		if( !isset( $uploaded_file ) || ($uploaded_file['size'] <= 0 ) ) {
			return new WP_Error( 'broke', __( "Please upload a file.", 'jetpack' ) );
		} 

		$arr_file_type = wp_check_filetype( basename( $uploaded_file['name'] ) );
		$uploaded_file_type = $arr_file_type['type'];
		if( ! in_array( $uploaded_file_type, self::$accepted_file_types ) ) {
			// Create a temp file which should be deleted at when the scipt stops
			return new WP_Error( 'broke', __( "The file that you uploaded is not an accepted file type. Please try again.", 'jetpack' ) );
		}

        $image = wp_handle_upload( $uploaded_file, array( 'test_form' => false ) );

        if(  is_wp_error( $image ) ) {
  			// this should contain the error message returned from wp_handle_upload
  			unlink( $image['file'] ); // Lets delete the file since we are not going to be using it
        	return $image;
        }
        
        // Lets try to crop the image into smaller files. 
        // We will be doing this later so it is better if it fails now.
        $image_edit = wp_get_image_editor( $image['file'] );
        if ( is_wp_error( $image_edit ) ) {
        	// this should contain the error message from WP_Image_Editor 
        	unlink( $image['file'] ); // lets delete the file since we are not going to be using it
        	return $image_edit;
        }

        $image_size = getimagesize( $image['file'] );
        
        if( $image_size[0] < self::$min_size || $image_size[1] < self::$min_size ) {
        
        	
        	if( $image_size[0] < self::$min_size ) {
        		return new WP_Error( 'broke', __( sprintf( "The image that you uploaded is smalled then %spx in width", self::$min_size ) , 'jetpack' ) );
        	}

        	if( $image_size[1] < self::$min_size ) {
        		return new WP_Error( 'broke', __( sprintf( "The image that you uploaded is smalled then %spx in height", self::$min_size ) , 'jetpack' ) );
        	}
        }

     	// Save the image as an attachment for later use. 
        $large_attachment_id = self::save_attachment( 
        	__( 'Temporary Large Image for Blog Image', 'jetpack' ) , 
        	$image['file'], 
        	$uploaded_file_type,
        	false
        ); 
        
		// Let's resize the image so that the user can easier crop a image that in the admin view 
        $image_edit->resize( self::$page_crop, self::$page_crop, false );
        $dir = wp_upload_dir();
        
        $resized_filename = $image_edit->generate_filename( 'temp',  $dir['path'] , null );
        $image_edit->save( $resized_filename );

       	
       	$resized_attach_id = self::save_attachment( 
        	__( 'Temporary Resized Image for Blog Image', 'jetpack' ), 
        	$resized_filename, 
        	$uploaded_file_type,
        	false
        ); 

        $resized_image_size = getimagesize( $resized_filename ); 
        // Save all of this into the the database for that we can work with it later.
        update_option( 'blavatar_temp_data', array( 
        		'large_image_attachment_id'  => $large_attachment_id,
        		'large_image_data'			 => $image_size,
        		'resized_image_attacment_id' => $resized_attach_id,
        		'resized_image_data'		 => $resized_image_size
        		) );
        
        return wp_get_attachment_image_src( $resized_attach_id, 'full' );
	}

	/**
	 * Save Blavatar files to Media Library
	 * @param  string  	$title         
	 * @param  string  	$filename      
	 * @param  string  	$file_type     
	 * @param  boolean 	$generate_meta
	 * @return int 		$attactment_id                 
	 */
	static function save_attachment( $title, $filename, $file_type, $generate_meta = true ) {
		$wp_upload_dir = wp_upload_dir();
		$attachment = array(
		 	'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
            'post_mime_type' => $file_type,
            'post_title' 	 => $title,
            'post_content' 	 => '',
            'post_status' 	 => 'inherit'
        );
		$attachment_id = wp_insert_attachment( $attachment, $filename);

		if( ! function_exists( 'wp_generate_attachment_metadata') )  {
			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
		if( !$generate_meta )
			add_filter( 'intermediate_image_sizes_advanced', array( 'Jetpack_Blavatar', 'only_thumbnail_size' ) );
		
		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
		wp_update_attachment_metadata( $attachment_id, $attach_data );
		
		if( !$generate_meta ) {
			remove_filter( 'intermediate_image_sizes_advanced', array( 'Jetpack_Blavatar', 'only_thumbnail_size' ) );
		}

        return $attachment_id;
	}

	/**
	 * Add additional sizes to be made when creating the blavatar images
	 * @param  array $sizes
	 * @return array       
	 */
	static function additional_sizes( $sizes ) {
		/**
		 * blavatar_image_sizes
		 * filter the different dimentions that the image should be saved in
		 */
		self::$blavatar_sizes = apply_filters( 'blavatar_image_sizes', self::$blavatar_sizes );
  		// use a natular sort of numbers
		natsort( self::$blavatar_sizes ); 
  		self::$blavatar_sizes = array_reverse ( self::$blavatar_sizes );
    	
    	// ensure that we only resize the image into 
    	foreach( $sizes as $name => $size_array ) {
    		if( $size_array['crop'] ){
    			$only_crop_sizes[ $name ] = $size_array;
    		}
    	}

    	foreach( self::$blavatar_sizes as $size ) {
    		if( $size < self::$min_size ) {
    			
  	 			$only_crop_sizes['blavatar-'.$size] =  array( 
						    				"width" => $size,
						    				"height"=> $size,
						    				"crop"  => true
						    				);
    		}
    	}

		return $only_crop_sizes;
	}

	static function intermediate_image_sizes( $sizes ) {

		self::$blavatar_sizes = apply_filters( 'blavatar_image_sizes', self::$blavatar_sizes );
		foreach( self::$blavatar_sizes as $size ) {
			$sizes[] = 'blavatar-'.$size;
		}
		return $sizes;
	}
	/**
	 * Only resize the image to thumbnail so we can use 
	 * Use when resizing temporary images. This way we can see the temp image in Media Gallery. 
	 * @param  array $sizes
	 * @return array
	 */
	static function only_thumbnail_size( $sizes ){
		foreach( $sizes as $name => $size_array ) {
			if( 'thumbnail' == $name)
				$only_thumb['thumbnail'] = $size_array;
		}
		return $only_thumb;
	}
}


Jetpack_Blavatar::init();

if( ! function_exists( 'has_blavatar' ) ) :
function has_blavatar( $blog_id = null ) {

	if( ! is_int( $blog_id ) )
		$blog_id = get_current_blog_id();

	if( blavatar_url( $blog_id, 96, '' ) ) {
		return true;
	}

	return false;
}
endif;

if( ! function_exists( 'get_blavatar' ) ) :
function get_blavatar( $blog_id = null, $size = '96', $default = '', $alt = false ) {

	if( ! is_int( $blog_id ) )
		$blog_id = get_current_blog_id();

	$size  = esc_attr( $size );
	$class = "avatar avatar-$size";
	$alt = ( $alt ? esc_attr( $alt ) : __( 'Blog Image', 'jetpack' ) );
	$src = esc_url( blavatar_url( $blog_id, $size, $default ) );
	$avatar = "<img alt='{$alt}' src='{$src}' class='$class' height='{$size}' width='{$size}' />";

	return apply_filters( 'get_blavatar', $avatar, $blog_id, $size, $default, $alt );
}
endif; 

if( ! function_exists( 'blavatar_url' ) ) :
function blavatar_url( $blog_id = null, $size = '96', $default = false ) {
	$url = '';
	if( ! is_int( $blog_id ) )
		$blog_id = get_current_blog_id();

	if( function_exists( 'get_blog_option' ) ) {
		$blavatar_id = get_blog_option( $blog_id, 'blavatar_id' );
	} else {
		$blavatar_id = get_option( 'blavatar_id' );
	}
	
	if( ! $blavatar_id  ) {
		if( $default === false && defined( 'BLAVATAR_DEFAULT_URL' ) )
			$url =  BLAVATAR_DEFAULT_URL;
		else
			$url = $default;
	} else {

		$url_data = wp_get_attachment_image_src( $blavatar_id, array( $size, $size ) );
		$url = $url_data[0];
	}

	return $url;
}
endif; 