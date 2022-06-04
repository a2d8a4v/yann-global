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
	 */
	// @http://www.iana.org/assignments/media-types/media-types.xhtml 
	// @https://github.com/samuelneff/MimeTypeMap/blame/master/MimeTypeMap.cs
	// @https://stackoverflow.com/questions/1029740/get-mime-type-from-filename-extension
	public function DONWLOAD_API_mimeTypes_get( $ext ) { 
		$mime_types = array (
			"323" => "text/h323", 
			"acx" => "application/internet-property-stream", 
			"ai" => "application/postscript", 
			"aif" => "audio/x-aiff", 
			"aifc" => "audio/x-aiff", 
			"aiff" => "audio/x-aiff", 
			"asf" => "video/x-ms-asf", 
			"asr" => "video/x-ms-asf", 
			"asx" => "video/x-ms-asf", 
			"au" => "audio/basic", 
			"avi" => "video/x-msvideo", 
			"axs" => "application/olescript", 
			"bas" => "text/plain", 
			"bcpio" => "application/x-bcpio", 
			"bin" => "application/octet-stream", 
			"bmp" => "image/bmp", 
			"c" => "text/plain", 
			"cat" => "application/vnd.ms-pkiseccat", 
			"cdf" => "application/x-cdf", 
			"cer" => "application/x-x509-ca-cert", 
			"class" => "application/octet-stream", 
			"clp" => "application/x-msclip", 
			"cmx" => "image/x-cmx", 
			"cod" => "image/cis-cod", 
			"cpio" => "application/x-cpio", 
			"crd" => "application/x-mscardfile", 
			"crl" => "application/pkix-crl", 
			"crt" => "application/x-x509-ca-cert", 
			"csh" => "application/x-csh", 
			"css" => "text/css", 
			"dcr" => "application/x-director", 
			"der" => "application/x-x509-ca-cert", 
			"dir" => "application/x-director", 
			"dll" => "application/x-msdownload", 
			"dms" => "application/octet-stream", 
			"doc" => "application/msword", 
			"dot" => "application/msword", 
			"dvi" => "application/x-dvi", 
			"dxr" => "application/x-director", 
			"eps" => "application/postscript", 
			"etx" => "text/x-setext", 
			"evy" => "application/envoy", 
			"exe" => "application/octet-stream", 
			"fif" => "application/fractals", 
			"flr" => "x-world/x-vrml", 
			"gif" => "image/gif", 
			"gtar" => "application/x-gtar", 
			"gz" => "application/x-gzip", 
			"h" => "text/plain", 
			"hdf" => "application/x-hdf", 
			"hlp" => "application/winhlp", 
			"hqx" => "application/mac-binhex40", 
			"hta" => "application/hta", 
			"htc" => "text/x-component", 
			"htm" => "text/html", 
			"html" => "text/html", 
			"htt" => "text/webviewhtml", 
			"ico" => "image/x-icon", 
			"ief" => "image/ief", 
			"iii" => "application/x-iphone", 
			"ins" => "application/x-internet-signup", 
			"isp" => "application/x-internet-signup", 
			"jfif" => "image/pipeg", 
			"jpe" => "image/jpeg", 
			"jpeg" => "image/jpeg", 
			"jpg" => "image/jpeg", 
			"js" => "application/x-javascript", 
			"keynote" => "application/vnd.apple.keynote",
			"latex" => "application/x-latex", 
			"lha" => "application/octet-stream", 
			"lsf" => "video/x-la-asf", 
			"lsx" => "video/x-la-asf", 
			"lzh" => "application/octet-stream", 
			"m13" => "application/x-msmediaview", 
			"m14" => "application/x-msmediaview", 
			"m3u" => "audio/x-mpegurl", 
			"man" => "application/x-troff-man", 
			"mdb" => "application/x-msaccess", 
			"me" => "application/x-troff-me", 
			"mht" => "message/rfc822", 
			"mhtml" => "message/rfc822", 
			"mid" => "audio/mid", 
			"mny" => "application/x-msmoney", 
			"mov" => "video/quicktime", 
			"movie" => "video/x-sgi-movie", 
			"mp2" => "video/mpeg", 
			"mp3" => "audio/mpeg", 
			"mpa" => "video/mpeg", 
			"mpe" => "video/mpeg", 
			"mpeg" => "video/mpeg", 
			"mpg" => "video/mpeg", 
			"mpp" => "application/vnd.ms-project", 
			"mpv2" => "video/mpeg", 
			"ms" => "application/x-troff-ms", 
			"mvb" => "application/x-msmediaview", 
			"numbers" => "application/vnd.apple.numbers",
			"nws" => "message/rfc822", 
			"oda" => "application/oda", 
			"p10" => "application/pkcs10", 
			"p12" => "application/x-pkcs12", 
			"p7b" => "application/x-pkcs7-certificates", 
			"p7c" => "application/x-pkcs7-mime", 
			"p7m" => "application/x-pkcs7-mime", 
			"p7r" => "application/x-pkcs7-certreqresp", 
			"p7s" => "application/x-pkcs7-signature", 
			"pages" => "application/x-iwork-pages-sffpages",
			"pbm" => "image/x-portable-bitmap", 
			"pdf" => "application/pdf", 
			"pfx" => "application/x-pkcs12", 
			"pgm" => "image/x-portable-graymap", 
			"pko" => "application/ynd.ms-pkipko", 
			"pma" => "application/x-perfmon", 
			"pmc" => "application/x-perfmon", 
			"pml" => "application/x-perfmon", 
			"pmr" => "application/x-perfmon", 
			"pmw" => "application/x-perfmon", 
			"pnm" => "image/x-portable-anymap", 
			"pot" => "application/vnd.ms-powerpoint", 
			"ppm" => "image/x-portable-pixmap", 
			"pps" => "application/vnd.ms-powerpoint", 
			"ppt" => "application/vnd.ms-powerpoint", 
			"pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation", 
			"prf" => "application/pics-rules", 
			"ps" => "application/postscript", 
			"pub" => "application/x-mspublisher", 
			"qt" => "video/quicktime", 
			"ra" => "audio/x-pn-realaudio", 
			"ram" => "audio/x-pn-realaudio", 
			"ras" => "image/x-cmu-raster", 
			"rgb" => "image/x-rgb", 
			"rmi" => "audio/mid", 
			"roff" => "application/x-troff", 
			"rtf" => "application/rtf", 
			"rtx" => "text/richtext", 
			"scd" => "application/x-msschedule", 
			"sct" => "text/scriptlet", 
			"setpay" => "application/set-payment-initiation", 
			"setreg" => "application/set-registration-initiation", 
			"sh" => "application/x-sh", 
			"shar" => "application/x-shar", 
			"sit" => "application/x-stuffit", 
			"snd" => "audio/basic", 
			"spc" => "application/x-pkcs7-certificates", 
			"spl" => "application/futuresplash", 
			"src" => "application/x-wais-source", 
			"sst" => "application/vnd.ms-pkicertstore", 
			"stl" => "application/vnd.ms-pkistl", 
			"stm" => "text/html", 
			"svg" => "image/svg+xml", 
			"sv4cpio" => "application/x-sv4cpio", 
			"sv4crc" => "application/x-sv4crc", 
			"t" => "application/x-troff", 
			"tar" => "application/x-tar", 
			"tcl" => "application/x-tcl", 
			"tex" => "application/x-tex", 
			"texi" => "application/x-texinfo", 
			"texinfo" => "application/x-texinfo", 
			"tgz" => "application/x-compressed", 
			"tif" => "image/tiff", 
			"tiff" => "image/tiff", 
			"tr" => "application/x-troff", 
			"trm" => "application/x-msterminal", 
			"tsv" => "text/tab-separated-values", 
			"txt" => "text/plain", 
			"uls" => "text/iuls", 
			"ustar" => "application/x-ustar", 
			"vcf" => "text/x-vcard", 
			"vrml" => "x-world/x-vrml", 
			"wav" => "audio/x-wav", 
			"wcm" => "application/vnd.ms-works", 
			"wdb" => "application/vnd.ms-works", 
			"wks" => "application/vnd.ms-works", 
			"wmf" => "application/x-msmetafile", 
			"wps" => "application/vnd.ms-works", 
			"wri" => "application/x-mswrite", 
			"wrl" => "x-world/x-vrml", 
			"wrz" => "x-world/x-vrml", 
			"xaf" => "x-world/x-vrml", 
			"xbm" => "image/x-xbitmap", 
			"xla" => "application/vnd.ms-excel", 
			"xlc" => "application/vnd.ms-excel", 
			"xlm" => "application/vnd.ms-excel", 
			"xls" => "application/vnd.ms-excel", 
			"xlt" => "application/vnd.ms-excel", 
			"xlw" => "application/vnd.ms-excel", 
			"xof" => "x-world/x-vrml", 
			"xpm" => "image/x-xpixmap", 
			"xwd" => "image/x-xwindowdump", 
			"z" => "application/x-compress", 
			"rar" => "application/x-rar-compressed", 
			"zip" => "application/zip"
		);
	
		return $mime_types[$ext];
	}

	public function DONWLOAD_API_ext_get( $mimetype ) { 
		$mime_types = array(
			"application/pdf" => "pdf",
			"application/vnd.ms-powerpoint" => "ppt",
			"application/vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
			"application/x-iwork-pages-sffpages" => "pages",
			"application/fsharp-script" => "fsx",
			"application/msaccess" => "adp",
			"application/msword" => "doc",
			"application/octet-stream" => "bin",
			"application/onenote" => "one",
			"application/postscript" => "eps",
			"application/step" => "step",
			"application/vnd.ms-excel" => "xls",
			"application/vnd.ms-works" => "wks",
			"application/vnd.visio" => "vsd",
			"application/x-director" => "dir",
			"application/x-msdos-program" => "exe",
			"application/x-shockwave-flash" => "swf",
			"application/x-x509-ca-cert" => "cer",
			"application/x-zip-compressed" => "zip",
			"application/xhtml+xml" => "xhtml",
			"application/xml" => "xml",
			"audio/aac" => "AAC",
			"audio/aiff" => "aiff",
			"audio/basic" => "snd",
			"audio/mid" => "midi",
			"audio/mp4" => "m4a",
			"audio/wav" => "wav",
			"audio/x-m4a" => "m4a",
			"audio/x-mpegurl" => "m3u",
			"audio/x-pn-realaudio" => "ra",
			"audio/x-smd" => "smd",
			"image/bmp" => "bmp",
			"image/jpeg" => "jpg",
			"image/pict" => "pic",
			"image/png" => "png",
			"image/x-png" => "png",
			"image/tiff" => "tiff",
			"image/x-macpaint" => "mac",
			"image/x-quicktime" => "qti",
			"message/rfc822" => "eml",
			"text/calendar" => "ics",
			"text/html" => "html",
			"text/plain" => "txt",
			"text/scriptlet" => "wsc",
			"text/xml" => "xml",
			"video/3gpp" => "3gp",
			"video/3gpp2" => "3gp2",
			"video/mp4" => "mp4",
			"video/mpeg" => "mpg",
			"video/quicktime" => "mov",
			"video/vnd.dlna.mpeg-tts" => "m2t",
			"video/x-dv" => "dv",
			"video/x-la-asf" => "lsf",
			"video/x-ms-asf" => "asf",
			"x-world/x-vrml" => "xof",
		);

		return $mime_types[$mimetype];
	}

}

