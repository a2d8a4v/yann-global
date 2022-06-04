<?php
/**
 * Class: CPT_UTILS
 * Custom for global settings.
 */

class CPT_UTILS {

	private $errors;
	private $meta_keys;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->errors = False;
		$this->YANN_root_user_id = 1;
	}

	public function CPT_UTILS_ajax_nonce_generate() {
		return wp_create_nonce("YANN-ajax-nonce");
	}

	public function CPT_UTILS_ajax_restapi_nonce_generate() {
		return wp_create_nonce('wp_rest');
	}

	public function CPT_UTILS_ajax_url() {
		return admin_url('admin-ajax.php');
	}

	public function CPT_UTILS_validation_enqueue_resources( $type, $hook, $posttype, $_get ) {

		// $hook
		if (! in_array(
				$type,
				array(
					'cpt',
					'profile',
				)
			  )
		   ) {
			throw new Exception('cpt or profile option only!');
		}

		if ($type === 'cpt') {

			if ( ! in_array( $hook , array('post.php', 'post-new.php') ) ) {
				return FALSE;
			}
	
			if ( isset($_get['post_type']) && $_get['post_type'] !== $posttype ) {
				return FALSE;
			} else if ( isset($_get['post']) && get_post($_get['post'])->post_type !== $posttype ) {
				return FALSE;
			}

		} else if ($type === 'profile') {

			if ( !in_array( $hook , array( 'profile.php' , 'user-edit.php' ) ) ) {
				return FALSE;
			}

		}

		return TRUE;
	}

	public function CPT_INITIAL__if_posttype_call_user_func_array( $call_func, $posttype, $args_call_func=array() ) {
		
		// validation
		if (! (is_string($call_func)||is_array($call_func))) {
			throw new Exception("call_func should be a string or an array!");
		}
		if (! is_string($posttype)) {
			throw new Exception("posttype should be a string!");
		}
		if (! is_array($args_call_func)) {
			throw new Exception("args_call_func should be an array!");
		}

		// variable
		global $typenow;

		// is_this
		if ( $typenow && $typenow === $posttype ) {
			call_user_func_array($call_func, $args_call_func);
		}
		if ( isset($_REQUEST["post"]) && get_post_type($_REQUEST["post"]) === $posttype ) {
			call_user_func_array($call_func, $args_call_func);
		}
	}

	public function CPT_INITIAL__if_posttype_add_filter($hook, $call_func, $prior, $params_num, $posttype ) {
		
		// validation
		if (! is_string($hook)) {
			throw new Exception("hook should be a string!");
		}
		if (! is_array($call_func)) {
			throw new Exception("call_func should be an array!");
		}
		if (! is_int($prior)) {
			throw new Exception("prior should be a int value!");
			if (intval($prior) < 0) {
				throw new Exception("prior should be bigger than 0!");
			}
		}
		if (! is_int($params_num)) {
			throw new Exception("params_num should be a int value!");
			if (intval($params_num) < 0) {
				throw new Exception("params_num should be bigger than 0!");
			}
		}
		if (! is_string($posttype)) {
			throw new Exception("posttype should be a string!");
		}

		// variable
		global $typenow;

		// is_this
		if ( $typenow && $typenow === $posttype ) {
			add_filter($hook , $call_func, $prior, $params_num);
		}
		if ( isset($_REQUEST["post"]) && get_post_type($_REQUEST["post"]) === $posttype ) {
			add_filter($hook , $call_func, $prior, $params_num);
		}
	}

}
