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
												'fileupload'
											)
										)
									) {
										throw new Exception("Value of key {$a_m_b} should only has 'input', 'monoselect', 'fileupload' three options!");
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
		if ($this->CPT_SAVE_DELETE_save_validation( $post_id, $_post, $_request, $box_input_args )) {
			// Its safe for us to save the data !
			update_post_meta( $post_id , $post_meta_key , wc_clean( wp_unslash( $_post[ $post_meta_key ] ) ) );
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
		if ($this->CPT_SAVE_DELETE_save_validation( $post_id, $_post, $_request, $box_input_args )) {
			// Its safe for us to save the data !
			if( isset( $_post[ $id ] ) ) {
				update_post_meta( $post_id, $post_meta_key, sanitize_text_field( $_post[ $id ] ) );
			} else {
				delete_post_meta( $post_id, $post_meta_key );
			}
			return $post_id;
		}
	}
}
