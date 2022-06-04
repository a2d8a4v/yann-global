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
	private static $run;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->errors = False;
		$this->YANN_root_user_id = 1;
		$this->customdir = 'members';
		if (self::$run) {
			$this->add_hooks();
		}
	}

	/**
	 * Registers the plugin.
	 */
	public static function register($run) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new IMAGES_UPLOAD();
				self::$run = $run;
			}
		}
	}

	/**
	 * Register actions and filters.
	 */
	public function add_hooks() {
		add_action('admin_init', array( $this, 'YANN_NTNUSMIL_create_upload_dir_for_avator') , 10 , 0 );

		add_filter('wp_handle_upload_prefilter', array( $this , 'YANN_NTNUSMIL_handle_uploadedimage' ) , 10 , 1 );
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
	 */
	// @https://stackoverflow.com/questions/4636166/only-variables-should-be-passed-by-reference
    public function YANN_NTNUSMIL_get_current_page_from_reffer_url($SERVER) {
		if (!isset($SERVER['HTTP_REFERER'])) {
			return 'index.php';
		}
		$t = explode("?",$SERVER['HTTP_REFERER']);
		$p = array_reverse($t);
		$r = explode("/", array_pop($p));
		return array_pop( $r );
    }

	/**
	 * Add user contact methods
	 *
	 * @param object $file file info
	 */
    public function YANN_NTNUSMIL_create_upload_dir_for_avator() {
		$upload_dir = wp_upload_dir()['basedir'] . "/" . $this->customdir;
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

	/**
	 * Add user contact methods
	 *
	 * @param object $path file upload path info
	 */
	// @https://wordpress.stackexchange.com/questions/195453/different-upload-path-per-file-type
    public function YANN_NTNUSMIL_custom_media_library_custom_upload_dir( $path ) {

		$pagenow = $this->YANN_NTNUSMIL_get_current_page_from_reffer_url($_SERVER);
		if ( ! in_array( $pagenow , array("user-edit.php", "profile.php") ) ) {
            return $path;
        }

        $extension = substr(strrchr($_POST['name'],'.'),1);
        if ( !empty( $path['error'] ) || ! in_array( $extension ,array('jpg','jpeg','gif','png','webp') ) ) {
            //error or other filetype; do nothing. 
            return $path; 
        }

        $customdir = "/" . $this->customdir;

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

	public function YANN_NTNUSMIL_handle_uploadedimage($arr) {

		$pagenow = $this->YANN_NTNUSMIL_get_current_page_from_reffer_url($_SERVER);

		if ( ! in_array( $pagenow , array("user-edit.php","profile.php") ) ) {
            return $arr;
        }
		$ext = pathinfo($arr['name'], PATHINFO_EXTENSION);

		// due to needing to upload thesis pdf file at profile page, should return if is pdf
		if ( $ext == "pdf" ) {
			return $arr;
		}

		$arr['name'] = "A00_NTNUSMIL_@NTNUSMIL_" . wp_get_current_user()->user_login . "-" . time() . "@_ori." . $ext;

        return $arr;
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

IMAGES_UPLOAD::register(TRUE);