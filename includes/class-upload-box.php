<?php
/**
 * Class: UPLOAD_BOX
 * Custom for NTNU SMIL LAB Meeting components.
 * 
 */

class UPLOAD_BOX {

	private $errors;
	private $labname;
	public $params;
	
	public $run;
	public $_post;
	public $joystick;
	public $posttype;
	public $postid_userid;
	public $joystick_types_arr;
	public $table_for_uploaded_file;

	/**
	 * This plugin's instance.
	 *
	 * @var UPLOAD_BOX
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register($run, $params) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new UPLOAD_BOX($params);
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

		$this->year = "";
		$this->joystick = "";

		// validation
		foreach($params as $k => $v) {
			if ( in_array($k,
					// 'upload_dir' is unnecessary
					array(
						'_post',
						'posttype',
						'joystick',
						'postid_userid',
						'joystick_types_arr',
						'table_for_uploaded_file',
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
		}
		
		// assign
		foreach ($params as $k => $v) {
			$this->$k = $v;
        }

	}


	/**
	 * Input Metabox for CPTs - file upload
	 * 
	 * :See @https://rudrastyh.com/wordpress/meta-boxes.html
	 * :See @https://css-tricks.com/drag-and-drop-file-uploading/
	 * 
	 */
	public function UPLOAD_BOX_input_for_files_upload() {

		if ( in_array($this->joystick, array('paper', 'ppt', 'publications') ) ) {
			global $post;
			$postid_userid_num = $post->ID;
		} else if ( in_array($this->joystick, array('thesis') ) ) {
			$postid_userid_num = get_current_user_id();
		}
		
		// variable initialize
		$key = 0;

		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_for_uploaded_file;
		$result = $wpdb->get_results ( "
			SELECT * 
			FROM  $table_name
				WHERE ".$this->postid_userid." = '".$postid_userid_num."'
				AND joystick = '".$this->joystick."'
		" );
		
		echo '<table class="form-table" id="insert-success-' . $this->joystick . '"><tbody>';
		if ( !empty( $result ) ) {
			foreach ( $result as $file ) {
				$key += 1;
				$showkey = sprintf("%02d", $key);
				$file_name = $file->filename;
				$Microtime = $file->microtimestamp;
				echo '<tr id="uploaded-' . $this->joystick . '-row-' . $showkey . '" filename="' . $file_name . '" '.$this->postid_userid.'="' . $postid_userid_num . '"><th><label for="uploaded-' . $this->joystick . '-' . $showkey . '">' . $file_name . '</label></th><td><input type="button" download="'.$postid_userid_num.'-'.$Microtime.'-'.$this->joystick.'" id="uploaded-' . $this->joystick . '-download-' . $showkey . '" class="button" name="uploaded-' . $this->joystick . '-download-' . $showkey . '" value="下載"></td><td><input type="button" id="uploaded-' . $this->joystick . '-delete-' . $showkey . '" class="button" name="uploaded-' . $this->joystick . '-delete-' . $showkey . '" value="刪除"></td></tr>';
			}
		}
		echo '</tbody></table>';

		if ( empty( $result ) ) {
			echo $this->UPLOAD_BOX_html_form_content( $this->joystick , $postid_userid_num );
		}
	}



	/**
	 * Input Metabox for CPTs - submit form content
	 * 
	 */
	// @https://rudrastyh.com/wordpress/meta-boxes.html
	// @https://css-tricks.com/drag-and-drop-file-uploading/
	private function UPLOAD_BOX_html_form_content( $joystick , $postid_userid_num ) {
		return '<div enctype="multipart/form-data" novalidate class="box" id="upload-box-' . $joystick . '">
		<div class="box__uploadsize" max_upload_size="' . wp_max_upload_size() . '"></div>
		<div class="box__input">
		<svg class="box__icon" xmlns="http://www.w3.org/2000/svg" width="50" height="43" viewBox="0 0 50 43"><path d="M48.4 26.5c-.9 0-1.7.7-1.7 1.7v11.6h-43.3v-11.6c0-.9-.7-1.7-1.7-1.7s-1.7.7-1.7 1.7v13.2c0 .9.7 1.7 1.7 1.7h46.7c.9 0 1.7-.7 1.7-1.7v-13.2c0-1-.7-1.7-1.7-1.7zm-24.5 6.1c.3.3.8.5 1.2.5.4 0 .9-.2 1.2-.5l10-11.6c.7-.7.7-1.7 0-2.4s-1.7-.7-2.4 0l-7.1 8.3v-25.3c0-.9-.7-1.7-1.7-1.7s-1.7.7-1.7 1.7v25.3l-7.1-8.3c-.7-.7-1.7-.7-2.4 0s-.7 1.7 0 2.4l10 11.6z" /></svg>
		<label for="CPT_' . $this->posttype . '_input_for_' . $joystick . '_upload"><strong>選擇檔案</strong><span class="box__dragndrop"> 或直接拖曳檔案於此</span>。</label>
		<input type="file" name="files[]" id="CPT_' . $this->posttype . '_input_for_' . $joystick . '_upload" class="box__file" data-multiple-caption="{count} files selected" multiple />
		<input hidden type="text" name="joystick" joystick="' . $joystick . '">
		<input hidden type="text" name="' . $this->postid_userid . '" ' . $this->postid_userid . '="' . $postid_userid_num . '">
		<button type="submit" class="box__button">Upload</button>
		</div>
		<div class="box__uploading">上傳中&hellip;</div>
		<div class="box__success">上傳成功！</div>
		<div class="box__error">上傳失敗！<span></span><br/><a href="#" class="box__restart" role="button">再試一次！</a></div>
		</div>
		<p class="upload-description-' . $joystick . '"><strong>Be sure to try the demo on a browser (e.g. IE 9 and below) that does not support drag&amp;drop file upload. You can also try with a JavaScript support disabled.</strong></p>';
	}




	public function UPLOAD_BOX_handle_html_form_output_validation() {

		$POST = $this->_post;

		$nonce = ( isset( $POST[ 'nonce' ] ) ) ? wc_clean( wp_unslash( $POST[ 'nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce , 'YANN-ajax-nonce' ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}
		if ( ! ( is_array($POST) && defined('DOING_AJAX') && DOING_AJAX ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}
		if ( ! isset($POST['joystick']) || ( isset($POST['joystick']) && !in_array( $POST['joystick'] , $this->joystick_types_arr ) ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Do not change the joystick attributes!' ) );
			wp_die();
		}
		if ( ! isset($POST[$this->postid_userid]) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'No postid' ) );
			wp_die();
		}

		return TRUE;
	}

	private function UPLOAD_BOX_return_status_check( $status ) {
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


	public function UPLOAD_BOX_html_form_handle_reply() {
		$status = $this->UPLOAD_BOX_html_form_call();
		$this->UPLOAD_BOX_return_status_check($status);
	}

	/**
	 * Ajax call for YANN_NTNUSMIL_CPT_meeting_outputform_handle function
	 *
	 */
	private function UPLOAD_BOX_html_form_call() {
		$POST = $this->_post;
		$joystick = $POST['joystick'];
		$postid_userid_num = $POST[$this->postid_userid];
		if ( $rtn = $this->UPLOAD_BOX_html_form_content( $joystick , $postid_userid_num ) ) {
			return array( 'success' => true , 'data' => $rtn );
		} else {
			return array( 'error' => true , 'errmessage' => '伺服器哪裡怪怪的，請聯繫網站管理員' );
		}
	}

}
