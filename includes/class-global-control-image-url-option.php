<?php
/**
 * Class: YANN_MEETING
 * Custom for NTNU SMIL LAB Meeting components.
 */

// @https://rudrastyh.com/wordpress/creating-options-pages.html
// @https://codex.wordpress.org/Creating_Options_Pages

class YANN_MEETING_OPTION {
	private $errors;

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->errors = False;
		$this->YANN_root_user_id = 1;
		$this->posttype = "meeting";
		$this->labname = "NTNUSMIL";
		$this->pageslug = "CPT_Meeting_options";
		$this->optiongroupname = "CPT_Meeting_option_group";
		$this->table_for_option_record = "CPT_meeting_table_for_conference_names";
		$this->conference_names_default = array(
			'ACL',
			'Interspeech',
			'EUSIPCO',
			'ROCLING',
			'APSIPA',
			'IJCNLP',
			'SLT',
			'ASRU',
			'ICME',
			'Arxiv',
			'ICASSP',
			'EMNLP',
			'CIKM',
			'NIPS',
			'NAACL',
			'IJCAI',
			'AAAI',
			'SIGIR',
		);
		$this->conference_names_show_option = $this->optiongroupname."_show_option";
		$this->conference_names_show_default = array(
			'strtoupper',
			'strtolower',
		);
		$this->conference_names_show = array(
			'normal',
		);
		$this->conference_showing_depending_on_uploaded_ppts_option = $this->optiongroupname."_show_conferences_depending_on_uploaded_ppts";
		$this->conference_showing_depending_on_uploaded_ppts = 'yes';
		$this->conference_show_conferences_depending_on_selected_pages_option = $this->optiongroupname."_show_conferences_depending_on_selected_pages";
		$this->conference_show_conferences_depending_on_selected_pages_show = array(
			'meeting',
		);
		$this->conference_places_adding_option = $this->optiongroupname."conference_places_adding_option";
		$this->conference_places_split = "|";
		$this->conference_places_adding = array(
			"Online" => "線上舉辦",
			"C209"   => "C209",
			"Central_Meeting_Room" => "中間會議室",
		);
		$this->add_hooks();
	}

	/**
	 * Register actions and filters.
	 */
	// @https://www.cssigniter.com/how-to-add-custom-fields-to-the-wordpress-registration-form/
	public function add_hooks() {
		add_action( 'wp' , array( $this, 'YANN_NTNUSMIL_CPT_meeting_options_table' ) );

		add_action( 'admin_menu', array( $this, 'YANN_NTNUSMIL_CPT_meeting_options_page_register' ) );
		add_action( 'admin_init', array( $this, 'YANN_NTNUSMIL_CPT_meeting_options_page_setting' ) );
		add_action( 'admin_enqueue_scripts' , array( $this, 'YANN_NTNUSMIL_CPT_meeting_option_admin_enqueue_scripts' ) , 20 , 1 );
		add_action( 'wp_ajax_YANN_NTNUSMIL_CPT_meeting_search_for_conferences_search_action', array( $this, 'YANN_NTNUSMIL_CPT_meeting_search_for_conferences_search_handle' ) );
		add_action( 'wp_ajax_YANN_NTNUSMIL_CPT_meeting_search_for_pages_search_action', array( $this, 'YANN_NTNUSMIL_CPT_meeting_search_for_pages_search_action_handle' ) );
		add_action( 'wp_ajax_CPT_meeting_search_for_places_search_action', array( $this, 'CPT_meeting_search_for_places_search_action_handle' ) );
		add_action( 'wp_ajax_YANN_NTNUSMIL_CPT_meeting_search_for_conferences_settings_save_action', array( $this, 'YANN_NTNUSMIL_CPT_meeting_search_for_conferences_settings_save_handle' ) );

		// add custom static page settings
		add_filter( 'display_post_states', array($this, 'YANN_NTNUSMIL_CPT_meeting_pages_states'), 10, 2 );
	}

	/**
	 * Registers option page for meeting post type.
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_options_page_register() {

		add_submenu_page(
			'edit.php?post_type=meeting',
			'設定', // page <title>Title</title>
			'設定', // menu link text
			'manage_options', // capability to access the page
			$this->pageslug, // page URL slug
			array( $this , 'YANN_NTNUSMIL_CPT_meeting_options_page_content' ), // callback function /w content
		);
	}

	/**
	 * Registers field in option page.
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_options_page_content(){

		echo '<div class="wrap">
		<h1>設定</h1>
		<form method="post" action="options.php">';
				
			settings_fields( $this->optiongroupname ); // settings group name
			do_settings_sections( $this->pageslug ); // just a page slug
			submit_button();

		echo '</form></div>';
	
	}

	/**
	 * Registers the option field for meeting post type.
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_options_page_setting(){
	
		register_setting(
			$this->optiongroupname, // settings group name
			'YANN_NTNUSMIL_CPT_meeting_options_conference_names', // option name
			'sanitize_text_field' // sanitization function
		);

		// Generate options in db
		if ( !get_option( $this->conference_names_show_option, false ) ) {
			update_option( $this->conference_names_show_option, $this->conference_names_show, '', 'no' );
		}
		if ( !get_option( $this->conference_showing_depending_on_uploaded_ppts_option, false ) ) {
			update_option( $this->conference_showing_depending_on_uploaded_ppts_option, $this->conference_showing_depending_on_uploaded_ppts, '', 'no' );
		}
		if ( !get_option( $this->conference_show_conferences_depending_on_selected_pages_option, false ) ) {
			update_option( $this->conference_show_conferences_depending_on_selected_pages_option, array(), '', 'no' );
		}
		if ( !get_option( $this->conference_places_adding_option, false ) ) {
			update_option( $this->conference_places_adding_option, array(), '', 'no' );
		}
	
		// Conference Section
		add_settings_section(
			'YANN_NTNUSMIL_CPT_meeting_options_section_normal', // section ID
			'一般設定', // title (if needed)
			'', // callback function (if needed)
			$this->pageslug // page slug
		);
		add_settings_field(
			'YANN_NTNUSMIL_CPT_meeting_options_conference_names',
			'Conference 名稱',
			array( $this , 'YANN_NTNUSMIL_CPT_meeting_option_field_html' ), // function which prints the field
			$this->pageslug, // page slug
			'YANN_NTNUSMIL_CPT_meeting_options_section_normal', // section ID
			array( 
				'label_for' => 'YANN_NTNUSMIL_CPT_meeting_options_conference_names',
				'class' => 'row-YANN_NTNUSMIL_CPT_meeting_options_conference_names', // for <tr> element
			)
		);
		add_settings_field(
			'YANN_NTNUSMIL_CPT_meeting_options_conference_names_show',
			'Conference 前台顯示',
			array( $this , 'YANN_NTNUSMIL_CPT_meeting_checkbox_field_html' ), // function which prints the field
			$this->pageslug, // page slug
			'YANN_NTNUSMIL_CPT_meeting_options_section_normal', // section ID
			array( 
				'label_for' => 'YANN_NTNUSMIL_CPT_meeting_options_conference_names_show',
				'class' => 'row-YANN_NTNUSMIL_CPT_meeting_options_conference_names_show', // for <tr> element
			)
		);

		// Showing Section
		add_settings_section(
			'YANN_NTNUSMIL_CPT_meeting_options_section_showing', // section ID
			'顯示設定', // title (if needed)
			'', // callback function (if needed)
			$this->pageslug // page slug
		);
		add_settings_field(
			'YANN_NTNUSMIL_CPT_meeting_options_showing_depending_on_uploaded_ppts',
			'根據是否上傳 PPT 檔案決定在前端顯示會議',
			array( $this , 'YANN_NTNUSMIL_CPT_meeting_checkbox_showing_depending_on_uploaded_ppts_html' ), // function which prints the field
			$this->pageslug, // page slug
			'YANN_NTNUSMIL_CPT_meeting_options_section_showing', // section ID
			array( 
				'label_for' => 'YANN_NTNUSMIL_CPT_meeting_options_showing_depending_on_uploaded_ppts',
				'class' => 'row-YANN_NTNUSMIL_CPT_meeting_options_showing_depending_on_uploaded_ppts', // for <tr> element
			)
		);
		add_settings_field(
			'YANN_NTNUSMIL_CPT_meeting_options_showing_block_usable',
			'指定 Meeting 為哪些頁面（區塊才可以使用）',
			array( $this , 'YANN_NTNUSMIL_CPT_meeting_options_showing_block_usable_html' ), // function which prints the field
			$this->pageslug, // page slug
			'YANN_NTNUSMIL_CPT_meeting_options_section_showing', // section ID
			array( 
				'label_for' => 'YANN_NTNUSMIL_CPT_meeting_options_showing_block_usable',
				'class' => 'row-YANN_NTNUSMIL_CPT_meeting_options_showing_block_usable', // for <tr> element
			)
		);

		// Places section
		add_settings_section(
			'CPT_meeting_options_places', // section ID
			'舉辦地點', // title (if needed)
			'', // callback function (if needed)
			$this->pageslug // page slug
		);
		add_settings_field(
			'CPT_meeting_options_places_usable',
			'指定 Meeting 為哪些頁面（區塊才可以使用）',
			array( $this , 'CPT_meeting_options_places_usable_html' ), // function which prints the field
			$this->pageslug, // page slug
			'CPT_meeting_options_places', // section ID
			array( 
				'label_for' => 'CPT_meeting_options_places_usable',
				'class' => 'row-CPT_meeting_options_places_usable', // for <tr> element
			)
		);

	}
	
	/**
	 * Function for YANN_NTNUSMIL_CPT_meeting_options_page_setting
	 * 
	 */
	// @https://stackoverflow.com/questions/49324327/how-not-to-allow-delete-options-in-select2
	public function YANN_NTNUSMIL_CPT_meeting_option_field_html(){

		$html = "";
		$html .= '<select id="CPT_meeting_option_conference_names" name="CPT_meeting_option_conference_names[]" multiple="multiple" style="width:99%;max-width:25em;">';

		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_for_option_record;
		$search_c = $wpdb->get_results( "SELECT * FROM $table_name" );
		$default = $this->conference_names_default;
		if ( !empty($default) ) {
			foreach( $default as $d ) {
				$name = $d;
				$html .= '<option value="' . $name . '" selected=selected locked=locked>' . $name . '</option>';
			}
		}
		if ( !empty($search_c) ) {
			foreach( $search_c as $s_c ) {
				$name = $s_c->name;
				$html .= '<option value="' . $name . '" selected=selected>' . $name . '</option>';
			}
		}

		$html .= '</select>';
		echo $html;

	}


	/**
	 * Function for YANN_NTNUSMIL_CPT_meeting_options_page_setting
	 * 
	 */
	// @https://stackoverflow.com/questions/49324327/how-not-to-allow-delete-options-in-select2
	public function YANN_NTNUSMIL_CPT_meeting_checkbox_field_html() {

		$options = array_merge($this->conference_names_show_default, $this->conference_names_show);
		$db_option = get_option( $this->conference_names_show_option )[0];
		$options_names = array(
			'strtoupper' => '大寫',
			'strtolower' => '小寫',
			'normal' => '不調整',
		);

		$html = "";
		$html .= '<select id="CPT_meeting_option_conference_show" name="CPT_meeting_option_conference_show" style="width:99%;max-width:25em;">';

		foreach($options as $option) {
			$selected = selected($option, $db_option, false);
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $options_names[$option] . '</option>';
		}

		$html .= '</select>';
		echo $html;
	}


	/**
	 * Function for YANN_NTNUSMIL_CPT_meeting_options_page_setting
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_checkbox_showing_depending_on_uploaded_ppts_html() {

		$db_option = get_option( $this->conference_showing_depending_on_uploaded_ppts_option );

		$html = "";
		$html .= '<input type="checkbox" ' . checked($db_option,'yes',false) . ' id="CPT_meeting_option_showing_conferences_depending_on_uploaded_ppts" name="CPT_meeting_option_showing_conferences_depending_on_uploaded_ppts" />';

		echo $html;

	}


	/**
	 * Function for YANN_NTNUSMIL_CPT_meeting_options_page_setting
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_options_showing_block_usable_html() {

		$db_option = get_option( $this->conference_show_conferences_depending_on_selected_pages_option );
		$default = $this->conference_show_conferences_depending_on_selected_pages_show;

		$html = "";
		$html .= '<select id="CPT_meeting_option_showing_block_usable" name="CPT_meeting_option_showing_block_usable[]" multiple="multiple" style="width:99%;max-width:25em;">';

		foreach( $default as $slug ) {
			$post = get_page_by_path($slug);
			$html .= '<option value="' . $post->ID . '" selected=selected locked=locked ids=' . $post->ID . '>' . $post->post_title . '</option>';
		}

		if ( !empty($db_option) ) {
			foreach( $db_option as $_p_id ) {
				$post = get_post( $_p_id );
				$html .= '<option value="' . $post->ID . '" selected=selected ids=' . $post->ID . '>' . $post->post_title . '</option>';
			}
		}

		$html .= '</select>';
		echo $html;

	}


	/**
	 * Function for YANN_NTNUSMIL_CPT_meeting_options_page_setting
	 * 
	 */
	public function CPT_meeting_options_places_usable_html() {

		$db_option = get_option( $this->conference_places_adding_option );
		$default   = $this->conference_places_adding;

		$html = "";
		$html .= '<select id="CPT_meeting_option_places_usable" name="CPT_meeting_option_places_usable[]" multiple="multiple" style="width:99%;max-width:25em;">';

		foreach( $default as $slug => $name ) {
			$comb = $name.$this->conference_places_split.$slug;
			$html .= '<option value="' . $comb . '" selected=selected locked=locked ids=' . $comb . '>' . $comb . '</option>';
		}

		if ( !empty($db_option) ) {
			foreach( $db_option as $slug => $name ) {
				$comb = $name.$this->conference_places_split.$slug;
				$html .= '<option value="' . $comb . '" selected=selected ids=' . $comb . '>' . $comb . '</option>';
			}
		}

		$html .= '</select>';
		$html .= '<div class="invalid-feedback" id="CPT_meeting_options_places_usable_warning"></div>';
		$html .= '<p>請以此格式輸入：<code>名稱'.$this->conference_places_split.'slug</code>，slug 只能使用<mark>英文字母或阿拉伯數字</mark></p>';
		echo $html;

	}


	/**
	 * Enqueue scripts for option page of meeting post type.
	 * 
	 * @param object $hook, which admin page
	 */
	public function YANN_NTNUSMIL_CPT_meeting_option_admin_enqueue_scripts( $hook ) {
		if ( ! in_array( $hook , array('meeting_page_CPT_Meeting_options') ) ) {
			return;
		}
		if ( !isset($_REQUEST['post_type']) ) {
			return;
		}
		if ( isset($_REQUEST['post_type']) && $_REQUEST['post_type'] !== $this->posttype ) {
			return;
		}
		wp_enqueue_style( 'select2' , 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' );
		wp_enqueue_script( 'select2' , 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' , array('jquery') );
		wp_enqueue_style( 'admin-cpt-meeting-option-css' , plugins_url( '', dirname( __FILE__ ) ) . '/includes/css/admin/admin-cpt-meeting-option-css.css' );
		wp_enqueue_script( 'admin-cpt-meeting-option-js' , plugins_url( '', dirname( __FILE__ ) ) . '/includes/js/admin/admin-cpt-meeting-option-js.js' , array() , time() , true );
		wp_localize_script( 'admin-cpt-meeting-option-js' , 'YANN' , array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ) ,
			'nonce'   => wp_create_nonce( 'YANN-ajax-nonce' ) ,
			'action_search_conferences'  => 'YANN_NTNUSMIL_CPT_meeting_search_for_conferences_search_action',
			'action_search_pages'  => 'YANN_NTNUSMIL_CPT_meeting_search_for_pages_search_action',
			'action_search_places' => 'CPT_meeting_search_for_places_search_action',
			'action_save'  => 'YANN_NTNUSMIL_CPT_meeting_search_for_conferences_settings_save_action',
		));
	}

	/**
	 * Registers the demo post type.
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_search_for_conferences_search_handle() {
		$nonce = ( isset( $_POST[ 'nonce' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce , 'YANN-ajax-nonce' ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}

		// variables
		$return = array( 
			"d" => array(),
			"c" => array(),
		);
		$q = strtoupper($_POST['q']);

		// default search
		foreach ( $this->conference_names_default as $s ) {
			if (strpos(strtoupper($s), $q) !== false) {
				$return["d"][] = $s;
			}
		}

		// custom search
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_for_option_record;
		$search_c = $wpdb->get_results( "SELECT * FROM $table_name WHERE name LIKE '".$q."%'" );
		if ( !empty($search_c) ) {
			foreach ( $search_c as $s_c ) {
				$return["c"][] = $s_c->name;
			}
		}

		echo json_encode($return);
		die();
	}


	/**
	 * Registers the demo post type.
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_search_for_pages_search_action_handle() {
		$nonce = ( isset( $_POST[ 'nonce' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce , 'YANN-ajax-nonce' ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}

		// variables
		$return = array( 
			"d" => array(),
			"c" => array(),
		);
		$q = $_POST['q'];

		// default search
		$default = $this->conference_show_conferences_depending_on_selected_pages_show;
		foreach ( $default as $slug ) {
			$d_page = get_page_by_path($slug);
			$return["d"][$d_page->ID] = $d_page->post_title;
		}

		// custom search
		$pages_all = new WP_Query( empty($q) ? array(
			"sort_order" => "ASC",
			"post_type" => "page",
			'post_status' => 'publish',
			'posts_per_page' => -1,
		) : array(
			"sort_order" => "ASC",
			"post_type" => "page",
			'post_status' => 'publish',
			'posts_per_page' => -1,
			"s" => $q,
		) );
		$post_ids = wp_list_pluck( $pages_all->posts, 'ID' );
		foreach ( $post_ids as $post_id ) {
			$p = get_post($post_id);
			if ( $default === $p->post_name ) continue;
			if ( stripos($p->post_title, $q) === false || stripos($p->post_name, $q) === false ) continue;
			$return["c"][$p->ID] = $p->post_title;
		}

		echo json_encode($return);
		die();
	}


	/**
	 * Registers the demo post type.
	 * 
	 */
	public function CPT_meeting_search_for_places_search_action_handle() {
		$nonce = ( isset( $_POST[ 'nonce' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce , 'YANN-ajax-nonce' ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}

		// Variables
		$return = array( 
			"d" => array(),
			"c" => array(),
		);
		$error = array(
			"e" => array(),
		); // Donot use WP_Error here
		$q = $_POST['q'];
		
		// Default search
		$default = $this->conference_places_adding;
		foreach ( $default as $slug => $name ) {
			$return["d"][$slug] = $name;
		}

		// Custom search
		$q_list = explode($this->conference_places_split, $q);
		$q_name = $q_list[0];
		if ( intval(count($q_list)) !== 2 || ( intval(count($q_list)) === 2 && empty(trim($q_list[1])) ) ) {
			$error["e"][] = "請輸入 名稱".$this->conference_places_split."slug 格式。";
			echo json_encode($error);
			die();
		}
		$q_slug = $q_list[1];
		$db_option = get_option( $this->conference_places_adding_option );
		if ( array_key_exists($q_slug, $db_option) ) {
			$error["e"][] = "您輸入的 slug：".$q_slug." 已經被使用，請使用其他 slug 名稱。";
			echo json_encode($error);
			die();
		}
		if ( $this->specialCharacters( $q_slug ) ) {
			$error["e"][] = "您輸入的 slug：".$q_slug." 有英文字母和阿拉伯數字之外的字元。";
			echo json_encode($error);
			die();
		}
		if ( in_array($q_name, $db_option) ) {
			$error["e"][] = "您輸入的 名稱：".$q_name." 已經被使用，請使用其他名稱。";
			echo json_encode($error);
			die();
		}

		$return["c"][$q_slug] = $q_name;

		echo json_encode($return);
		die();
	}


	/**
	 * Function for ajax save options
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_search_for_conferences_settings_save_handle() {
		$nonce = ( isset( $_POST[ 'nonce' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce , 'YANN-ajax-nonce' ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}
		if ( !isset($_POST[ 'showoption' ]) || empty($_POST[ 'showoption' ]) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}
		$tmp = array_merge($this->conference_names_show_default, $this->conference_names_show);
		if ( !in_array(sanitize_text_field($_POST['showoption']), $tmp) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}
		if ( !isset($_POST[ 'showdppts' ]) || empty($_POST[ 'showdppts' ]) || !is_bool(filter_var($_POST[ 'showdppts' ], FILTER_VALIDATE_BOOLEAN))) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}

		$V = $this->YANN_NTNUSMIL_CPT_meeting_search_for_conferences_settings_save_call( $_POST );
		if ( array_key_exists( 'success' , $V ) ) {
			wp_send_json_success( $V );
		} else if ( array_key_exists( 'error' , $V ) ) {
			wp_send_json_error( $V );
			wp_die();
		} else {
			wp_send_json_error( array( 'error' => true , 'errmessage' => '哪裡怪怪的，請檢察程式碼修正錯誤' ) );
			wp_die();
		}
	}


	/**
	 * Function for YANN_NTNUSMIL_CPT_meeting_search_for_conferences_name_save_handle
	 * 
	 */
	public function YANN_NTNUSMIL_CPT_meeting_search_for_conferences_settings_save_call( $POST ) {

		// Variables
		global $wpdb;
		$all = array();
		$default = $this->conference_names_default;
		$table_name = $wpdb->prefix . $this->table_for_option_record;

		// Save Option
		update_option( $this->conference_names_show_option, array(sanitize_text_field($POST['showoption'])), '', 'no' );

		// Save Checkbox
		update_option( $this->conference_showing_depending_on_uploaded_ppts_option, sanitize_text_field(filter_var($POST[ 'showdppts' ], FILTER_VALIDATE_BOOLEAN)?$this->conference_showing_depending_on_uploaded_ppts:'no'), '', 'no' );


		/// *** Save Pages *** ///

		// default search
		$default_p_ids = array();
		foreach ( $this->conference_show_conferences_depending_on_selected_pages_show as $slug ) {
			$d_page = get_page_by_path($slug);
			$default_p_ids[] = $d_page->ID;
		}
		$_save_pages_ids = array_diff($POST[ 'savepageslist' ], $default_p_ids);
		update_option( $this->conference_show_conferences_depending_on_selected_pages_option, $_save_pages_ids, '', 'no');


		/// *** Save Places *** ///
		$default_pl_slugs = array_keys( $this->conference_places_adding );
		$_want_to_save = array();
		foreach( $POST[ 'saveplaceslist' ] as $s ) {
			$s_list = explode($this->conference_places_split, $s);
			if ( intval(count($s_list)) !== 2 || ( intval(count($s_list)) === 2 && empty(trim($s_list[1])) ) ) {
				continue;
			}
			if ( $this->specialCharacters( $s_list[1] ) ) {
				continue;
			}
			$_want_to_save[] = $s;
		}
		update_option( $this->conference_places_adding_option, $this->splitString2Dict($_want_to_save, $default_pl_slugs), '', 'no');


		/// *** Save Conferences *** ///

		// Search All
		$search_c = $wpdb->get_results( "SELECT * FROM $table_name" );
		if ( !empty($search_c) ) {
			foreach ( $search_c as $s_c ) {
				$all[] = $s_c->name;
			}
		}

		// Save List
		// When saving, we should save in insensitive case
		$_savelist = $POST['savelist'];
		foreach ( $_savelist as $_s ) {
			$data = array( 'name' => $_s );
			$data_format = array( '%s' );
			if (!in_array(strtoupper($_s),array_map('strtoupper',$all))) {
				// if being in default, not save in db.
				if (in_array(strtoupper($_s),array_map('strtoupper',$default))) {
					continue;
				}
				if ( !$wpdb->insert( $table_name , $data , $data_format ) ) {
					return array( 'error' => true , 'errmessage' => "database insert error." );
				}
			}
		}

		// Delete custom
		foreach ( $all as $a ) {
			$data = array( 'name' => $a );
			$data_format = array( '%s' );
			if (!in_array(strtoupper($a),array_map('strtoupper',$_savelist))) {
				// if being in default, not delete.
				if (in_array(strtoupper($a),array_map('strtoupper',$default))) {
					continue;
				}
				if ( !$wpdb->delete( $table_name , $data , $data_format ) ) {
					return array( 'error' => true , 'errmessage' => "database delete error." );
				}
			}
		}

		// Delete defaults
		foreach ( $default as $d ) {
			$data = array( 'name' => $d );
			$data_format = array( '%s' );
			if (in_array($d,$all)) {
				if ( !$wpdb->delete( $table_name , $data , $data_format ) ) {
					continue;
				}
			}
		}

		// Delete emtpy entry in db
		$search_e = $wpdb->get_results( "SELECT * FROM $table_name WHERE name = ''" );
		if ( !empty($search_e) ) {
			foreach ( $search_e as $e ) {
				$data = array( 'name' => $e->name );
				$data_format = array( '%s' );
				if ( !$wpdb->delete( $table_name , $data , $data_format ) ) {
						continue;
				}
			}
		}

		// Update _input_for_paper_conference_name in CPT meeting posts for changing upper lower case
		$_all = array_merge($all,$default);
		$_merge_all = array_unique($_all);
		$_cln = array_diff($_savelist, $_merge_all);
		if ( !empty($_cln) ) {
			// update default if needed
			$new_all = array_merge($_cln,$default);
			foreach( $new_all as $search ) {
				if ( !in_array($search,$_savelist) ) {
					continue;
				}
				$q_search = new WP_Query( array(
					'post_type' => array($this->posttype),
					'posts_per_page' => -1,
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'   => '_input_for_paper_conference_name',
							'value' => $search,
							'compare' => 'LIKE'
						)
					),
				));
				$post_ids = wp_list_pluck( $q_search->posts, 'ID' );
				foreach( $post_ids as $postid ) {
					$tmp = get_post_meta( $postid, '_input_for_paper_conference_name', true );
					if ( strval($tmp) === strval($search) ) {
						continue;
					}
					if ( strtoupper(strval($tmp)) === strtoupper(strval($search)) ) {
						update_post_meta( $postid , '_input_for_paper_conference_name' , $search );
					}
				}
			}
		}

		return array( 'success' => true , 'data' => 'Success.' );
	}


	/**
	 * Function for split string to array with keys and values
	 *
	 */
	private function splitString2Dict( $arr, $skip=array() ) {
		$rtn = array();
		foreach( $arr as $e ) {
			$n  = explode($this->conference_places_split, $e);
			$n_ = $n[0];
			$s_ = $n[1];
			if ( in_array($s_, $skip) ) {
				continue;
			}
			$rtn[$s_] = $n_;
		}
		return $rtn;
	}


	/**
	 * Function for check whether the string inputed is valid
	 *
	 */
	private function specialCharacters( $string ) {
		$rtn      = FALSE;
		$_S_ptn   = "/[_]/";
		$_E_N_ptn = "/[a-zA-Z0-9]/";
		for ($i = 0; $i < strlen($string); $i++){
			if ( ! preg_match($_E_N_ptn, $string[$i]) && ! preg_match($_S_ptn, $string[$i]) ) {
				$rtn = TRUE;
				break;
			}
		}
		return $rtn;
	}


	/**
	 * Function for generating table to recording uploaded files
	 *
	 */
	public function YANN_NTNUSMIL_CPT_meeting_options_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_for_option_record;
		if ( $this->YANN_NTNUSMIL_CPT_meeting_check_table_not_exists( $table_name ) === true ) {
			$charset_collate = $this->YANN_NTNUSMIL_CPT_meeting_get_charset_table() ;
			$sql             = "CREATE TABLE IF NOT EXISTS `$table_name` (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name VARCHAR(999) NOT NULL,
			UNIQUE KEY id (id)
			) $charset_collate;" ;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ) ;
			dbDelta( $sql ) ;
		}
	}

	/**
	 * Function for YANN_NTNUSMIL_CPT_meeting_uploaded_file_record_table
	 *
	 */
	public function YANN_NTNUSMIL_CPT_meeting_get_charset_table() {
		global $wpdb ;
		$charset_collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '' ;
		return $charset_collate ;
	}

	/**
	 * Create Table for Record download
	 *
	 */
	public function YANN_NTNUSMIL_CPT_meeting_check_table_not_exists( $table_name ) {
		global $wpdb ;
		$data_base     = constant( 'DB_NAME' ) ;
		$column_exists = $wpdb->query( "select * from information_schema.columns where table_schema='$data_base' and table_name = '$table_name'" ) ;
		if ( $column_exists === 0 ) {
			return true ;
		}
		return false ;
	}


	/**
	 * Filters the post states on the "Pages" edit page. Displays "Projects Page"
	 * after the post/page title, if the current page is the Projects static page.
	 *
	 * @param array $states
	 * @param WP_Post $post
	 */
	public function YANN_NTNUSMIL_CPT_meeting_pages_states( $states, $post ) {
		$_p_ids = array();
		foreach ( $this->conference_show_conferences_depending_on_selected_pages_show as $slug ) {
			$d_page = get_page_by_path($slug);
			$_p_ids[] = $d_page->ID;
		}
		foreach ( get_option( $this->conference_show_conferences_depending_on_selected_pages_option ) as $p_id ) {
			$_p_ids[] = $p_id;
		}

		if ( in_array($post->ID, $_p_ids) ) {
			$states['page_for_meeting'] = __( 'Meeting Page' );
		}
		return $states;
	}
}

