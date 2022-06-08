<?php
/**
 * Class: CPT_CUSTOM
 * Custom for CPTs.
 */

class CPT_CUSTOM {


	private $errors;
	private $labname;
	public $params;
	
	public $run;
	public $posttype;
	public $trash_messages;
	public $update_messages;
	public $post_custom_columns;
	public $remove_row_actions;

	/**
	 * This plugin's instance.
	 *
	 * @var CPT_CUSTOM
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register($run, $params) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new CPT_CUSTOM($params);
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
						'trash_messages',
						'update_messages',
						'update_messages_unset',
						'post_custom_columns',
						'post_unset_columns',
						'remove_row_actions',
						'extend_admin_search',
						'submit_with_schedule',
						'remove_edit_permilink_button',
					)
				)
			) {
				throw new Exception("Key {$k} not ready!");
			}
			if ($k === 'posttype'){
				if (! is_string($v)) {
					throw new Exception("Value of key {$k} should be a string!");
				}
			}
			if (in_array($k,
					array(
						'trash_messages',
						'update_messages',
						'update_messages_unset',
						'post_custom_columns',
						'post_unset_columns',
						'remove_row_actions',
						'extend_admin_search',
					)
				)
			) {
				if (! is_array($v)) {
					throw new Exception("Value of key {$k} should be an array!");
				}
			}
			if (in_array($k,
					array(
						'submit_with_schedule',
						'remove_edit_permilink_button',
					)
				)
			) {
				if (! is_bool($v)) {
					throw new Exception("Value of key {$k} should be a boolean!");
				}
			}
			if ($k === 'post_unset_columns'){
				foreach($v as $p_u_c) {
					if (! in_array(
							$p_u_c,
							array(
								'title',
								'date',
							)
						  )
					   ) {
						throw new Exception("Value of key {$k} should have either title or date inside the array!");
					}
				}
			}
			if ($k === 'remove_row_actions'){
				foreach($v as $p_u_c) {
					if (! in_array(
							$p_u_c,
							array(
								'view',
								'inline hide-if-no-js',
								'edit',
								'trash',
							)
						  )
					   ) {
						throw new Exception("Value of key {$k} should have either view, 'inline hide-if-no-js', edit or trash inside the array!");
					}
				}
			}
			if ($k === 'update_messages'){
				if (!empty(array_diff(array_keys($v), range(0, 10))) ){
					throw new Exception("Value of key {$k} should have keys from 0 to 10!");
				}
				foreach(array_values($v) as $u_m_v) {
					if (! (is_string($u_m_v)||is_bool($u_m_v))) {
						throw new Exception("Value of key {$k} should be a dictionary with values in string or boolean types!");
					}
				}
			}
			if ($k === 'update_messages_unset'){
				foreach($v as $u_m_u_v) {
					if (! is_array($u_m_u_v)) {
						throw new Exception("Value of key {$k} should be an array!");
					}
					foreach($u_m_u_v as $u_m_u_v_k => $u_m_u_v_v) {
						if (! in_array(
								$u_m_u_v_k,
								array(
									'action',
									'find_action',
									'transient_label',
								)
							)
					   	) {
							throw new Exception("Arrays inside the items of key {$k} should have both 'action' and 'transient_label' keys!");
						}
						if (! is_string($u_m_u_v_v)) {
							throw new Exception("Value of key {$u_m_u_v_k} should be the string type!");
						}
					}
				}
			}
			if ($k === 'trash_messages'){
				foreach($v as $t_m => $t_m_v) {
					if (! in_array(
							$t_m,
							array(
								'trashed',
								'untrashed',
								'deleted',
								'locked',
								'updated',
							)
						  )
					   ) {
						throw new Exception("Value of key {$k} should have 'trashed', 'untrashed', 'deleted', 'locked' and 'updated' keys inside the array!");
					}
					if (! is_string($t_m_v)) {
						throw new Exception("Value of key {$k} should be a dictionary with values in string type!");
					}
				}
			}
			if ($k === 'post_custom_columns') {
				foreach(array_values($v) as $p_c_c_v) {
					if (! is_string($p_c_c_v)) {
						throw new Exception("Value of key {$k} should be a dictionary with values in string type!");
					}
				}
			}
			if ($k === 'extend_admin_search') {
				foreach($v as $e_a_s_v) {
					if (! is_string($e_a_s_v)) {
						throw new Exception("Value of key {$k} should be an array with values in string type!");
					}
				}
			}
		}

		// assign
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}

		// run hooks
		$this->CPT_INITIAL_preload();
		$this->add_hooks();
	}


	private function CPT_INITIAL_preload() {

		// set different message for CPTs
		add_filter( 'bulk_post_updated_messages', array( $this, 'CPT_INITIAL_bulk_move_to_trash_message' ) , 10, 2 );

		// rename labels for CPTs
		add_filter( 'post_updated_messages' , array( $this, 'CPT_INITIAL_bulk_change_post_updated_labels' ), 10, 1 );

		// hide some unnessesary list item actions for CPTs
		add_filter( 'post_row_actions' , array( $this, 'CPT_INITIAL_remove_row_actions' ) , 10 , 1 );

		// setting Column Names for CPTs
		add_filter( "manage_edit-{$this->posttype}_columns" , array( $this, 'CPT_INITIAL_posts_custom_column_add' ), 10, 1 );

		// add Post state behind Post title for CPTs
		add_filter( 'display_post_states', array( $this, 'CPT_INITIAL_add_post_state_behind_post_title' ) , 10, 2 );

		// modify the titles in rows
		add_action( 'admin_head-edit.php', array( $this, 'CPT_INITIAL_edit_post_change_title_posts_row' ) , 30 );

		// hide unnessesary button of submitdiv block when eding for CPTs
		add_action( 'admin_head-post.php' , array( $this, 'CPT_INITIAL_bulk_CPTs_publishing_actions'), 10, 0 );
		add_action( 'admin_head-post-new.php' , array( $this, 'CPT_INITIAL_bulk_CPTs_publishing_actions'), 10, 0 );
		
		// edit permilink button for CPTs
		add_filter( 'get_sample_permalink_html' , array( $this, 'CPT_INITIAL_remove_edit_permilink_button' ) , 10 , 4 );

		// extend search query
		add_action( 'admin_init', array($this, 'CPT_INITIAL_extend_admin_search'), 10, 0 );

	}


	private function add_hooks() {

	}

	/**
	 * Set different message for CPTs.
	 * 
	 * :See @https://wordpress.stackexchange.com/questions/359980/change-message-given-when-deleting-post-from-custom-post-type
	 * 
	 */
	public function CPT_INITIAL_bulk_move_to_trash_message( $bulk_messages, $bulk_counts ) {
		$bulk_messages[$this->posttype] = isset( $bulk_messages[$this->posttype] ) ? $bulk_messages[$this->posttype] : array();
		foreach($this->trash_messages as $k => $v) {
			$bulk_messages[$this->posttype][$k] = $v;	
		}
		return $bulk_messages;
	}

	/**
	 * Rename labels for CPTs.
	 * 
	 */
	public function CPT_INITIAL_bulk_change_post_updated_labels($messages) {
		global $post;

		if ( $post->post_type === $this->posttype ) {
			$messages[$this->posttype] = isset( $messages[$this->posttype] ) ? $messages[$this->posttype] : array();
			$messages[$this->posttype] = $this->update_messages;
		}

		foreach($this->update_messages_unset as $u_m_u_v) {

			$new_CPT_MESSAGES = new CPT_MESSAGES(
				array(
					'posttype' => $this->posttype,
					'post_obj' => $post,
					'action' => $u_m_u_v['action'],
					'find_action' => $u_m_u_v['find_action'],
					'transient_label' => $u_m_u_v['transient_label']
				)
			);

			if ( $err = get_transient( $new_CPT_MESSAGES->return_result ) ) {
				if ( !empty($err) ) {
					unset($messages[$this->posttype][1]);
				}
			}
		}
		return $messages;
	}

	/**
	 * Hide some unnessesary list item actions for CPTs.
	 * 
	 */
	public function CPT_INITIAL_remove_row_actions( $actions ) {
		if( get_post_type() === $this->posttype ) {
			foreach($this->remove_row_actions as $k) {
				unset( $actions[$k] );	
			}
		}
		return $actions;
	}

	/**
	 * Setting Column Names for CPTs.
	 * 
	 */
	public function CPT_INITIAL_posts_custom_column_add( $columns ) {
		foreach($this->post_custom_columns as $k => $v) {
			$columns[$k] = $v;	
		}
		foreach($this->post_unset_columns as $k) {
			unset($columns[$k]);
		}
		return $columns;
	}

	/**
	 * Add Post state behind Post title for CPTs.
	 * 
	 * @param object $post_states, $post
	 */
	public function CPT_INITIAL_add_post_state_behind_post_title( $post_states, $post ) {
		if ( $post->post_type === $this->posttype ) {
			$emoji = array('😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😇', '😉', '😊', '🙂', '🙃', '😋', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '🥲', '🤪', '😜', '😝', '😛', '🤑', '😎', '🤓', '🥸', '🧐', '🤠', '🥳', '🤗', '🤡', '😏', '😶', '😐', '😑', '😒', '🙄', '🤨', '🤔', '🤫', '🤭', '🤥', '😳', '😞', '😟', '😠', '😡', '🤬', '😔', '😕', '🙁', '😬', '🥺', '😣', '😖', '😫', '😩', '🥱', '😤', '😮‍💨', '😮', '😱', '😨', '😰', '😯', '😦', '😧', '😢', '😥', '😪', '🤤', '😓', '😭', '🤩', '😵', '😵‍💫', '🥴', '😲', '🤯', '🤐', '😷', '🤕', '🤒', '🤮', '🤢', '🤧', '🥵', '🥶', '😶‍🌫️', '😴', '💤', '😈', '👿', '👹', '👺', '💩', '👻', '💀', '☠', '👽', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾');
			$post_states[] = $emoji[array_rand($emoji)];
		}
		return $post_states;
	}

	/**
	 * Modify the titles in rows.
	 * 
	 */
	public function CPT_INITIAL_edit_post_change_title_posts_row() {
		global $cpt_utils;
		$cpt_utils->CPT_INITIAL__if_posttype_add_filter('the_title', array( $this, 'CPT_INITIAL_construct_new_title' ), 10 , 2, $this->posttype );
	}

	/**
	 * Function for 'CPT_INITIAL_edit_post_change_title_posts_row'.
	 * 
	 */
	public function CPT_INITIAL_construct_new_title( $title, $id ) {
		return "{$this->posttype}-{$id}";
	}

	/**
	 * Hide unnessesary button of submitdiv block when eding for CPTs.
	 * 
	 * :See @https://wordpress.stackexchange.com/questions/36118/how-to-hide-everything-in-publish-metabox-except-move-to-trash-publish-button
	 * :See @https://wordpress.stackexchange.com/questions/175/marking-future-dated-post-as-published
	 * :See @https://wordpress.stackexchange.com/questions/52294/remove-minor-publishing-div-from-publish-admin-metabox
	 * 
	 */
	public function CPT_INITIAL_bulk_CPTs_publishing_actions() {
		if ($this->submit_with_schedule) {
			global $post;
			if( $post->post_type === $this->posttype ) {
				echo '<style type="text/css">#minor-publishing{display:none}</style>';
				if ( $post->post_status !== 'publish' ) {
					echo '<style type="text/css">#edit-slug-box{display:none}</style>';
				}
			}
		}
	}

	/**
	 * Edit permilink button for CPTs.
	 * 
	 * @param object $return, $id, $new_title, $new_slug
	 */
	public function CPT_INITIAL_remove_edit_permilink_button($return, $id, $new_title, $new_slug){
		if ($this->remove_edit_permilink_button) {
			global $cpt_utils;
			return $cpt_utils->CPT_UTILS__if_posttype_call_user_func_array(
				'preg_replace',
				$this->posttype,
				array(
					'/<span id="edit-slug-buttons">.*<\/span>|<span id=\'view-post-btn\'>.*<\/span>/i',
					'', 
					$return
				)
			);
		}
	}

	/**
	 * Extend for custom search
	 * 
	 */
	public function CPT_INITIAL_extend_admin_search() {
		global $cpt_utils;
		$cpt_utils->CPT_INITIAL__if_posttype_add_filter('posts_search', array($this, 'CPT_INITIAL_posts_search_CPTs'), 10, 2, $this->posttype );
    }

	/**
	 * Function for 'CPT_INITIAL_extend_admin_search'.
	 * 
	 */
	public function CPT_INITIAL_posts_search_CPTs($search, $query) {

		global $wpdb;

		// combination of $this->meta_keys
		$meta_keys_sql = array();
		foreach($this->extend_admin_search as $k) {
			$meta_keys_sql[] = "'".$k."'";
		}
		$meta_keys_sql_str = implode(",", $meta_keys_sql);
		unset($meta_keys_sql);

		// if have custom search query
        if ($query->is_main_query() && !empty($query->query['s'])) {
            $sql    = "
            or exists (
                select * from {$wpdb->postmeta} where post_id={$wpdb->posts}.ID
                and meta_key in ({$meta_keys_sql_str})
                and meta_value like %s
            )
			";
            $like   = '%' . $wpdb->esc_like($query->query['s']) . '%';
            $search = preg_replace("#\({$wpdb->posts}.post_title LIKE [^)]+\)\K#",
                $wpdb->prepare($sql, $like), $search);
        }

		global $utils;

        return $search;
	}
}
