<?php
/**
 * Class: DONWLOAD_API
 * Custom for NTNU SMIL LAB Meeting components.
 */

class DONWLOAD_API {
	private $labname;
	public $params;
	public $posttype;
	public $joystick;
	public $postid_userid;
	public $table_for_uploaded_file;
	public $upload_dir;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct( $params = array() ) {
		$this->labname = "NTNUSMIL";
		$this->params = $params;
		foreach ($params as $k => $v) {
            $this->$k = $v;
        }
		$this->add_hooks();
	}

	/**
	 * Register actions and filters.
	 */
	public function add_hooks() {
		add_action( 'rest_api_init', array( $this , 'DONWLOAD_API_register_restapi' ) );
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, * or null if none.
	 */
	// @https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	// @https://stackoverflow.com/questions/47455745/wordpress-api-permission-callback-check-if-user-is-logged-in
	// @https://wordpress.stackexchange.com/questions/323637/verify-nonce-in-rest-api
	// @https://wordpress.stackexchange.com/questions/296691/adding-wordpress-api-endpoint-with-multiple-parameters
	// @https://wordpress.stackexchange.com/questions/210597/query-wp-rest-api-v2-by-multiple-meta-keys
	public function DONWLOAD_API_register_restapi() {
		$postid_userid = $this->postid_userid;
		if ( $postid_userid == "postid" ) {
			$routes = '/'.$this->posttype.'/(?P<postid>\d+)/(?P<microtimestamp>\d+)/(?P<joystick>\w+)';
		} else {
			$routes = '/'.$this->posttype.'/(?P<userid>\d+)/(?P<microtimestamp>\d+)/(?P<joystick>\w+)';
		}
		register_rest_route( 'download/v2', $routes, array(
			'methods' => 'GET',
			'callback' => array( $this , 'DONWLOAD_API_restapi_download_file_callback' ),
			'args' => array(
				$postid_userid => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
				'microtimestamp' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
				'joystick' => array(
					'validate_callback' => function($param, $request, $key) {
						return in_array( $param , $this->joystick );
					}
				),
			),
			'permission_callback' => function ($param) {
				return is_user_logged_in();
			}
		) );
		register_rest_route( 'information/v2', $routes, array(
			'methods' => 'GET',
			'callback' => array( $this , 'DONWLOAD_API_restapi_get_information_callback' ),
			'args' => array(
				$postid_userid => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
				'microtimestamp' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
				'joystick' => array(
					'validate_callback' => function($param, $request, $key) {
						return in_array( $param , $this->joystick );
					}
				),
			),
			'permission_callback' => function ($param) {
				return is_user_logged_in();
			}
		) );
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, or null if none.
	 */
	public function DONWLOAD_API_restapi_get_information_callback( $data ) {
		$postid_userid = $this->postid_userid;
		$puid = $data[$postid_userid];
		$microtimestamp = $data["microtimestamp"];
		$joystick = $data["joystick"];

		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_for_uploaded_file;
		$DBppt = $wpdb->get_results ( "
			SELECT *
			FROM  $table_name
				WHERE CPTtype = '".$this->posttype."'
				AND ".$postid_userid." = '".$puid."'
				AND joystick = '".$joystick."'
				AND microtimestamp = '".$microtimestamp."'
		" );

		// validation
		if ( count($DBppt) !== 1 ) {
			return array( "error" => true , "errmessage" => "Should be only one!" );
		}

		// extract
		$DBppt = $DBppt[0];

		if ( isset($this->upload_dir) && !empty($this->upload_dir) ){
			$adddir = $this->upload_dir . "/" . $DBppt->filename;
		} else {
			$adddir = '/CPT/' . $DBppt->CPTtype . "/" . $DBppt->joystick . "/" . $DBppt->year . "/" . $DBppt->$postid_userid . "/" . $DBppt->filename;
		}

		$FilePath = wp_upload_dir()['basedir'] . $adddir;

		// return information
		$ext = $this->DONWLOAD_API_ext_get( $DBppt->mimetype );
		return array( 'success' => true , 'mimetype' => $DBppt->mimetype , 'newfilename' => $this->labname."_".time().".".$ext );
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, or null if none.
	 */
	public function DONWLOAD_API_restapi_download_file_callback( $data ) {
		$postid_userid = $this->postid_userid;
		$puid = $data[$postid_userid];
		$microtimestamp = $data["microtimestamp"];
		$joystick = $data["joystick"];

		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_for_uploaded_file;
		$DBppt = $wpdb->get_results ( "
			SELECT *
			FROM  $table_name
				WHERE CPTtype = '".$this->posttype."'
				AND ".$postid_userid." = '".$puid."'
				AND joystick = '".$joystick."'
				AND microtimestamp = '".$microtimestamp."'
		" );

		// validation
		if ( count($DBppt) !== 1 ) {
			return array( "error" => true , "errmessage" => "Should be only one!" );
		}

		// extract
		$DBppt = $DBppt[0];

		if ( isset($this->upload_dir) && !empty($this->upload_dir) ){
			$adddir = $this->upload_dir . "/" . $DBppt->filename;
		} else {
			$adddir = '/CPT/' . $DBppt->CPTtype . "/" . $DBppt->joystick . "/" . $DBppt->year . "/" . $DBppt->$postid_userid . "/" . $DBppt->filename;
		}

		$FilePath = wp_upload_dir()['basedir'] . $adddir;

		// download file
		$ext = $this->DONWLOAD_API_ext_get( $DBppt->mimetype );
		return $this->DONWLOAD_API_download_process( $FilePath , $DBppt->mimetype , $ext , true );
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, * or null if none.
	 */
	public function DONWLOAD_API_restapi_geturl() {
		$url = "download/v2/{$this->posttype}";
		return rest_url($url);
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, * or null if none.
	 */
	public function DONWLOAD_API_restinfoapi_geturl() {
		$url = "information/v2/{$this->posttype}";
		return rest_url($url);
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, * or null if none.
	 */
	public function DONWLOAD_API_download_process( $filepath , $type , $ext , $retbytes=true ) {
		
		$chunksize = 32;
		$fakeFileName = $this->labname."_".time().".".$ext;
		
		if ( file_exists( $filepath ) ) {
		
			set_time_limit( 0 );
		
			$buffer = '';
			$cnt = 0;

			header('Content-Description: File Transfer');
			header('Content-Type: '.$type);
			header('Content-Disposition: attachment; filename='.$fakeFileName);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: '.filesize($filepath));
		
			if ( intval(1) > $chunksize ) {
				$handle = fopen( $filepath , 'rb' );

				while ( ! feof( $handle ) ) {
					$buffer = fread( $handle, $chunksize );
					print( $buffer );
					ob_flush();
					flush();
					if ($retbytes) {
						$cnt += strlen($buffer);
					}
				}
				$status = fclose( $handle );
				if ( $retbytes && $status ) {
					return $cnt;
				}
				return $status;
				
			} else {
				ob_clean();
				flush();
				readfile( $filepath );
			}
			exit();
		} else {
			return array( 'error' => 'true' , 'errmessage' => "File is not exist, please contact webmaster." );
		}
	}

	/**
	 * Function to get correct MIME type for download
	 *
	 * :See @http://www.iana.org/assignments/media-types/media-types.xhtml
	 * :See @https://github.com/samuelneff/MimeTypeMap/blame/master/MimeTypeMap.cs
	 * :See @https://stackoverflow.com/questions/1029740/get-mime-type-from-filename-extension
	 * 
	 */
	private function DONWLOAD_API_mimeTypes_get( $ext ) { 
		// variables
		global $variables;
		$mime_types = $variables->extension_mimetype;

		return $mime_types[$ext];
	}

	private function DONWLOAD_API_ext_get( $mimetype ) { 
		// variables
		global $variables;
		$mime_types = $variables->mimetype_extension;

		return $mime_types[$mimetype];
	}

}

