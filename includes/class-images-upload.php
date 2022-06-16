<?php
/**
 * Class: YANN_Images_Upload
 * Upload image files as avator
 */
class IMAGES_UPLOAD {

	private $errors;

	/**
	 * This plugin's instance.
	 *
	 * @var IMAGES_UPLOAD
	 */
	private static $instance;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {

		$this->errors = False;
		$this->YANN_root_user_id = 1;
		$this->labname = "NTNUSMIL";

		$this->custom_dirs = array(
			'profile' => 'members',
			'cpts' => 'post_page',
			'default' => '',
		);
		$this->images_prefix = array(
			'profile' => 'A00',
			'media' => 'A01',
			'cpts' => 'A01',
			'default' => '',
		);
		$this->accept_media_extension = array(
			'jpg',
			'jpeg',
			'gif',
			'png',
			'webp'
		);
		$this->accepted_screens = array(
			'user-edit.php',
			'profile.php',
			'post.php',
			'post-new.php',
			'upload.php'
		);


		$this->add_hooks();
	}

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new IMAGES_UPLOAD();
		}
	}

	/**
	 * Register actions and filters.
	 */
	public function add_hooks() {
		add_action('admin_init', array( $this, 'YANN_NTNUSMIL_create_upload_dir_for_avator') , 10 , 0 );

		add_filter('wp_handle_upload_prefilter', array( $this , 'IMAGES_UPLOAD_handle_uploaded_image' ) , 10 , 1 );
		add_filter('wp_handle_upload_prefilter', array( $this, 'YANN_NTNUSMIL_custom_media_library_pre_upload') , 10 , 1 );
		add_filter('wp_handle_upload', array( $this, 'YANN_NTNUSMIL_custom_media_library_post_upload') , 10 , 1 );

		add_filter( 'pre_delete_attachment', array( $this , 'YANN_NTNUSMIL_Media_Library_prevent_delete_attachment' ) , 0 , 2 );
		// add_filter( 'wp_ajax_query-attachments', array( $this , 'YANN_NTNUSMIL_Media_Library_prevent_delete_attachment_ajax' ) , 0 , 2 );
		// add_filter( 'wp_ajax_nopriv_query-attachments', array( $this , 'YANN_NTNUSMIL_Media_Library_prevent_delete_attachment_ajax' ) , 0 , 2 );
	}

	/**
	 * Get reffer information from $_SERVER
	 *
	 * @param object $file file info
	 * 
	 * :See @https://stackoverflow.com/questions/4636166/only-variables-should-be-passed-by-reference
	 * 
	 * NOTICE: only $_SERVER global variable can provide the url information. DO NOT USE $_REQUEST here
	 * 
	 */
    private function IMAGES_UPLOAD___get_current_page_from_reffer_url($_server) {

		// validation
		if (!isset($_server['HTTP_REFERER'])) {
			return 'index.php';
		}

		$t = explode("?",$_server['HTTP_REFERER']);
		$p = array_reverse($t);
		$r = explode('/', array_pop($p));
		return array_pop( $r );
    }

	/**
	 * Add user contact methods
	 *
	 * @param object $file file info
	 */
    public function YANN_NTNUSMIL_create_upload_dir_for_avator() {
		$upload_dir = wp_upload_dir()['basedir'] . '/' . $this->custom_dirs['profile'];
		if ( ! is_dir($upload_dir) ) {
			mkdir( $upload_dir, 0700 );
		}
    }

	/**
	 * Add user contact methods
	 *
	 * @param object $file file info
	 */
    public function YANN_NTNUSMIL_custom_media_library_pre_upload( $file ) {
        add_filter( 'upload_dir' , array( $this , 'YANN_NTNUSMIL_custom_media_library_custom_upload_dir' ) , 10 , 1 );
        return $file;
    }

	/**
	 * Add user contact methods
	 *
	 * @param object $fileinfo file info
	 */
    public function YANN_NTNUSMIL_custom_media_library_post_upload( $fileinfo ) {
        remove_filter( 'upload_dir' , array( $this , 'YANN_NTNUSMIL_custom_media_library_custom_upload_dir' ) , 10 , 1 );
        return $fileinfo;
    }

	private function IMAGES_UPLOAD___validation_screen_now( $pagenow ) {

		// variables
		$errors = new WP_Error();

		// validation
		if ( ! in_array( $pagenow , $this->accepted_screens ) ) {
			$errors->add( __FUNCTION__, __('screen is invalid!', 'YANN_NTNUSMIL') );
        }

		return $errors;
	}

	private function IMAGES_UPLOAD___validation_file_type( $_request ) {

		// variables
		global $variables;
		$errors = new WP_Error();

		if ( isset( $_request['post_id'] ) ) {
			$post = get_post($_request['post_id']);
			$post_type = $post->post_type;
		}

		global $post;
		if (! empty($post) && ! isset($post_type) ) {
			$post_type = $post->post_type;
		}

		if (! isset($post_type)) {
			$errors->add( __FUNCTION__, __('validation of file type failed!', 'YANN_NTNUSMIL') );
		}

		$acceptable_file_types = array_merge(
			$variables->custom_post_types,
			$variables->wordpress_default_posttypes
		);

		if (! in_array($post_type, $acceptable_file_types)) {
			$errors->add( __FUNCTION__, __('invalid post type!', 'YANN_NTNUSMIL') );
		}

		return $errors;

	}

	/**
	 * Add user contact methods
	 *
	 * @param object $path file upload path info
	 */
	// @https://wordpress.stackexchange.com/questions/195453/different-upload-path-per-file-type
	// @https://stackoverflow.com/questions/8519968/changing-location-of-uploads-folder-for-custom-post-type-only-not-working
    public function YANN_NTNUSMIL_custom_media_library_custom_upload_dir( $path ) {

		// variables
		global $utils;

		// validation
		$pagenow = $this->IMAGES_UPLOAD___get_current_page_from_reffer_url($_SERVER);
		if ( $this->IMAGES_UPLOAD___validation_screen_now( $pagenow )->has_errors() ) {
			return $path;
		}

		// validation
		// we only accept 'jpg', 'jpeg', 'gif', 'png', and 'webp'
        $extension = substr(
			strrchr(
				$_POST['name'],
				'.'
			),
			1
		);

		// validation
        if (
			! empty($path['error'] ) ||
			! in_array( $extension,
						$this->accept_media_extension
					  )
		   ) {
            return $path;
        }

		// validation
		// we only accept some specific post type here
		if ( $this->IMAGES_UPLOAD___validation_file_type($_REQUEST)->has_errors() ) {
			return $path;
		}

		// change routes based on the pagenow we are at
		switch ($pagenow) {

			// profile
			case ('user-edit.php'):
				$customdir = '/' . $this->custom_dirs['profile'];
				break;

			case ('profile.php'):
				$customdir = '/' . $this->custom_dirs['profile'];
				break;

			// post and page
			case ('post.php'):
				$customdir = '/' . $this->custom_dirs['cpts'] . '/' . $utils->UTILS_get_post_type($_REQUEST) . '/' . $utils->UTILS_get_post_id($_REQUEST);
				break;

			case ('post-new.php'):
				$customdir = '/' . $this->custom_dirs['cpts'] . '/' . $utils->UTILS_get_post_type($_REQUEST) . '/' . $utils->UTILS_get_post_id($_REQUEST);
				break;

			// media upload
			case ('upload.php'):
				$customdir = '/' . $this->custom_dirs['cpts'];
				break;

			// default
			default:
				$customdir = $this->custom_dirs['default'];
				break;
				
		}        

        $path['path']    = str_replace($path['subdir'], '', $path['path']); //remove default subdir (year/month)
        $path['url']     = str_replace($path['subdir'], '', $path['url']);      
        $path['subdir']  = $customdir;
        $path['path']   .= $customdir; 
        $path['url']    .= $customdir;

		if ( ! is_dir($path['path']) ) {
			mkdir( $path['path'], 0700, true );
		}

        return $path;
    }

	public function IMAGES_UPLOAD_handle_uploaded_image( $file_upload_info ) {

		// variables
		global $variables;
		$extension = pathinfo(
			$file_upload_info['name'],
			PATHINFO_EXTENSION
		);
		$extension = strtolower($extension);

		// map some hetogenious extension to one
		$mimetype = $variables->extension_mimetype[$extension];
		$extension = $variables->mimetype_extension[$mimetype];
		
		// validation
		$pagenow = $this->IMAGES_UPLOAD___get_current_page_from_reffer_url($_SERVER);
		if ( $this->IMAGES_UPLOAD___validation_screen_now( $pagenow )->has_errors() ) {
			return $file_upload_info;
		}

		// validation
		// due to uploading thesis pdf file on profile page, we should give a return if is upload thesis file
		if ( ! in_array( $variables->mimetype_extension[$file_upload_info['type']],
						$this->accept_media_extension
			   )
		   ) {
			return $file_upload_info;
		}

		// validation
		// we only accept some specific post type here
		if ( $this->IMAGES_UPLOAD___validation_file_type($_REQUEST)->has_errors() ) {
			return $file_upload_info;
		}

		switch ($pagenow) {

			// profile
			case ('user-edit.php'):
				$new_name = $this->images_prefix['profile'] . '_' . $this->labname . '_@' . wp_get_current_user()->user_login . '-' . time() . '@_ori.' . $extension;
				break;

			case ('profile.php'):
				$new_name = $this->images_prefix['profile'] . '_' . $this->labname . '_@' . wp_get_current_user()->user_login . '-' . time() . '@_ori.' . $extension;
				break;

			// post and page
			case ('post.php'):
				$new_name = $this->images_prefix['cpts'] . '_' . $this->labname . '_@' . wp_get_current_user()->user_login . '-' . time() . '@_ori.' . $extension;
				break;

			case ('post-new.php'):
				$new_name = $this->images_prefix['cpts'] . '_' . $this->labname . '_@' . wp_get_current_user()->user_login . '-' . time() . '@_ori.' . $extension;
				break;

			// media upload
			case ('upload.php'):
				$new_name = $this->images_prefix['media'] . '_' . $this->labname . '_@' . wp_get_current_user()->user_login . '-' . time() . '@_ori.' . $extension;
				break;

			// default
			default:
				$new_name = $this->images_prefix['default'] . '_' . $this->labname . '_@' . wp_get_current_user()->user_login . '-' . time() . '@_ori.' . $extension;
				break;
				
		}  
		$file_upload_info['name'] = $new_name;

        return $file_upload_info;
    }

	/**
	 * Prevent Image Being Deleted by non-administrator
	 *
	 * @param object $delete , $post
	 */
	// @https://stackoverflow.com/questions/66473662/how-to-prevent-wordpress-media-library-from-removing-image
	// @https://wordpress.stackexchange.com/questions/99248/disable-media-uploads-to-non-admin-users
	public function YANN_NTNUSMIL_Media_Library_prevent_delete_attachment( $delete , $post ) {
		if ( $post->post_type !== 'attachment' ) {
			return;
		}
		if (! current_user_can( 'manage_options' )) {
			return false;
		}
	}

	// Prevent Image Being Uploaded by non-administrator
	// add_filter( 'wp_handle_upload_prefilter', 'tomjn_only_upload_for_admin' );
	// public function tomjn_only_upload_for_admin( $file ) {
	//     if ( ! current_user_can( 'manage_options' ) ) {
	//         $file['error'] = 'You can\'t upload images without admin privileges!';
	//     }
	//     return $file;
	// }
}

IMAGES_UPLOAD::register();
