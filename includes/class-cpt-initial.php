<?php
/**
 * Class: CPT_INITIAL
 * Custom for global settings.
 */

class CPT_INITIAL {


	private $errors;
	private $labname;
	public $params;
	
	public $run;
	public $posttype;
	public $initial_args;

	/**
	 * This plugin's instance.
	 *
	 * @var CPT_INITIAL
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register($run, $params) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new CPT_INITIAL($params);
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
		foreach($params as $k => $v) {
			if (! in_array($k,
					array(
						'posttype',
						'autosave',
						'initial_args',
						'remove_media_btn',
					)
				)
			) {
				throw new Exception("Key {$k} not ready!");
			}
			if ($k === 'initial_args') {
				$this->CPT_INITIAL_register_array_validation($v);
			}
			if (in_array($k,
						array(
							'autosave',
							'remove_media_btn',
						)
				)
			   ) {
				if (! is_bool($v)) {
					throw new Exception("Value of key {$k} should be a boolean!");
				}
			}
			if ($k === 'posttype'){
				if (! is_string($v)) {
					throw new Exception("Value of key {$k} should be a string!");
				}
			}
		}

		// assign
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}

		// run hooks
		$this->CPT_INITIAL_preload();
	}

	private function CPT_INITIAL_register_array_validation( $cpt_initialization_array ) {

		// first is the $cpt_initialization_array
		if (empty($cpt_initialization_array)) {
			throw new Exception("Value of key initial_args shouldn't be empty!");
		}

		foreach($cpt_initialization_array as $k => $v) {
			if (! in_array($k,
					array(
						'labels',
						'supports',
						'public',
						'capability_type',
						'rewrite',
						'has_archive',
						'menu_position',
						'menu_icon',
						'delete_with_user',
						'show_in_rest',
						'rest_base',
						'exclude_from_search',
						'hierarchical',
						'publicly_queryable',
					)
				)
			) {
				throw new Exception("Key {$k} not ready!");
			}

			// validation for supports
			if ($k === 'supports') {
				if (! is_array($v)) {
					throw new Exception("Key {$k} should be an array!");
				}
				foreach($v as $s_v) {
					if (! in_array(
							$s_v,
							array(
								'title',
								'editor',
								'thumbnail',
								'comments',
								'revisions',
							)
						  )
					   ) {
						throw new Exception("Value of {$k} should be an array with either 'title', 'editor', 'thumbnail', 'comments' or 'revisions' five keys!");
					}
				}
			}
			// validation for rewrite
			if ($k === 'rewrite') {
				if (! is_array($v)) {
					throw new Exception("Key {$k} should be an array!");
				}
				foreach(
						array(
							'slug',
							'with_front'
						) as $s_v
					) {
					if (! in_array(
							$s_v,
							$v
						  )
					   ) {
						throw new Exception("Value of {$k} should be an array with both 'slug' and 'with_front' two keys!");
					}
				}
			}
			// validation for others
			if (in_array($k,
					array(
						'public',
						'has_archive',
						'delete_with_user',
						'show_in_rest',
						'exclude_from_search',
						'rest_base',
						'hierarchical',
						'publicly_queryable',
					)
				)
			) {
				if (! is_bool($v)) {
					throw new Exception("Value of key {$k} should be a boolean!");
				}
			}
			if ($k === 'labels') {
				foreach(array_values($v) as $l_v) {
					if (! (is_string($l_v)||is_bool($l_v))) {
						throw new Exception("Value of key {$k} should be a dictionary with values in string or boolean types!");
					}
				}
				foreach(
					array(
						'name',
						'singular_name',
						'add_new',
						'add_new_item',
						'edit_item',
						'new_item',
						'view_item',
						'search_items',
						'not_found',
						'not_found_in_trash',
					) as $l
					) {
					if (! in_array($l,
							array_keys($v),
						 )
					) {
						throw new Exception("Value of key {$k} should be a dictionary with key ${$l} in!");
					}
				}
			}
		}
	}


	private function CPT_INITIAL_preload() {

		// register posttype
		add_action( 'init' , array( $this, 'CPT_INITIAL_posttype_register' ), 10, 0 );

		// remove unnecessary edit screen support
		add_action( 'admin_init' , array( $this, 'CPT_INITIAL_remove_edit_screen_support' ) , 10, 0 );

		// disable auto save function
		add_action( 'admin_init', array( $this, 'CPT_INITIAL_disable_autosave' ) , 10, 0 );

		// remove media buttons
		add_action( 'admin_head' , array( $this, 'CPT_INITIAL_remove_media_buttons' ), 10, 0 );

		// 404 redirect
		add_filter( '404_template' , array( $this, 'CPT_INITIAL_template_404_redirect' ) , 1 , 1 );
	}

	public function CPT_INITIAL_posttype_register() {
		return register_post_type($this->posttype, $this->initial_args);
	}

	public function CPT_INITIAL_remove_edit_screen_support() {
		// variables
		global $cpt_utils;
		$supports = $this->initial_args['supports'];

		// remove edit screen support if not in supports array
		foreach(
				array(
					'title',
					'editor',
					'thumbnail',
					'comments',
					'revisions',
				) as $k 
			   ) {
			if (! in_array($k, $supports)) {
				$cpt_utils->CPT_UTILS__if_posttype_call_user_func_array('remove_post_type_support', $this->posttype, array($this->posttype, $k));
			}
		}
	}

	public function CPT_INITIAL_disable_autosave() {
		global $cpt_utils;
		$cpt_utils->CPT_UTILS__if_posttype_call_user_func_array(array($this, 'CPT_INITIAL_disable_autosave_call'), $this->posttype);
	}
	
	public function CPT_INITIAL_disable_autosave_call() {
		if ($this->autosave) {
			return wp_deregister_script( 'autosave' );
		}
		return;
	}
	
	public function CPT_INITIAL_remove_media_buttons() {
		if (! $this->remove_media_btn) {
			return;
		}

		global $current_screen;
		if ( $this->posttype == $current_screen->post_type ) {
			remove_action('media_buttons', 'media_buttons');
		}
	}

	/**
	 * 404 redirect when view CPT posts in frontend
	 * 
	 * @param object $template, which kind of page/post template
	 */
	public function CPT_INITIAL_template_404_redirect( $template ) {
		// Check if current path matches ^/event/ 
		if ( ! preg_match( "#^/{$this->posttype}/#", add_query_arg( [] ) ) ) {
			return $template;
		}

		// Try to locate our custom 404-event.php template    
		$new_404_template = locate_template( [ '404.php'] );

		// Override if it was found    
		if ( $new_404_template ) {
			$template = $new_404_template;
		}

		return $template;
	}
}
