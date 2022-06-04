<?php
/**
 * Class: GLOBAL_CONTROL
 * Custom for global settings.
 * If want to run the class, remember pass "True" value as input to activate the class
 */

class GLOBAL_CONTROL {
	private $errors;

	/**
	 * This plugin's instance.
	 *
	 * @var GLOBAL_CONTROL
	 */
	private static $instance;
	private static $run;

	/**
	 * Registers the plugin.
	 */
	public static function register($run) {
		if ( $run === TRUE ) {
			if ( null === self::$instance ) {
				self::$instance = new GLOBAL_CONTROL();
				self::$run = $run;
			}
		}
	}

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->errors = False;
		$this->plugin = self::class; # @https://stackoverflow.com/questions/15103810/how-do-i-get-class-name-in-php
		$this->pageslug = "GLOBAL_CONTROL_options";
		$this->checked  = "yes";
		$this->unchecked = "no";
		$this->optiongroupname = "GLOBAL_CONTROL_option_group";
		$this->save_ = $this->pageslug."_settings_save";
		$this->html_target = $this->pageslug."_html_save_target";
		$this->GLOBAL_CONTROL_root_user_id = 1;
		$this->GLOBAL_CONTROL_options_dict = array(
			array(
				'section' => 'image',
				'items'   => array(
					array(
						'name' => __('自動剪裁圖片', 'domain'),
						'desp' => __('Remove scaled big size image', 'domain'),
						'func' => 'big_image_size_threshold',
					),
				),
			),
			array(
				'section' => 'security',
				'items'   => array(
					array(
						'name' => __('關閉 wlwmanifest link', 'domain'),
						'desp' => __('Remove wlwmanifest link', 'domain'),
						'func' => 'wlwmanifest_link',
					),
					array(
						'name' => __('關閉 XML-RPC', 'domain'),
						'desp' => __('Disable XML-RPC', 'domain'),
						'func' => 'disable_xmlrpc',
					),
					array(
						'name' => __('關閉 link tag', 'domain'),
						'desp' => __('Remove the value of rel attribute pingbak for link tag', 'domain'),
						'func' => 'close_xmlrpc_pingback',
					),
					array(
						'name' => __('關閉 XML-RPC RSD link', 'domain'),
						'desp' => __('Disable XML-RPC RSD link from WordPress Header', 'domain'),
						'func' => 'rsd_link',
					),
					array(
						'name' => __('關閉 xmlrcp/pingback', 'domain'),
						'desp' => __('Disable xmlrcp/pingback', 'domain'),
						'func' => 'xmlrpcpingback',
					),
					array(
						'name' => __('強制 uncheck ping pingback', 'domain'),
						'desp' => __('Force to uncheck pingbck and trackback options', 'domain'),
						'func' => 'uncheckpingandpingback',
					),
					array(
						'name' => __('關閉 pingback.ping', 'domain'),
						'desp' => __('Just disable pingback.ping functionality while leaving XMLRPC intact', 'domain'),
						'func' => 'closepingbackping',
					),
					array(
						'name' => __('隱藏 XMLRPC 選項', 'domain'),
						'desp' => __('Hide XMLRPC options on the discussion page', 'domain'),
						'func' => 'hide_xmlrpc',
					),
					array(
						'name' => __('隱藏 WooComerce Version', 'domain'),
						'desp' => __('Remove WooCommerce version', 'domain'),
						'func' => 'remove_woo_version',
					),
					array(
						'name' => __('隱藏 WordPress Version', 'domain'),
						'desp' => __('Clean meta generator for WordPress core', 'domain'),
						'func' => 'remove_wp_version',
					),
					array(
						'name' => __('移除 meta generators', 'domain'),
						'desp' => __('Remove all the meta generators', 'domain'),
						'func' => 'clean_meta_generators',
					),
					array(
						'name' => __('移除 short link', 'domain'),
						'desp' => __('Remove Short Link', 'domain'),
						'func' => 'shortlink_header',
					),
					array(
						'name' => __('移除 api.w.org 相關的網址', 'domain'),
						'desp' => __('Remove api.w.org relation link', 'domain'),
						'func' => 'apiworg_restremove',
					),
					array(
						'name' => __('隱藏 Feed Urls', 'domain'),
						'desp' => __('Hide the urls of feed', 'domain'),
						'func' => 'hide_feed_links',
					),
					array(
						'name' => __('隱藏 Author 的個人資訊', 'domain'),
						'desp' => __('Avoid anoumyous people get the information of author', 'domain'),
						'func' => 'protect_author_get',
					),
					array(
						'name' => __('移除 XFN link', 'domain'),
						'desp' => __('Remove XFN (XHTML Friends Network) links', 'domain'),
						'func' => 'remove_xfnlink',
					),
				),
			),
			array(
				'section' => 'resource',
				'items'   => array(
					array(
						'name' => __('關閉 emojis', 'domain'),
						'desp' => __('Disable emojis', 'domain'),
						'func' => 'disable_emojis',
					),
					array(
						'name' => __('移除 comments style', 'domain'),
						'desp' => __('Remove Recent Comments Style', 'domain'),
						'func' => 'remove_recent_comments_style',
					),
					array(
						'name' => __('移除 urls 當中的 query', 'domain'),
						'desp' => __('Remove query in urls', 'domain'),
						'func' => 'remove_query_strings',
					),
					array(
						'name' => __('移除 Jquery Migrate', 'domain'),
						'desp' => __('Remove jquery migrate', 'domain'),
						'func' => 'remove_jquery_migrate',
					),
					array(
						'name' => __('加入 Jquery', 'domain'),
						'desp' => __('Add jquery if not exist', 'domain'),
						'func' => 'load_jquery',
					),
					array(
						'name' => __('移除 Open Sans', 'domain'),
						'desp' => __('Remove Open Sans which WP already add at frontend', 'domain'),
						'func' => 'remove_open_sans',
					),
					array(
						'name' => __('加入客製化的 Logo', 'domain'),
						'desp' => __('Hook into the administrative header output', 'domain'),
						'func' => 'custom_admin_logo_adding',
					),
					array(
						'name' => __('加入客製化的 Global CSS', 'domain'),
						'desp' => __('Global CSS', 'domain'),
						'func' => 'global_css',
					),
					array(
						'name' => __('移除 urls 當中的 Global Javascript', 'domain'),
						'desp' => __('Global Javascript', 'domain'),
						'func' => 'global_enqueue_script',
					),
				),
			),
			array(
				'section' => 'dashboard',
				'items'   => array(
					array(
						'name' => __('(TABOR Theme) 移除 dashborad button', 'domain'),
						'desp' => __('Remove tabor dashboard button', 'domain'),
						'func' => 'remove_tabor_help_button',
					),
					array(
						'name' => __('移除 dashborad metaboxes', 'domain'),
						'desp' => __('Remove dashboard metaboxes', 'domain'),
						'func' => 'disable_metaboxes',
					),
					array(
						'name' => __('移除 dashborad footer 的文字', 'domain'),
						'desp' => __('Remove dashboard footer texts', 'domain'),
						'func' => 'disable_metaboxes_footer_texts',
					),
				),
			),

			array(
				'section' => 'notices',
				'items'   => array(
					array(
						'name' => __('移除所有 dashborad 訊息', 'domain'),
						'desp' => __('Remove all dashboard messages', 'domain'),
						'func' => 'disable_admin_notices',
					),
				),
			),

			array(
				'section' => 'fronted',
				'items'   => array(
					array(
						'name' => __('隱藏 ', 'domain'),
						'desp' => __('Hide admin bar front always', 'domain'),
						'func' => 'global_admin_bar_front_hide',
					),
				),
			),

			array(
				'section' => 'convinient_functions',
				'items'   => array(
					array(
						'name' => __('移除 random post links', 'domain'),
						'desp' => __('Remove random post links', 'domain'),
						'func' => 'start_post_rel_link',
					),
					array(
						'name' => __('移除 parent post links', 'domain'),
						'desp' => __('Remove parent post links', 'domain'),
						'func' => 'parent_post_rel_link',
					),
					array(
						'name' => __('移除前後頁的 links', 'domain'),
						'desp' => __('Remove the links of the previous and next page', 'domain'),
						'func' => 'disable_metaboxes_footer_texts',
					),
					array(
						'name' => __('停止猜測搜尋到文章的連結', 'domain'),
						'desp' => __('Stop geussing the url of the posts', 'domain'),
						'func' => 'stop_guessing_url',
					),
					array(
						'name' => __('當搜尋結果只有一篇文章時自動進入到該文章', 'domain'),
						'desp' => __('Return the only post when search result has only one', 'domain'),
						'func' => 'redirect_single_post',
					),
				),
			),
		);
		$this->default_uncheck = array(
			'remove_jquery_migrate',
			'disable_admin_notices'
		);
		if (self::$run) {
			$this->add_hooks();
		}
	}

	/**
	 * Register actions and filters.
	 */
	// @https://www.cssigniter.com/how-to-add-custom-fields-to-the-wordpress-registration-form/
	public function add_hooks() {

		/* Register the control menu */
		add_action( 'admin_menu', array( $this, 'GLOBAL_CONTROL_options_page_register' ) );
		add_action( 'admin_init', array( $this, 'GLOBAL_CONTROL_options_page_setting' ) );
		add_action( 'admin_enqueue_scripts' , array( $this, 'GLOBAL_CONTROL_options_admin_enqueue_scripts' ) , 20 , 1 );
		add_action( 'wp_ajax_'.$this->save_.'_action', array( $this, $this->save_.'_handle' ) );
	}

	/**
	 * Registers option page for global options.
	 * 
	 */
	public function GLOBAL_CONTROL_options_page_register() {

		add_submenu_page(
			'options-general.php',
			'網站最佳化設定', // page <title>Title</title>
			'網站最佳化設定', // menu link text
			'manage_options', // capability to access the page
			$this->pageslug, // page URL slug
			array( $this , 'GLOBAL_CONTROL_options_page_content' ), // callback function /w content
		);
	}

	/**
	 * Registers field in option page.
	 * 
	 */
	public function GLOBAL_CONTROL_options_page_content(){

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
	public function GLOBAL_CONTROL_options_page_setting(){
	
		$pageslug = $this->pageslug;
		$optiongroupname = $this->optiongroupname;
		$option_dict = json_decode(json_encode($this->GLOBAL_CONTROL_options_dict), FALSE);

		register_setting(
			$optiongroupname, // settings group name
			$pageslug, // option name
			'sanitize_text_field' // sanitization function
		);
		
		foreach($option_dict as $section){

			$section_id = $pageslug."_".$section->section;

			add_settings_section(
				$section_id, // section ID
				'一般設定', // title (if needed)
				'', // callback function (if needed)
				$pageslug // page slug
			);

			foreach($section->items as $field) {

				$id = $section_id."_".$field->func;

				// Generate options in db
				if ( !get_option( $id, false ) ) {
					if ( !in_array( $field->func, $this->default_uncheck ) ) {
						update_option( $id, $this->checked, '', 'no' );
					} else {
						update_option( $id, $this->unchecked, '', 'no' );
					}
				}

				add_settings_field(
					$id."_field",
					$field->name,
					array($this, 'GLOBAL_CONTROL_options_checkbox_html'),
					$pageslug,
					$section_id,
					array( 
						'type'         => 'checkbox',
						'option_name'  => $id,
						'label_for'    => $id,
						'id'           => $id,
						'description'  => $field->desp,
						'tip'          => $field->desp,
						)
				);
			}
		}
	}


	/**
	 * Function for GLOBAL_CONTROL_options_page_setting
	 * 
	 */
	public function GLOBAL_CONTROL_options_checkbox_html($args) {

		$db_option = get_option($args["option_name"]);

		$html = '<input type="checkbox" ' . checked($db_option, 'yes', false) . ' id=' . $args["id"] . ' name=' . $args["id"] . ' class=' . $this->html_target . ' />';

		echo $html;

	}


	/**
	 * Enqueue scripts for option page of meeting post type.
	 * 
	 * @param object $hook, which admin page
	 */
	public function GLOBAL_CONTROL_options_admin_enqueue_scripts( $hook ) {
		if ( ! in_array( $hook , array("settings_page_".$this->pageslug) ) ) {
			return;
		}
		// wp_die($hook);
		wp_enqueue_style( 'admin-global-control-option-css' , plugins_url( '', dirname( __FILE__ ) ) . '/includes/css/admin/admin-global-control-option-css.css' );
		wp_enqueue_script( 'admin-global-control-option-js' , plugins_url( '', dirname( __FILE__ ) ) . '/includes/js/admin/admin-global-control-option-js.js' , array() , time() , true );
		wp_localize_script( 'admin-global-control-option-js' , $this->plugin , array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ) ,
			'nonce'   => wp_create_nonce( 'YANN-ajax-nonce' ) ,
			'action_save' => $this->save_.'_action',
			'save_target' => $this->html_target,
		));
	}


	/**
	 * Function for ajax save options
	 * 
	 */
	public function GLOBAL_CONTROL_options_settings_save_handle() {
		$nonce = ( isset( $_POST[ 'nonce' ] ) ) ? wc_clean( wp_unslash( $_POST[ 'nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce , 'YANN-ajax-nonce' ) ) {
			wp_send_json_error( array( 'error' => true , 'errmessage' => 'Missing parameters' ) );
			wp_die();
		}

		$V = $this->GLOBAL_CONTROL_options_settings_save_call( $_POST );
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
	 * Function for GLOBAL_CONTROL_options_settings_save_handle
	 * 
	 */
	public function GLOBAL_CONTROL_options_settings_save_call( $POST ) {

		// Variables
		global $wpdb;

		// Save Checkbox
		$pageslug = $this->pageslug;
		$option_dict = json_decode(json_encode($this->GLOBAL_CONTROL_options_dict), FALSE);
		// error_log(json_encode($POST), 0);

		foreach($option_dict as $section){
			$section_id = $pageslug."_".$section->section;

			foreach($section->items as $field) {

				$id  = $section_id."_".$field->func;
				$val = $POST["savelist"][$id];
				update_option( $id, sanitize_text_field(filter_var($val, FILTER_VALIDATE_BOOLEAN)?$this->checked:$this->unchecked), '', 'no' );
				

			}
		}

		return array( 'success' => true , 'data' => 'Success.' );
	}
	
}

GLOBAL_CONTROL::register(TRUE);
