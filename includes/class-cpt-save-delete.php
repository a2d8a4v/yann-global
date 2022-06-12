<?php
/**
 * Class: CPT_SAVE_DELETE
 * Custom for global settings.
 */

class CPT_SAVE_DELETE {

	private $errors;
	private $labname;
	public $params;

	/**
	 * This plugin's instance.
	 *
	 * @var CPT_SAVE_DELETE
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register($run, $params) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new CPT_SAVE_DELETE($params);
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

		// validation
		$this->CPT_SAVE_DELETE_params_validation($params);
		// assign
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}

		// run hooks
		$this->CPT_SAVE_DELETE_preload();
	}

	private function CPT_SAVE_DELETE_params_validation( $cpt_metaboxes_array ) {

		// first is the $cpt_initialization_array
		if (empty($cpt_metaboxes_array)) {
			throw new Exception("Arguments shouldn't be empty!");
		}

		foreach($cpt_metaboxes_array as $k => $v) {
			if (! in_array($k,
					array(
						'posttype',
						'added_meta_box',
						'joystick_types_arr',
						'table_for_uploaded_file'
					)
				)
			) {
				throw new Exception("Key {$k} is not ready!");
			}

			if ($k === 'posttype') {
				if (! is_string($v)) {
					throw new Exception("Value of key {$k} should be a string type!");
				}
			}
			if ($k === 'joystick_types_arr') {
				if (! is_array($v)) {
					throw new Exception("Value of key {$k} should be an array!");
				}
				foreach($v as $j_t_a_v) {
					if (! is_string($j_t_a_v)) {
						throw new Exception("Value of key {$k} should be a array with items of string type!");
					}
				}
			}
			if ($k === 'table_for_uploaded_file') {
				if (! is_string($v)) {
					throw new Exception("Value of key {$k} should be a string type!");
				}
			}
			if ($k === 'added_meta_box') {

				if (! is_array($v)) {
					throw new Exception("Value of key {$k} should be an array");
				}

				for ($x=0; $x<count($v); $x++) {

					$item = $v[$x];

					foreach($item as $a_m_b => $a_m_b_v) {
						if (! in_array($a_m_b,
								array(
									'id',
									'box_input_type',
									'box_input_args',
								)
							)
						) {
							throw new Exception("Key {$a_m_b} in {$k} is not ready!");
						}
						if (in_array($a_m_b,
								array(
									'id',
									'box_input_type',
								)
							)
						) {
							if (! is_string($a_m_b_v)) {
								throw new Exception("Value of key {$a_m_b} should be a string");
							}
							if ($a_m_b === 'box_input_type') {
								if (! in_array(
										$a_m_b_v,
										array(
											'input',
											'monoselect',
											'multiselect',
											'fileupload'
										)
									)
								) {
									throw new Exception("Value of key {$a_m_b} should only has 'input', 'monoselect', 'multiselect', 'fileupload' 4 options!");
								}
							}
						}
						if ($a_m_b === 'box_input_args') {

							if (! is_array($a_m_b_v)) {
								throw new Exception("Value of key {$a_m_b} should be an array");
							}

							switch ($item['box_input_type']) {
								case ('input'):
									foreach ($a_m_b_v as $b_i_a => $b_i_a_v) {
										if (! in_array(
												$b_i_a, array(
													'post_meta_key',
												)
											)
										) {
											throw new Exception("Key {$b_i_a} is not ready for input type!");
										}
										if (in_array(
												$b_i_a, 
												array(
													'post_meta_key',
												)
											)
										) {
											if (! is_string($b_i_a_v)) {
												throw new Exception("Value of key {$b_i_a} should be a string");
											}
										}
									}

									break;

								case ('monoselect'):
									foreach ($a_m_b_v as $b_i_a => $b_i_a_v) {
										if (! in_array(
											$b_i_a, array(
												'post_meta_key',
											)
										)
									) {
										throw new Exception("Key {$b_i_a} is not ready for input type!");
									}
									if (in_array(
											$b_i_a, 
											array(
												'post_meta_key',
											)
										)
									) {
										if (! is_string($b_i_a_v)) {
											throw new Exception("Value of key {$b_i_a} should be a string");
										}
									}
									}
									break;

								case ('fileupload'):
									break;

								case ('multiselect'):
									foreach ($a_m_b_v as $b_i_a => $b_i_a_v) {
										if (! in_array(
											$b_i_a, array(
												'post_meta_key',
												'is_author_validation'
											)
										)
									) {
										throw new Exception("Key {$b_i_a} is not ready for input type!");
									}
									if (in_array(
											$b_i_a, 
											array(
												'post_meta_key',
												'is_author_validation'
											)
										)
									) {
										if (! is_string($b_i_a_v)) {
											throw new Exception("Value of key {$b_i_a} should be a string");
										}
									}
									}

									break;
							}
						}
					}
				}
			}
		}
	}

	private function CPT_SAVE_DELETE_preload() {

		// save function for customized input
		add_action( 'save_post', array( $this, 'CPT_SAVE_DELETE_save_for_input' ), 10, 1 );

		// @https://wordpress.stackexchange.com/questions/10678/function-to-execute-when-a-post-is-moved-to-trash
		// @https://wordpress.stackexchange.com/questions/91049/hook-on-trash-post/91052
		// fired after post moved to trash state
		add_action( 'wp_trash_post' , array( $this,'CPT_SAVE_DELETE_trash_multiple_posts' ) , 10 , 1 );
		add_action( 'before_delete_post' , array( $this, 'CPT_SAVE_DELETE_delete_post' ) , 10 , 1 );
		add_action( 'wp_scheduled_auto_draft_delete', array( $this, 'CPT_SAVE_DELETE_delete_autodraft_post' ) , 10, 1 );

	}

	/**
	 * Save function of customized inputs for CPTs
	 * 
	 * @param object $post_id, post id of post
	 */
	public function CPT_SAVE_DELETE_save_for_input( $post_id ) {

		for ($x=0; $x<count($this->added_meta_box); $x++) {

			// args for metabox
			$metabox_args = $this->added_meta_box[$x];

			// items
			$id = $metabox_args['id'];
			$type = $metabox_args['box_input_type'];
			$func = "CPT_SAVE_DELETE_save_for_type_{$type}";

			// callback args
			$box_input_args = $metabox_args['box_input_args'];
			$box_input_args['id'] = $id;

			// back-end variables
			$_post = $_POST;
			$_request = $_REQUEST;

			// run the function
			$this->$func( $post_id, $_post, $_request, $box_input_args );

		}
	}

	/**
	 * Function for 'CPT_SAVE_DELETE_save_for_type_input'
	 * 
	 */
	private function CPT_SAVE_DELETE_save_validation( $post_id, $_post, $_request, $box_input_args ) {

		$id = $box_input_args['id'];

		if ( ! isset( $_post[ "{$id}_nonce" ] ) ) {
			return $post_id;
		}
		$nonce = $_request[ "{$id}_nonce" ];
		if ( ! wp_verify_nonce( $nonce ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( $this->posttype == $_post[ 'post_type' ] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		if (isset($box_input_args['is_author_validation']) && $box_input_args['is_author_validation'] === True) {
			
			// author validation
			$new_CPT_MESSAGES = new CPT_MESSAGES(
				array(
					'posttype' => $this->posttype,
					'post_obj' => get_post($post_id),
					'msg_type' => 'error',
					'action' => 'editing',
					'act_for_type' => 'save_type_multiselect',
					'action_msg' => array(
						'non_author' => "{$this->posttype}-%s 必須為本篇作者 %s 才可以進行編輯。"
					),
					'post_meta_key' => $box_input_args['post_meta_key'],
					'func_append' => array(),
					'transient_label' => 'non_author',
					'time_keep' => 45,
					'redirect' => array()
				)
			);

			return $new_CPT_MESSAGES->return_result;

		} else {

			// normal saving
			$new_CPT_MESSAGES = new CPT_MESSAGES(
				array(
					'posttype' => $this->posttype,
					'post_obj' => get_post($post_id),
					'msg_type' => 'error',
					'action' => 'editing',
					'act_for_type' => 'save_type_input',
					'action_msg' => array(
						'non_author' => "{$this->posttype}-%s 必須為本篇作者 %s 才可以進行編輯。"
					),
					'func_append' => array(),
					'transient_label' => 'non_author',
					'time_keep' => 45,
					'redirect' => array()
				)
			);

			return $new_CPT_MESSAGES->return_result;

		}

		return TRUE;
	}

	/**
	 * Save function of customized inputs for type input in CPTs
	 * 
	 * @param object $post_id, $_post, $_request, $box_input_args
	 */
	private function CPT_SAVE_DELETE_save_for_type_input( $post_id, $_post, $_request, $box_input_args ) {

		// variables
		$id = $box_input_args['id'];
		$post_meta_key = $box_input_args['post_meta_key'];

		// validation
		if ($validation = $this->CPT_SAVE_DELETE_save_validation( $post_id, $_post, $_request, $box_input_args )) {

			if (is_wp_error($validation)) {
				if ($validation->has_errors()) {
					return $post_id;
				}
			}

			// Its safe for us to save the data !
			if( isset( $_post[ $id ] ) ) {
				update_post_meta( $post_id, $post_meta_key, sanitize_text_field( $_post[ $id ] ) );
			} else {
				delete_post_meta( $post_id, $post_meta_key );
			}
			return $post_id;
		}
	}

	/**
	 * Save function of customized inputs for type monoselect in CPTs
	 * 
	 * @param object $post_id, $_post, $_request, $box_input_args
	 */
	private function CPT_SAVE_DELETE_save_for_type_monoselect( $post_id, $_post, $_request, $box_input_args ) {

		// variables
		$id = $box_input_args['id'];
		$post_meta_key = $box_input_args['post_meta_key'];

		// validation
		if ($validation = $this->CPT_SAVE_DELETE_save_validation( $post_id, $_post, $_request, $box_input_args )) {

			if (is_wp_error($validation)) {
				if ($validation->has_errors()) {
					return $post_id;
				}
			}

			// Its safe for us to save the data !
			if( isset( $_post[ $id ] ) ) {
				update_post_meta( $post_id, $post_meta_key, sanitize_text_field( $_post[ $id ] ) );
			} else {
				delete_post_meta( $post_id, $post_meta_key );
			}
			return $post_id;
		}
	}

	/**
	 * Save function of customized inputs for type fileupload in CPTs
	 * 
	 * @param object $post_id, $_post, $_request, $box_input_args
	 */
	private function CPT_SAVE_DELETE_save_for_type_fileupload( $post_id, $_post, $_request, $box_input_args ) {
		return;
	}


	/**
	 * Save function of customized inputs for type fileupload in CPTs
	 * 
	 * @param object $post_id, $_post, $_request, $box_input_args
	 */
	private function CPT_SAVE_DELETE_save_for_type_multiselect( $post_id, $_post, $_request, $box_input_args ) {

		// variables
		$id = $box_input_args['id'];
		$post_meta_key = $box_input_args['post_meta_key'];

		// validation
		if ($validation = $this->CPT_SAVE_DELETE_save_validation( $post_id, $_post, $_request, $box_input_args )) {

			if (is_wp_error($validation)) {
				if ($validation->has_errors()) {
					return $post_id;
				}
			}

			// Its safe for us to save the data !
			if( isset( $_post[ $id ] ) ) {
				update_post_meta( $post_id, $post_meta_key, sanitize_text_field( $_post[ $id ] ) );
			} else {
				delete_post_meta( $post_id, $post_meta_key );
			}
			return $post_id;
		}
	}

	/**
	 * stop move to trash if is not author
	 * 
	 * @param object $post_id
	 */
	public function CPT_SAVE_DELETE_trash_multiple_posts( $post_id ) {

		if ( $_REQUEST['post_type'] !== $this->posttype ) {
			return;
		}

		if ( isset( $_GET['post'] ) && is_array( $_GET['post'] ) ) {

			$new_CPT_MESSAGES = new CPT_MESSAGES(
				array(
					'posttype' => $this->posttype,
					'get_ojb'  => $_GET,
					'msg_type' => 'error',
					'action' => 'deleting',
					'act_for_type' => 'multiple_posts',
					'action_msg' => array(
						'non_author' => "{$this->posttype}-%s 必須為本篇作者 %s 才可以進行刪除。"
					),
					'func_append' => array(),
					'transient_label' => 'non_author',
					'time_keep' => 45,
					'redirect' => array(
						'query' => array(
							'post_type' => $this->posttype,
							'post_status' => 'trash'
						),
						'url' => admin_url( 'edit.php' )
					)
				)
			);

		} else {

			$new_CPT_MESSAGES = new CPT_MESSAGES(
				array(
					'posttype' => $this->posttype,
					'post_obj' => get_post($post_id),
					'msg_type' => 'error',
					'action' => 'deleting',
					'act_for_type' => 'single_post',
					'action_msg' => array(
						'non_author' => "{$this->posttype}-%s 必須為本篇作者 %s 才可以進行刪除。"
					),
					'func_append' => array(),
					'transient_label' => 'non_author',
					'time_keep' => 45,
					'redirect' => array()
				)
			);

		}
	}

	/**
	 * stop delete if is not author
	 * 
	 * @param object $post_id, post id of post
	 */
	public function CPT_SAVE_DELETE_delete_post( $post_id ) {

		$post = get_post($post_id);
		// Should be this CPT meeting post author, otherwise deny
		if ( $post->post_type !== $this->posttype ) {
			return;
		}
		
		$new_CPT_MESSAGES = new CPT_MESSAGES(
			array(
				'posttype' => $this->posttype,
				'post_obj' => $post,
				'msg_type' => 'error',
				'action' => 'deleting',
				'act_for_type' => 'single_post',
				'action_msg' => array(
					'non_author' => "{$this->posttype}-%s 必須為本篇作者 %s 才可以進行刪除。"
				),
				'func_append' => array(
					array($this, 'CPT_SAVE_DELETE_move_to_trash_sql')
				),
				'transient_label' => 'non_author',
				'time_keep' => 45,
				'redirect' => array(
					'query' => array(
						'post_type' => $this->posttype,
						'post_status' => 'trash'
					),
					'url' => admin_url( 'edit.php' )
				)
			)
		);

		// Delete the static files
		$this->CPT_SAVE_DELETE_deleteUploadedFiles( $post_id );

		// Deletion will start
	}	


	public function CPT_SAVE_DELETE_move_to_trash_sql( $postid ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'posts';

		$wpdb->update(
			$table_name,
			array(
				'post_status' => 'trash'
			),
			array(
				'id' => $postid
			)
		);

	}

	/**
	 * stop delete if is not author
	 * 
	 * @param object $wp_delete_auto_drafts, $post in array
	 */
	public function CPT_SAVE_DELETE_delete_autodraft_post( $wp_delete_auto_drafts ) {

		foreach( $wp_delete_auto_drafts as $del_post ) {
			if ( $del_post->post_type !== $this->posttype ) {
				continue;
			}

			// Delete the static files
			$this->CPT_SAVE_DELETE_deleteUploadedFiles( $del_post->ID );
		}
	}

	/**
	 * Remove uploaded files
	 * 
	 * @param object $post_id
	 */
	private function CPT_SAVE_DELETE_deleteUploadedFiles( $post_id ) {
		global $wpdb;
		$posttype  = $this->posttype;
		$joysticks = $this->joystick_types_arr;
		$labname   = $this->labname;

		foreach ($joysticks as $joystick) {

			// has file check
			$table_name = $wpdb->prefix . $this->table_for_uploaded_file;
			$DBsearch   = $wpdb->get_results ( "
			SELECT * 
			FROM  $table_name
				WHERE postid = '".$post_id."'
				AND CPTtype = '".$posttype."'
				AND joystick = '".$joystick."'
			" );

			// Maybe multiple files
			foreach($DBsearch as $fileinfo) {

				$Year      = $fileinfo->year;
				$micro     = $fileinfo->microtimestamp;
				$filename  = $fileinfo->filename;
				$timestamp = $fileinfo->timestamp;
				// Upload dir path
				if (!isset($this->upload_dir)) {
					$this->upload_dir = '/CPT/' . $posttype . "/" . $joystick . "/" . $Year;
				}
				$dir = wp_upload_dir()['basedir'] . $this->upload_dir;

				// Remove db record
				$data = array(
					'postid' => $post_id ,
					'Labname' => $labname ,
					'CPTtype' => $posttype ,
					'joystick' => $joystick ,
					'timestamp' => $timestamp ,
					'microtimestamp' => $micro ,
					'filename' => $filename ,
					'year' => $Year,
				);
				$data_format = array( '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' , '%s' );
				$do_delete   = $wpdb->delete( $table_name , $data , $data_format );

				// Delete files and folder
				$this->CPT_SAVE_DELETE_rmDirRecursive( $dir . "/" . $post_id );
				// If the Year level folder is also empty, just delete it
				if ( $this->CPT_SAVE_DELETE_is_dir_empty( $dir ) ) {
					rmdir($dir);
				}
				$this->upload_dir = "";
			}
		}
	}

	/**
	 * Remove all files and the folder
	 * 
	 * @param object $dir, dir absolute path
	 */
	private function CPT_SAVE_DELETE_rmDirRecursive( $dir ) {
		$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($it,
					 RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) {
			if ($file->isDir()){
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		rmdir($dir);
	}

	/**
	 * Check is empty folder
	 * 
	 * @param object $post_id, post id of post
	 */
	private function CPT_SAVE_DELETE_is_dir_empty($dir) {
		$handle = opendir($dir);
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				closedir($handle);
				return false;
			}
		}
		closedir($handle);
		return true;
	}


}
