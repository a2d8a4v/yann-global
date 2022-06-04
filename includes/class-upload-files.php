<?php
/**
 * Class: UPLOAD_FILES
 * Custom for NTNU SMIL LAB Meeting components.
 * 
 * @ See: https://www.cssigniter.com/how-to-add-custom-fields-to-the-wordpress-registration-form/
 * 
 */

use Monolog\Logger;
use PHPExiftool\Reader;
use PHPExiftool\Driver\Value\ValueInterface;
use Stringy\Stringy;

class UPLOAD_FILES {

	private $errors;
	private $labname;
	public $params;
	
	public $run;
	public $_post;
	public $_files;
	public $_server;
	public $posttype;
	public $upload_dir;
	public $postid_userid;
	public $postid_useridjs;
	public $joystick_types_arr;
	public $table_for_uploaded_file;

	/**
	 * This plugin's instance.
	 *
	 * @var UPLOAD_FILES
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register($run, $params) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new UPLOAD_FILES($params);
				self::$run = $run;
			}
		}
	}

	/**
	 * Set up the hooks and default values
	 */
	public function __construct($params=array()) {

		$this->run = TRUE;
		$this->errors = False;
		$this->YANN_root_user_id = 1;
		$this->labname = "NTNUSMIL";
		$this->spliter = "YANNYANNYANN";

		$this->year = "";
		$this->joystick = "";
		$this->postid_userid_num = "";
		$this->table_for_option_record = "CPT_meeting_table_for_conference_names";
		$this->allowed_types = array(
			"application/vnd.ms-powerpoint",
			"application/vnd.apple.keynote",
			"application/vnd.openxmlformats-officedocument.presentationml.presentation",
			"application/pdf",
		);

		// validation
		foreach($params as $k => $v) {
			if ( in_array($k,
					// 'upload_dir' is unnecessary
					array(
						'_post',
						'_files',
						'_server',
						'posttype',
						'joystick_types_arr',
						'postid_userid',
						'postid_useridjs',
						'table_for_uploaded_file',
					)
				)
			) {
				continue;
			} else if ( in_array($k,
					array(
						'upload_dir',
					)
				)
			) {
				continue;
			} else {
				throw new Exception("Key {$k} not ready!");
			}
			if ( $k == 'postid_userid' ) {
				if ( !in_array($v, array('postid', 'userid')) ) {
					throw new Exception('Key should be postid or userid!');
				}
			}
			if ( $k == 'postid_useridjs' ) {
				if ( !in_array($v, array('postidjs', 'useridjs')) ) {
					throw new Exception('Key should be postidjs or useridjs!');
				}
			}
			if ( $k == 'posttype' ) {
				if ( !in_array($v, array('paper', 'ppt', 'thesis', 'publications')) ) {
					throw new Exception('posttype only have paepr, ppt, thesis three types!');
				}
			}
		}
		
		// assign
		foreach ($params as $k => $v) {
			$this->$k = $v;
        }

		// validation
		if ( in_array($this->posttype , array('paper', 'ppt', 'publications')) ) {
			if ($this->postid_userid !== 'postid' || $this->postid_useridjs !== 'postidjs') {
				throw new Exception('postid_userid and postid_useridjs should be postid-related!');
			}
		} else if ( in_array($this->posttype , array('thesis')) ) {
			if ($this->postid_userid !== 'userid' || $this->postid_useridjs !== 'useridjs') {
				throw new Exception('postid_userid and postid_useridjs should be userid-related!');
			}
		}

		// update $_POST with additive information
		$this->UPLOAD_FILES_update__post();

	}

	private function UPLOAD_FILES_update__post() {
		
		$_post = $this->_post;
		$_server = $this->_server;

		// get edited user id
		$POST_new = $_post;
		$query_str = parse_url(urldecode($_server["HTTP_REFERER"]), PHP_URL_QUERY);
		parse_str($query_str, $query_params);

		if (isset($query_params["user_id"])) {
			$POST_new["current_user_id"] = $query_params["user_id"];
		}

		$this->_post = $POST_new;
	}


	public function UPLOAD_FILES_handle_upload_delete_validation() {

		$POST = $this->_post;

		// nonce
		$nonce = ( isset( $POST[ 'nonce' ] ) ) ? wc_clean( wp_unslash( $POST[ 'nonce' ] ) ) : '';

		// ajax nonce check
		if ( ! wp_verify_nonce( $nonce , 'YANN-ajax-nonce' ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}

		// file exist and doing by ajax
		if ( ! ( is_array($POST) && is_array($_FILES) && defined('DOING_AJAX') && DOING_AJAX ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}

		// joystick check
		if ( ! isset($POST['joystick']) || ( isset($POST['joystick']) && !in_array( $POST['joystick'] , $this->joystick_types_arr ) ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Do not change the joystick attributes!' ) );
			wp_die();
		}

		// postid or userid check
		if ( ! isset($POST[$this->postid_userid]) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'No '.$this->postid_userid ) );
			wp_die();
		}

		// postidjs or useridjs check
		if ( ! isset($POST[$this->postid_useridjs]) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'No '.$this->postid_useridjs ) );
			wp_die();
		}

		// should be the same
		if ( $POST[$this->postid_useridjs] !== $POST[$this->postid_userid] ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'postid not equal '.$this->postid_useridjs ) );
			wp_die();
		}

		return TRUE;
	}


	public function UPLOAD_FILES_upload_handle_reply() {
		$status = $this->UPLOAD_FILES_upload_call();
		$this->UPLOAD_FILES_return_status_check($status);
	}


	/**
	 * Ajax call for YANN_NTNUSMIL_CPT_meeting_input_for_paper_ppt_upload_handle function
	 *
	 */
	// @https://makitweb.com/how-to-upload-multiple-image-files-with-jquery-ajax-and-php/
	// @https://dotblogs.com.tw/newmonkey48/2017/01/04/164650
	// @https://developer.wordpress.org/reference/hooks/upload_dir/
	// @https://www.codexworld.com/upload-multiple-images-using-jquery-ajax-php/
	// @https://www.sitepoint.com/html5-file-drag-and-drop/
	// @https://wordpress.stackexchange.com/questions/198781/wordpress-ajax-file-upload-frontend
	// @https://theaveragedev.com/wordpress-files-ajax/
	// @https://stackoverflow.com/questions/4178873/php-uploading-multiple-files
	// @https://wordpress.stackexchange.com/questions/49980/get-the-post-id-of-a-new-post
	// @https://vimsky.com/zh-tw/examples/detail/php-ex-----wp_upload_bits.html
	// @https://wordpress.stackexchange.com/questions/326704/how-to-wp-upload-bits-to-a-sub-folder
	// @https://www.phpkida.com/upload-file-using-ajax-in-wordpress/
	// @https://www.davidangulo.xyz/how-to-upload-files-in-wordpress-programmatically/
	// @https://rudrastyh.com/wordpress/how-to-add-images-to-media-library-from-uploaded-files-programmatically.html
	private function UPLOAD_FILES_upload_call() {
 
		if (!function_exists('wp_handle_upload')) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		}
	
		$POST = $this->_post;
		$FILES = $this->_files;
	
		// Variables
		global $wpdb, $utils;
		$this->joystick = $POST['joystick'];
		$this->postid_userid_num = $POST[$this->postid_useridjs];
		$_error = array();
		$_warning = array();
		$_success = array();
		$_count = 0;
	
		// author check
		$this->UPLOAD_FILES_postid_userid_check();
	
		// files counts
		if ( count($FILES['files']['name']) > 1 ) {
			return array( 'error' => true , 'errmessage' => '目前最多只開放同時上傳 1 個檔案。' );
		}
	
		// has file check
		$table_name = $wpdb->prefix . $this->table_for_uploaded_file;
		$DBsearch = $wpdb->get_results ( "
		SELECT ".$this->postid_userid." 
		FROM  $table_name
			WHERE ".$this->postid_userid." = '".$this->postid_userid_num."'
			AND CPTtype = '".$this->posttype."'
			AND joystick = '".$this->joystick."'
		" );
		if ( !empty($DBsearch) ) {
			return array( 'error' => true , 'errmessage' => "已經有檔案上傳了，請再重新整理瀏覽器一次。" );
		}
	
		foreach ( $FILES['files']['name'] as $key=>$val ) {

			// for multiple files
			$file_name  = $FILES['files']['name'][$key];
			$tmp_name   = $FILES['files']['tmp_name'][$key];
			$size       = $FILES['files']['size'][$key];
			$type       = $FILES['files']['type'][$key];
			$error      = $FILES['files']['error'][$key];
	
			// other variables
			$_ext           = pathinfo($file_name, PATHINFO_EXTENSION);
			$_time          = time();
			$joystick       = $this->joystick;
			$posttype       = $this->posttype;
			$labname        = $this->labname;
			$microtime      = $utils->UTILS_millitime();
			$Year           = date('Y',$_time);
			$this->year     = $Year;
			$_new_file_name = $labname."_".$posttype."_".$joystick."_".$this->postid_userid_num."_".$_time."_".$microtime.".".$_ext;
	
			// file information
			$uploadedfile = array(
				'name'     => $_new_file_name,
				'type'     => $type,
				'tmp_name' => $tmp_name,
				'error'    => $error,
				'size'     => $size,
			);

			// Check file is normal
			$parsed_pdf = $this->UPLOAD_FILES_parsePDFfile($tmp_name);
			if ( is_wp_error($parsed_pdf) ) {
				$_error["upload-v_".$key] = array( "file" => $uploadedfile , "result" => '這個檔案似乎已經損壞，或者根本不是 PDF 檔案。' );
				continue;
			}

			// Validation for pages in thesis
			if ( $this->joystick === "thesis" ) {
				// Check that the file is not an empty file
				if ( count($parsed_pdf->getPages()) < 30 ) {
					$_error["upload-v_".$key] = array( "file" => $uploadedfile , "result" => '論文頁數小於 30 頁。' );
					continue;
				}
			}

			// Validtion for file type
			if ( $this->joystick === "paper" ) {
				if ( !in_array( $type , array( "application/pdf" ) ) ) {
					$_error["ext_".$key] = array( "data" => "檔案格式不是 pdf 檔案，所以不上傳。" , "file" => $file_name );
					continue;
				}
			} else if ( $this->joystick === "ppt" ) {
				if ( !in_array( $type , $this->allowed_types ) ) {
					$_error["ext_".$key] = array( "data" => "檔案格式不是 ppt, pptx, keynote 或 pdf 檔案，所以不上傳。" , "file" => $file_name );
					continue;
				}
				// warning for PDF file
				if ( in_array( $type , array( "application/pdf" ) ) ) {
					$_warning["ext_".$key] = array( "data" => "PPT 的檔案格式盡量避免使用 pdf 檔案，盡可能使用 ppt, pptx 或 keynote。" , "file" => $file_name );
				}
			} else if ( $this->joystick === "thesis" ) {
				if ( !in_array( $type , array( "application/pdf" ) ) ) {
					$_error["ext_".$key] = array( "data" => "檔案格式不是 pdf 檔案，所以不上傳。" , "file" => $file_name );
					continue;
				}
			} else if ( $this->joystick === 'publications' ) {
				if ( !in_array( $type , $this->allowed_types ) ) {
					$_error["ext_".$key] = array( "data" => "檔案格式不是 ppt, pptx, keynote 或 pdf 檔案，所以不上傳。" , "file" => $file_name );
					continue;
				}
			} else {
				$_error["upload-v_".$key] = array( "file" => $uploadedfile , "result" => '並非目前支援的 CPT 種類的一種，請聯繫網站管理員。' );
			}
	
			// insert record
			$table_name = $wpdb->prefix . $this->table_for_uploaded_file;
			$data = array(
				$this->postid_userid => $this->postid_userid_num ,
				'Labname' => $labname ,
				'CPTtype' => $posttype ,
				'joystick' => $joystick ,
				'timestamp' => $_time ,
				'microtimestamp' => $microtime ,
				'filename' => $_new_file_name ,
				'year' => $Year ,
				'mimetype' => $type ,
				'counts' => 0 ,
			);
			$data_format   = array( '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%d' );
			if ( !$wpdb->insert( $table_name , $data , $data_format ) ) {
				return array( 'error' => true , 'errmessage' => "database insert error." );
			}
	
			if (!isset($this->upload_dir)) {
				$this->upload_dir = '/CPT/' . $posttype . "/" . $joystick . "/" . $Year . "/" . $this->postid_userid_num;
			}
	
			// Upload file
			$upload_overrides = array(
				'test_form' => FALSE,
			);
			// Create directory
			$upload_dir = wp_upload_dir()['basedir'] . $this->upload_dir;
			if ( ! is_dir($upload_dir) ) {
				mkdir( $upload_dir, 0700, true );
			}
			// Register our upload dir path
			add_filter( 'upload_dir', array( $this , 'UPLOAD_FILES_upload_path_change_temporarily' ) );
			// Upload file
			$movefile = wp_handle_upload( $uploadedfile , $upload_overrides );
			// Set everything back to normal
			remove_filter( 'upload_dir', array( $this , 'UPLOAD_FILES_upload_path_change_temporarily' ) );
	
			$this->upload_dir = "";
			
			if ( $movefile && ! isset( $movefile['error'] ) ) {
	
				// Get PDF information
				if ( $this->joystick === "paper" ) {
					
					// Get file PDF exif information
					$GetExif = $this->UPLOAD_FILES_pdf_file_get($movefile["file"]);
	
					// If not success, return error message
					if ( is_wp_error($GetExif) ) {
						$_error["upload-f_".$key] = array( "file" => $uploadedfile , "result" => $GetExif->get_error_message() );
						continue;
					}
					
					if (isset($GetExif["PDF:Title"])) {
						$movefile["pdftitle"] = $GetExif["PDF:Title"];
					}
					if (isset($GetExif["XMP-prism:URL"])) {
						$movefile["pdfurl"] = $GetExif["XMP-prism:URL"];
					}
					if (isset($GetExif["ConferenceYear"])) {
						$movefile["pdfcofyear"] = $GetExif["ConferenceYear"];
					}
					if (isset($GetExif["ConferenceTitle"])) {
						$movefile["pdfcoftitle"] = $GetExif["ConferenceTitle"];
					}
				}
	
				$movefile["newname"] = $_new_file_name;
				$movefile["microtime"] = $microtime;
				$_success["upload-s_".$key] = $movefile;
				continue;
			} else {
				// remember remove inserted sql
				$wpdb->delete( $table_name , $data , $data_format );
				$_error["upload-f_".$key] = array( "file" => $uploadedfile , "result" => $movefile );
			}
		}
	
		if ( empty($_error) && empty($_warning) ) {
			return array( 'success' => true , 'data' => $_success , 'all' => true , "message" => "全部 ".count($_success)." 筆檔案上傳成功", $this->postid_userid => $this->postid_userid_num );
		} else if ( ! empty($_success) && ( ! empty($_error) || ! empty($_warning) ) ) {
			return array( 'success' => true , 'data' => array_merge($_success , $_error, $_warning) , "message" => "全部 ".count($_success)." 筆檔案上傳成功，".count($_error)." 筆檔案上傳失敗", $this->postid_userid => $this->postid_userid_num );
		} else {
			return array( 'error' => true , 'data' => $_error );
		}
	
		// reset joystick, year and postid_userid_num attribute
		$this->year = "";
		$this->joystick = "";
		$this->postid_userid_num = "";
	
	}
	

	/**
	 * Function to change upload path
	 *
	 * :See @https://wordpress.stackexchange.com/questions/141088/wp-handle-upload-how-to-upload-to-a-custom-subdirectory-within-uploads
	 * :See @https://wpmayor.com/code-snippet-to-create-a-directory-within-uploads-folder/
	 * 
	 */
	public function UPLOAD_FILES_upload_path_change_temporarily( $param ) {
	
		// $this->upload_dir 
		$adddir = $this->upload_dir;
		$param['path'] = $param['path'] . $adddir;
		$param['url'] = $param['url'] . $adddir;
	
		return $param;
	}


	/**
	 * Handle password-protected documents
	 * @ See: https://github.com/smalot/pdfparser/issues/134
	 * 
	 */
	private function UPLOAD_FILES_parsePDFfile($file) {
		$parser = new \Smalot\PdfParser\Parser();
		try {
            $pdf = $parser->parseFile($file);
        } catch (Exception $e) {
            if ($t = $e->getMessage()) {
				return new WP_Error('500', __( $t, "my_textdomain" ) );
            }
        }
		if ( isset($pdf) ) {
			return $pdf;
		}
	}


	/**
	 * Function to get PDF file title
	 *
	 */
	// @https://github.com/smalot/pdfparser/issues/200
	// @https://ruiicheese.wordpress.com/2018/05/08/convert-pdf-to-text-in-php/
	public function UPLOAD_FILES_pdf_file_get($file) {
		$keys = "";
		$values = "";
		$output = "";
		if ( ! empty(trim(shell_exec("file {$file} | grep 'PDF document'"))) ) {
			global $utils;
			$logger = new Logger('exiftool');
			$reader = Reader::create($logger);
			$metadatas = $reader->files($file)->first();
			foreach ($metadatas as $metadata) {
				$keys .= $this->spliter.$metadata->getTag();
				$values .= $this->spliter.$metadata->getValue()->asString();
			}
			$keys_arr = explode($this->spliter,$keys);
			$vals_arr = explode($this->spliter,$values);
			array_shift($keys_arr);
			array_shift($vals_arr);
			$rtn = $utils->UTILS_array_zip($keys_arr,$vals_arr);

			// only paper need to use other API
			if ( $this->joystick !== 'paper' ) {
				return $rtn;
			}

			// Second method for adding paper title information
			if ( !isset($rtn["PDF:Title"]) || ( isset($rtn["PDF:Title"]) && empty($rtn["PDF:Title"]) ) ) {
				// The first line should be the title of paper
				$pdf = $this->UPLOAD_FILES_parsePDFfile($file);
				if ( is_wp_error($pdf) ) {
					// Problem with paper
					return $pdf;
				}
				$pages = $pdf->getPages();

				if (!empty($pages)) {

					// Use the content from the first page only
					$text = $pages[0]->getText();
					$lines = explode("\n", $text);

					try {
						$papers = $this->useArxivToSearch($lines[0]);
						if ( empty($papers) ) {
							// No paper is gotten
							throw new exception();
						}
						if ( $utils->UTILS_twoSeqSimilarity(strtolower($lines[0]), strtolower($papers[0]->title))*100 < 20.0 ) {
							// Similarity is too low
							throw new exception();
						}
						$rtn["PDF:Title"]       = $papers[0]->title; // Title
						$rtn["XMP-prism:URL"]   = $papers[0]->getId(); // Link
						if ( empty($papers[0]->comment) ) {
							// Empty comment
							throw new exception();
						}

						// Year
						$a = preg_match_all("/[0-9]{4}/", $papers[0]->comment, $m);
						$b = preg_replace("/[0-9]{4}/", "", $papers[0]->comment);
						unset($papers);
						$a = array();
						foreach ( $m as $y_arr ) {
							foreach( $y_arr as $y ) {
								if ( strlen($y) !== 4 ){
									continue;
								}
								if ( !in_array(strval($y)[0], array('2','1')) ) {
									continue;
								}
								$a[] = $y;
							}
						}
						$rtn["ConferenceYear"] = !empty($a)?$a[0]:""; // Conference year

						// Title
						global $wpdb;
						$tbn = $wpdb->prefix.$this->table_for_option_record;
						$Ser = $wpdb->get_results( "SELECT * FROM $tbn" );
						$all = array();
						foreach( $Ser as $s_c ) {
							$all[] = $s_c->name;
						}
						$tbn = new YANN_MEETING_OPTION();
						if ( !empty($tbn->conference_names_default) ) {
							foreach( $tbn->conference_names_default as $s_c ) {
								$all[] = $s_c;
							}
						}
						unset($tbn);
						$a   = explode(" ", strtolower($b));
						$m   = array();
						$Ser = array_map('strtolower', $all);
						foreach( $a as $y ) {
							if (!in_array($y, $Ser)) {
								continue;
							}
							$k = array_search($y, $Ser);
							$m[] = $all[intval($k)];
						}
						$rtn["ConferenceTitle"] = !empty($m)?$m[0]:"Arxiv"; // Conference title
					} catch (Exception $e) {
						// Use "Abstract" as the word to split the title, authors and organization out
						$key = array_search("abstract", array_map('strtolower', $lines));
						// Get the title, authors, organization
						$get = array_slice($lines, 0, intval($key-1));
						$get = implode(" ", $get);
						$get = $utils->UTILS_getTitleCase( $utils->UTILS_cleanAsciiCharacters( $get ) );
						if ( !empty($get) ) {
							$rtn["PDF:Title"] = $get;
						}
					}
				}
			}
			return $rtn;
		}
		return $output;
	}


	/**
	 * Use Arxiv API to search 
	 *
	 */
	private function useArxivToSearch($title) {
		// Make the search words to lower-case
		global $utils;
		$title = $utils->UTILS_removeBOMcharacters( strtolower($title) );
		$queryBuilder = SearchQueryBuilder::getInstance();
		$queryBuilder->setTitle($title);
		$api = new ArxivAPI();
		$papers = $api->getPapersWithBuilder($queryBuilder, 0, 1, "relevance", "descending");
		return $papers;
	}


	public function UPLOAD_FILES_delete_file_handle_reply() {
		$status = $this->UPLOAD_FILES_delete_file_call();
		$this->UPLOAD_FILES_return_status_check($status);
	}


	private function UPLOAD_FILES_return_status_check( $status ) {
		if ( array_key_exists( 'success' , $status ) ) {
			wp_send_json_success( $status );
		} else if ( array_key_exists( 'error' , $status ) ) {
			wp_send_json_error( $status );
			wp_die();
		} else {
			wp_send_json_error( array( 'error' => true , 'errmessage' => '哪裡怪怪的，請檢察程式碼修正錯誤' ) );
			wp_die();
		}
		return TRUE;
	}


	/**
	 * Ajax call for UPLOAD_FILES_delete_file_handle_reply function
	 *
	 */
	public function UPLOAD_FILES_delete_file_call() {

		$POST = $this->_post;

		$joystick = $POST['joystick'];
		$filename = $POST['file'];
		$labname  = $this->labname;
		$posttype = $this->posttype;
		$this->postid_userid_num = $POST[$this->postid_useridjs];

		// author check
		$this->UPLOAD_FILES_postid_userid_check();

		// Validation file name
		$filename_arr = explode("_",$filename);
		if ( $filename_arr[0] !== $labname ) {
			return array( 'error' => true , 'errmessage' => 'Lab name error.' );
		} else if ( $filename_arr[1] !== $posttype ) {
			return array( 'error' => true , 'errmessage' => 'posttype error.' );
		} else if ( $filename_arr[2] !== $joystick ) {
			return array( 'error' => true , 'errmessage' => 'joystick error.' );
		} else if ( !preg_match('/^[0-9]*$/' , intval($filename_arr[3]) ) || intval($filename_arr[3]) !== intval($this->postid_userid_num) ) {
			return array( 'error' => true , 'errmessage' => 'postid error.' );
		} else if ( !preg_match('/^[0-9]*$/' , intval($filename_arr[4]) ) ) {
			return array( 'error' => true , 'errmessage' => 'timestamp error.' );
		}
		
		// delete file
		$micro_dot = explode(".",$filename_arr[5]);
		$timestamp = $filename_arr[4];
		$micro = $micro_dot[0];

		if ( !preg_match('/^[0-9]*$/' , intval($micro) ) ) {
			return array( 'error' => true , 'errmessage' => 'micro timestamp error.' );
		}

		$_ext = $micro_dot[1];
		$Year = date("Y", $timestamp);
		if (!isset($this->upload_dir)) {
			$this->upload_dir = '/CPT/' . $posttype . "/" . $joystick . "/" . $Year . "/" . $this->postid_userid_num;
		}
		$file_pointer = wp_upload_dir()['basedir'].$this->upload_dir."/".$labname."_".$posttype."_".$joystick."_".$this->postid_userid_num."_".$timestamp."_".$micro.".".$_ext;
		if (!unlink($file_pointer)) { 
			return array( 'error' => true , 'errmessage' => "$filename cannot be deleted due to non-existing file." );
		} 

		// delete record
		global $wpdb;
		$table_name    = $wpdb->prefix . $this->table_for_uploaded_file;
		$data = array(
			$this->postid_userid => $this->postid_userid_num ,
			'Labname' => $labname ,
			'CPTtype' => $posttype ,
			'joystick' => $joystick ,
			'timestamp' => $timestamp ,
			'microtimestamp' => $micro ,
			'filename' => $filename ,
			'year' => $Year ,
		);
		$data_format   = array( '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' );
		if ( !$wpdb->delete( $table_name , $data , $data_format ) ) {
			return array( 'error' => true , 'errmessage' => "$filename has been deleted, but $filename record in database cannot be deleted due to an error." );
		} else {
			return array( 'success' => true , 'data' => "$filename has been deleted." , $this->postid_userid => $this->postid_userid_num ); 
		}
	}

    private function UPLOAD_FILES_postid_userid_check() {

        // author check
        $current_USERID = get_current_user_id();
        if ( $this->postid_userid === 'postid' ) {
            $author_ID = get_post($this->postid_userid_num)->post_author;
            if ( intval($author_ID) !== intval($current_USERID) ) {
                if (!empty($author_ID) && !empty($current_USERID)) {
                    $user_name = get_user_meta( $author_ID , 'last_name' , true ) . get_user_meta( $author_ID , 'first_name' , true );
                    return array( 'error' => true , 'errmessage' => "必須為本篇作者 {$user_name} 才可以進行上傳。" );
                }
            }
        } else if ( $this->postid_userid === 'userid' ) {
            if ( intval($this->postid_userid_num) !== intval($current_USERID) ) {
                if (!empty($this->postid_userid_num) && !empty($current_USERID)) {
                    $user_name = get_user_meta( $author_ID , 'last_name' , true ) . get_user_meta( $author_ID , 'first_name' , true );
                    return array( 'error' => true , 'errmessage' => "必須為本篇作者 {$user_name} 才可以進行上傳。" );
                }
            }
        }

        return TRUE;
    }



	/**
	 * Function for transfer timestamp to string form
	 *
	 */
	public function YANN_NTNUSMIL_timestamp_to_readable_form( $timestamp, $type='read' ) {
		if ( empty($timestamp) ) return "";
		if ( $type === 'read' ) return date('Y/m/d (D) A h:i', $timestamp);
		if ( $type === 'save' ) return date('d-m-Y h:i', $timestamp);
	}


	/**
	 * Function for transfer string form to timestamp
	 *
	 */
	public function YANN_NTNUSMIL_readable_form_to_timestamp( $strtime ) {
		// time for rerank
		$_splittime = explode(" ", $strtime);
		$_daymonyear = array_map('intval', explode('-', $_splittime[0]));
		$_minuteshours = array_map('intval', explode(':', $_splittime[1]));
		
		// Create timestamp with selected time for today date
		$timestamp = mktime($_minuteshours[0], $_minuteshours[1], 0, $_daymonyear[1], $_daymonyear[0], $_daymonyear[2]);
		return $timestamp;
	}


}
