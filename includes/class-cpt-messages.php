<?php
/**
 * Class: CPT_MESSAGES
 * Custom for global settings.
 */

class CPT_MESSAGES {

	private $errors;
	private $labname;
	public $params;
	public $return_result;

	/**
	 * This plugin's instance.
	 *
	 * @var CPT_MESSAGES
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register($run, $params) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new CPT_MESSAGES($params);
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
		$this->return_result = '';

		// validation
		// $this->CPT_MESSAGES_params_validation($params);
		// assign
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}
		
		// run functions
		$this->CPT_MESSAGES_select_function();
	}

	private function CPT_MESSAGES_params_validation( $cpt_messages_array ) {

		// first is the $cpt_initialization_array
		if (empty($cpt_metaboxes_array)) {
			throw new Exception("Arguments shouldn't be empty!");
		}

	}

	private function CPT_MESSAGES_select_function() {

		global $cpt_utils;

		switch ($this->action) {
			case ('deleting'):

				$func = array($this, "CPT_MESSAGES_{$this->action}_{$this->act_for_type}_{$this->msg_type}_message");
				$cpt_utils->CPT_UTILS__if_posttype_call_user_func_array( $func, $this->posttype, array() );

				break;

			case ('editing'):

				$func = array($this, "CPT_MESSAGES_{$this->action}_{$this->act_for_type}_{$this->msg_type}_message");
				$cpt_utils->CPT_UTILS__if_posttype_call_user_func_array( $func, $this->posttype, array() );

				break;
			
			case ('register'):

				// display admin notices for CPTs if not the post author edited
				add_action( 'admin_notices', array( $this, 'CPT_MESSAGES_reject_non_author_editing_notice' ), 10, 0 );
				break;

			case ('get_transient'):

				$func = array($this, "CPT_MESSAGES_{$this->action}");
				$cpt_utils->CPT_UTILS__if_posttype_call_user_func_array( $func, $this->posttype, array() );

				break;

		}
	}

	public function CPT_MESSAGES_editing_save_type_input_error_message() {

		// variables
		$post           = $this->post_obj;
		$current_USERID = get_current_user_id();
		$author_ID      = $post->post_author;
		$error          = new WP_Error();

		if ( intval($author_ID) !== intval($current_USERID) ) {

			$user_name = get_user_meta( $author_ID , 'last_name' , true ) . get_user_meta( $author_ID , 'first_name' , true );

			foreach($this->action_msg as $k => $msg) {
				$error->add("CPT_{$this->posttype}_{$this->action}_{$k}_{$this->msg_type}", $msg);
			}

			set_transient("CPT_{$this->posttype}_{$this->action}_{$this->transient_label}_{$post->ID}_{$current_USERID}", $error, $this->time_keep);

			if (! empty($this->redirect)) {
				wp_redirect(
					add_query_arg(
						$this->redirect['query'],
						$this->redirect['url']
					)
				);
				exit();
			}

			$this->return_result = $error;

			return $this->return_result;
		}
		
	}


	public function CPT_MESSAGES_editing_save_type_multiselect_error_message() {
	
		// variables
		$post           = $this->post_obj;
		$current_USERID = get_current_user_id();
		$author_ID      = $post->post_author;
		$error          = new WP_Error();

		// it should be this CPTs post author, otherwise deny
		if ( intval($post->post_author) !== intval($current_USERID) ) {

			$user_name = get_user_meta( $post->post_author , 'last_name' , true ) . get_user_meta( $post->post_author , 'first_name' , true );

			if ( $tmp = get_post_meta( $post->ID, $this->post_meta_key , true ) ) {

				if ( empty($tmp) ) {

					foreach($this->action_msg as $k => $msg) {
						$error->add("CPT_{$this->posttype}_{$this->action}_{$k}_{$this->msg_type}", $msg);
					}

				} else if ( !empty($tmp) ) {

					$names = array();
					foreach ( $tmp as $user_id ) {
						if ( $user_id === $post->post_author ) {
							continue;
						}
						$names[] = get_user_meta( $user_id , 'last_name' , true ) . get_user_meta( $user_id , 'first_name' , true );
					}
					foreach($this->action_msg as $k => $msg) {
						$error->add("CPT_{$this->posttype}_{$this->action}_{$k}_{$this->msg_type}", "必須為本篇作者 {$user_name} 以及 ".implode("、",$names)." 共同作品作者才可以進行修改。");
					}
				}
			}

			set_transient("CPT_{$this->posttype}_{$this->action}_{$this->transient_label}_{$post->ID}_{$current_USERID}", $error, $this->time_keep);

			$this->return_result = $error;

			return $this->return_result;
		}
		
	}


	public function CPT_MESSAGES_deleting_single_post_error_message() {

		// variables
		$post           = $this->post_obj;
		$current_USERID = get_current_user_id();
		$author_ID      = $post->post_author;
		$error          = new WP_Error();

		if ( intval($author_ID) !== intval($current_USERID) ) {

			$user_name = get_user_meta( $author_ID , 'last_name' , true ) . get_user_meta( $author_ID , 'first_name' , true );

			foreach($this->action_msg as $k => $msg) {
				$error->add("CPT_{$this->posttype}_{$this->action}_{$k}_{$this->msg_type}", $msg);
			}

			set_transient("CPT_{$this->posttype}_{$this->action}_{$this->transient_label}_{$current_USERID}", $error, $this->time_keep);

			if (! empty($this->redirect)) {
				wp_redirect(
					add_query_arg(
						$this->redirect['query'],
						$this->redirect['url']
					)
				);
				exit();
			}
		}
	}

	/**
	 * stop move to trash if is not author
	 * 
	 */
	public function CPT_MESSAGES_deleting_multiple_posts_error_message() {

		// variables
		$current_USERID = get_current_user_id();
		$error = new WP_Error();

		foreach ( $this->get_ojb['post'] as $postid ) {

			$author_ID = get_post($postid)->post_author;

			if ( intval($author_ID) !== intval($current_USERID) ) {
				$user_name = get_user_meta( $author_ID , 'last_name' , true ) . get_user_meta( $author_ID , 'first_name' , true );
				foreach($this->action_msg as $k => $msg) {
					$error->add("CPT_{$this->posttype}_{$this->action}_{$k}_{$this->msg_type}_{$postid}", $msg);
				}
				continue;
			}

			// move post to trash if is author
			global $cpt_utils;
			foreach($this->func_append as $func) {
				$cpt_utils->CPT_UTILS__if_posttype_call_user_func_array(
					$func,
					$this->posttype,
					array(
						$postid
					)
				);
			}
		}

		if ( $error->has_errors() ) {

			set_transient("CPT_{$this->posttype}_{$this->action}_{$this->transient_label}_{$current_USERID}", $error, $this->time_keep);

			if (! empty($this->redirect)) {
				wp_redirect(
					add_query_arg(
						$this->redirect['query'],
						$this->redirect['url']
					)
				);
				exit();
			}
		}
	}

	public function CPT_MESSAGES_get_transient() {
		
		// variables
		$post = $this->post_obj;
		$current_USERID = get_current_user_id();

		$transient = "CPT_{$this->posttype}_{$this->find_action}_{$this->transient_label}_{$post->ID}_{$current_USERID}";

		$this->return_result = $transient;
		return $this->return_result;
	}


	/**
	 * Display admin notices for CPT meeting if not the post author edited
	 * 
	 * :See @https://www.sitepoint.com/displaying-errors-from-the-save-post-hook-in-wordpress/
	 * :See @https://developer.wordpress.org/reference/classes/wp_error/get_error_messages/
	 * 
	 */
	public function CPT_MESSAGES_reject_non_author_editing_notice() {

		global $post;
		if (empty($post)) {
			return;
		}
		$post_id = $post->ID;
		$current_USERID = get_current_user_id();

		foreach($this->items as $item) {

			switch ($item['action']) {

				case ('deleting'):

					if ( $error = get_transient( "CPT_".$this->posttype."_".$item['action']."_".$item['transient_label']."_".$current_USERID ) ) { 

						foreach ( $error->get_error_messages() as $err_mess ) {
			
							?>
							<div class="notice notice-{<?php echo $item['msg_type']; ?>} is-dismissible">
								<p><?php echo $err_mess; ?></p>
							</div>
							<?php
			
						}
			
						delete_transient( "CPT_".$this->posttype."_".$item['action']."_".$item['transient_label']."_".$current_USERID );
					}

					break;

				case ('editing'):

					if ( $error = get_transient( "CPT_".$this->posttype."_".$item['action']."_".$item['transient_label']."_".$post_id."_".$current_USERID ) ) {

						?>
						<div class="notice notice-{<?php echo $item['msg_type']; ?>} is-dismissible">
							<p><?php echo $error->get_error_message(); ?></p>
						</div>
						<?php
			
						delete_transient( "CPT_".$this->posttype."_".$item['action']."_".$item['transient_label']."_".$post_id."_".$current_USERID );

					}

					break;
			}
		}
	}
}
