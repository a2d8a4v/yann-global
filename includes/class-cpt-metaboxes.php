<?php
/**
 * Class: CPT_METABOXES
 * Custom for global settings.
 */

class CPT_METABOXES {

	private $errors;
	private $labname;
	public $params;

	/**
	 * This plugin's instance.
	 *
	 * @var CPT_METABOXES
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register($run, $params) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new CPT_METABOXES($params);
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
		$this->CPT_METABOXES_params_validation($params);
		// assign
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}

		// run hooks
		$this->CPT_METABOXES_preload();
	}

	private function CPT_METABOXES_params_validation( $cpt_metaboxes_array ) {

		// first is the $cpt_initialization_array
		if (empty($cpt_metaboxes_array)) {
			throw new Exception("Arguments shouldn't be empty!");
		}

		foreach($cpt_metaboxes_array as $k => $v) {
			if (! in_array($k,
					array(
						'posttype',
						'add_meta_box',
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
			if ($k === 'add_meta_box') {
				if (! is_array($v)) {
					throw new Exception("Value of key {$k} should be an array");
				}

				foreach($v as $a_m_b => $a_m_b_v) {
					if (! in_array($a_m_b,
							array(
								'box_title',
								'id',
								'position',
								'priority',
								'box_input_type',
								'box_input_args',
							)
						)
					) {
						throw new Exception("Key {$a_m_b} in {$k} is not ready!");
					}
					if (in_array($a_m_b,
							array(
								'box_title',
								'id',
								'position',
								'priority',
								'box_input_type',
							)
						)
					) {
						if (! is_string($a_m_b_v)) {
							throw new Exception("Value of key {$a_m_b} should be a string");
						}
						if ($a_m_b === 'box_input_type') {
							if (! in_array(
									$a_m_b,
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
					if (in_array($a_m_b,
							array(
								'box_input_args'
							)
						)
					) {
						if (! is_array($a_m_b_v)) {
							throw new Exception("Value of key {$a_m_b} should be an array");
						}

						switch ($v['box_input_type']) {
							case ('input'):
								foreach ($a_m_b_v as $b_i_a => $b_i_a_v) {
									if (! in_array(
											$b_i_a, array(
												'input_title',
												'type',
												'class',
												'autocomplete',
												'placeholder',
												'post_meta_key',
												'invalid_feedbacks'
											)
										)
									) {
										throw new Exception("Key {$b_i_a} is not ready for input type!");
									}
									if (in_array(
											$b_i_a, 
											array(
												'invalid_feedbacks',
											)
										)
									) {
										if (! is_array($b_i_a_v)) {
											throw new Exception("Value of key {$b_i_a} should be an array");
										}
									}
									if (in_array(
											$b_i_a, 
											array(
												'input_title',
												'type',
												'class',
												'placeholder',
												'post_meta_key',
											)
										)
									) {
										if (! is_array($b_i_a_v)) {
											throw new Exception("Value of key {$b_i_a} should be a string");
										}
									}
									if (in_array(
											$b_i_a, 
											array(
												'autocomplete',
											)
										)
									) {
										if (! is_bool($b_i_a_v)) {
											throw new Exception("Value of key {$b_i_a} should be a boolean");
										}
									}
								}

								break;

							case ('monoselect'):
								foreach ($a_m_b_v as $b_i_a => $b_i_a_v) {
									if (! in_array(
											$b_i_a, array(
												'input_title',
												'post_meta_key',
												'options',
												'invalid_feedbacks'
											)
										)
									) {
										throw new Exception("Key {$b_i_a} is not ready for monoselect type!");
									}
									if (in_array(
											$b_i_a, 
											array(
												'options',
												'invalid_feedbacks'
											)
										)
									) {
										if (! is_array($b_i_a_v)) {
											throw new Exception("Value of key {$b_i_a} should be an array");
										}
									}

									if (in_array(
											$b_i_a, 
											array(
												'input_title',
												'post_meta_key'
											)
										)
									) {
										if (! is_array($b_i_a_v)) {
											throw new Exception("Value of key {$b_i_a} should be a string");
										}
									}
								}
								break;

							case ('fileupload'):
								foreach ($a_m_b_v as $b_i_a => $b_i_a_v) {
									if (! in_array(
											$b_i_a, array(
												'object_init_args'
											)
										)
									) {
										throw new Exception("Key {$b_i_a} is not ready for fileupload type!");
									}
									if (! is_array($b_i_a_v)) {
										throw new Exception("Value of key {$b_i_a} should be an array");
									}
								}
								break;
						} 

					}
				}


			}
		}

	}

	private function CPT_METABOXES_preload() {

		// register metabox
        add_action( 'add_meta_boxes', array( $this, 'CPT_METABOXES_add_meta_boxes'  ), 10, 0 );

	}

	/**
	 * Input Metabox for CPTs, input type
	 * 
	 */
	public function CPT_METABOXES_add_meta_boxes() {

		for ($x=0; $x<count($this->add_meta_box); $x++) {

			// args for metabox
			$metabox_args = $this->add_meta_box[$x];

			// items
			$id = $metabox_args['id'];
			$type = $metabox_args['box_input_type'];
			$func = array( $this, "CPT_METABOXES_type_{$type}_html_content" );
			$position = $metabox_args['position'];
			$priority = $metabox_args['priority'];
			$box_title = $metabox_args['box_title'];

			// callback args
			$box_input_args = $metabox_args['box_input_args'];
			$box_input_args['id'] = $id;

			switch ( $type ) {

				case ( 'input' ):
					add_meta_box(
						"{$id}-box",
						$box_title,
						$func,
						$this->posttype,
						$position,
						$priority,
						$box_input_args
					);
					break;

				case ( 'monoselect' ):
					add_meta_box(
						"{$id}-box",
						$box_title,
						$func,
						$this->posttype,
						$position,
						$priority,
						$box_input_args
					);
					break;

				case ( 'fileupload' ):
					add_meta_box(
						"{$id}-box",
						$box_title,
						$func,
						$this->posttype,
						$position,
						$priority,
						$box_input_args
					);
					break;
			}

		}
	}

	/**
	 * Input Metabox for CPTs - input
	 * 
	 */
	public function CPT_METABOXES_type_input_html_content( $post, $callback_args ) {

		// variables
		global $post;
		$box_input_args = $callback_args['args'];
		$id = $box_input_args['id'];
		$input_title = $box_input_args['input_title'];
		$input_type = $box_input_args['type'];
		$input_class = $box_input_args['class'];
		$autocomplete = $box_input_args['autocomplete']?'on':'off';
		$placeholder = $box_input_args['placeholder'];
		$post_meta_key = $box_input_args['post_meta_key'];
		$invalid_feedbacks = $box_input_args['invalid_feedbacks'];

		// metas
		$meta_get = get_post_meta( $post->ID , $post_meta_key , true );
		$meta_field_data = $meta_get ? $meta_get : '';
		$input_placeholder = empty($meta_field_data) ? $placeholder : $meta_field_data;

		// content
		echo '<input hidden type="text" name="'.$id.'_nonce" value="' . wp_create_nonce() . '">
				<table class="form-table">
					<tbody>
						<tr>
							<th><label for="'.$id.'">'.$input_title.'</label></th>
							<td>
								<div>';
		echo '<input type="'.$input_type.'" autocomplete="'.$autocomplete.'" id="'.$id.'" class="'.$input_class.'" name="'.$id.'" placeholder="'.$input_placeholder.'" value="'.$meta_field_data.'"></p>';
		foreach($invalid_feedbacks as $invalid_feedback) {
			echo '<div class="invalid-feedback">'.$invalid_feedback.'</div>';
		}
		echo '</div></td></tr></tbody></table>';
	}

	/**
	 * Input Metabox for CPTs - monoselect
	 * 
	 * :See @https://rudrastyh.com/wordpress/meta-boxes.html
	 * 
	 */
	public function CPT_METABOXES_type_monoselect_html_content( $post, $callback_args ) {

		// variables
		global $post;
		$box_input_args = $callback_args['args'];
		$id = $box_input_args['id'];
		$input_title = $box_input_args['input_title'];
		$post_meta_key = $box_input_args['post_meta_key'];
		$options = $box_input_args['options'];
		$invalid_feedbacks = $box_input_args['invalid_feedbacks'];

		// metas
		$meta_get = get_post_meta( $post->ID , $post_meta_key , true );
		$meta_field_data = $meta_get ? $meta_get : '';

		// content
		echo '<input hidden type="text" name="'.$id.'_nonce" value="' . wp_create_nonce() . '">';
		echo '<table class="form-table">
				<tbody>
					<tr>
						<th><label for="'.$id.'">'.$input_title.'</label></th>
						<td><div>
							<select id="'.$id.'" name="'.$id.'">';
		// do not add 'disabled' 'selected' 'hidden' 3 attributes here
		echo '<option value="">請選擇一個項目</option>';
		foreach($options as $key => $val) {
			echo '<option value="'.$key.'"' . selected( $key, $meta_field_data, false ) . '>'.$val.'</option>';
		}
		echo '</select>';
		foreach($invalid_feedbacks as $invalid_feedback) {
			echo '<div class="invalid-feedback">'.$invalid_feedback.'</div>';
		}
		echo '</div></td></tr></tbody></table>';
	}

	/**
	 * Input Metabox for CPTs - multiselect
	 * 
	 * :See @https://rudrastyh.com/wordpress/meta-boxes.html
	 * :See @https://rudrastyh.com/wordpress/select2-for-metaboxes-with-ajax.html
	 * :See @https://stackoverflow.com/questions/34131623/wp-user-query-from-meta-key-and-orderby-secondary-meta-key
	 * 
	 */
	public function CPT_METABOXES_type_multiselect_html_content() {

		// variables
		global $post;
		$box_input_args = $callback_args['args'];
		$id = $box_input_args['id'];
		$input_title = $box_input_args['input_title'];
		$post_meta_key = $box_input_args['post_meta_key'];
		$search_type = $box_input_args['search_type'];
		$search_type_args = $box_input_args['search_type_args'];
		$invalid_feedbacks = $box_input_args['invalid_feedbacks'];
	
		// html init
		$html = "";
	
		// add html
		$html .= '<input hidden type="text" name="'.$id.'_nonce" value="' . wp_create_nonce() . '">';
		$html .= '<table class="form-table"><tbody><tr><th><label for="'.$id.'">'.$input_title.'</label></th><td><div><select id="'.$id.'" name="'.$id.'[]" multiple="multiple" style="width:99%;max-width:25em;">';
	
		switch ($search_type) {
			case ( 'user' ):
				// metas
				$meta_get = get_post_meta( $post->ID , $post_meta_key , true );
	
				if ( $authors = get_users( $search_type_args ) ) {
					foreach( $authors as $author ) {
						$name = $author->last_name.$author->first_name." (".$author->nickname.")";
						$name = ( mb_strlen( $name ) > 20 ) ? mb_substr( $name, 0, 19 ) . '...' : $name;
						$selected = ( is_array( $meta_get ) && in_array( $author->ID, $meta_get ) ) ? ' selected="selected"' : '';
						$html .= '<option value="' . $author->ID . '" '.$selected.'>' . $name . '</option>';
					}
				}
				if ( $post->post_status == 'auto-draft' && empty($meta_get) ) {
					$author = wp_get_current_user();
					$name   = $author->last_name.$author->first_name." (".$author->nickname.")";
					$name   = ( mb_strlen( $name ) > 20 ) ? mb_substr( $name, 0, 19 ) . '...' : $name;
					$html  .= '<option value="' . $author->ID . '" selected="selected">' . $name . '</option>';
				}
				break;
	
			case ( 'post' ):
				// metas
				$meta_get = get_post_meta( $post->ID , $post_meta_key , true );
	
				break;
	
			case ( 'option' ):
				// variables
				$db_option = get_option( $search_type_args );
				$db_default_args = $box_input_args['db_default_args'];
				$db_text_spliter = $box_input_args['db_text_spliter'];
	
				foreach( $db_default_args as $slug => $name ) {
					$comb = $name.$spliter.$slug;
					$html .= '<option value="' . $comb . '" selected=selected locked=locked ids=' . $comb . '>' . $comb . '</option>';
				}
		
				if ( !empty($db_option) ) {
					foreach( $db_option as $slug => $name ) {
						$comb = $name.$spliter.$slug;
						$html .= '<option value="' . $comb . '" selected=selected ids=' . $comb . '>' . $comb . '</option>';
					}
				}
	
				break;
		}
	
		$html .= '</select>';
		foreach($invalid_feedbacks as $invalid_feedback) {
			echo '<div class="invalid-feedback">'.$invalid_feedback.'</div>';
		}
		$html .= '</div></td></tr></tbody></table>';
	
		echo $html;
	}

	/**
	 * Input Metabox for CPTs - fileupload
	 * 
	 */
	public function CPT_METABOXES_type_fileupload_html_content( $post, $callback_args ) {

		// variables
		$box_input_args = $callback_args['args'];
		$args = $box_input_args['object_init_args'];

		$new_UPLOAD_BOX = new UPLOAD_BOX( $args );
		$new_UPLOAD_BOX->UPLOAD_BOX_input_for_files_upload();

	}

}
