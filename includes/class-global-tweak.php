<?php
/**
 * Class: GLOBAL_TWEAK_GLOBAL
 * Custom for global settings.
 */

require_once( 'class-global-control.php' );
use \GLOBAL_CONTROL as GLOBAL_CONTROL; # @https://stackoverflow.com/questions/9317022/troubleshooting-the-use-statement-with-non-compound-name-has-no-effect, https://stackoverflow.com/questions/8610729/cannot-find-class-with-php-namespace

class GLOBAL_TWEAK {
	private $errors;

	/**
	 * This plugin's instance.
	 *
	 * @var GLOBAL_TWEAK
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new GLOBAL_TWEAK();
		}
	}


	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->errors = False;
		$this->root_user_arr = array(1);
		$this->add_hooks();
	}

	/**
	 * Register actions and filters.
	 */
	// @https://www.cssigniter.com/how-to-add-custom-fields-to-the-wordpress-registration-form/
	public function add_hooks() {
		$object = new GLOBAL_CONTROL(FALSE);
		$prefix = $object->pageslug;
		$checked = $object->checked;
		
		// *** IMAGE *** //
		/* remove scaled big size image */
		if (get_option($prefix."_image_"."big_image_size_threshold") === $checked){
			add_filter( 'big_image_size_threshold', '__return_false' );
		}

		// *** SECUTIRY *** ///
		/* Remove wlwmanifest link */
		if (get_option($prefix."_security_"."wlwmanifest_link") === $checked){
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}

		/* Disable XML-RPC */
		if (get_option($prefix."_security_"."disable_xmlrpc") === $checked){
			add_filter( 'template_redirect' , array( $this, 'GLOBAL_TWEAK_remove_XmlRpc_Pingback_Headers' ) );
			add_filter( 'wp_headers' , array( $this, 'GLOBAL_TWEAK_disable_XmlRpc_Pingback' ) );
		}

		/* Remove link tag with the attribute value pingbak of rel */
		if (get_option($prefix."_security_"."close_xmlrpc_pingback") === $checked){
			add_action( 'template_redirect' , array( $this, 'GLOBAL_TWEAK_remove_XmlRpc_Tag_Buffer_Start' ) , -1 );
			add_action( 'get_header' , array( $this, 'GLOBAL_TWEAK_remove_XmlRpc_Tag_Buffer_Start' ) );
			add_action( 'wp_head' , array( $this, 'GLOBAL_TWEAK_remove_XmlRpc_Tag_Buffer_End' ) );
		}

		/* Disable XML-RPC RSD link from WordPress Header */
		if (get_option($prefix."_security_"."rsd_link") === $checked){
			remove_action( 'wp_head' , 'rsd_link' );
		}

		/* Disable xmlrcp/pingback */
		if (get_option($prefix."_security_"."xmlrpcpingback") === $checked){
			add_filter( 'xmlrpc_enabled' , '__return_false' );
			add_filter( 'pre_update_option_enable_xmlrpc' , '__return_false' );
			add_filter( 'pre_option_enable_xmlrpc' , '__return_zero' );
			add_filter( 'pings_open' , '__return_false');
		}

		/* Force to uncheck pingbck and trackback options */
		if (get_option($prefix."_security_"."uncheckpingandpingback") === $checked){
			add_filter( 'pre_option_default_ping_status' , '__return_zero' );
			add_filter( 'pre_option_default_pingback_flag' , '__return_zero' );
		}

		/* Just disable pingback.ping functionality while leaving XMLRPC intact */
		if (get_option($prefix."_security_"."closepingbackping") === $checked){
			add_filter( 'xmlrpc_methods' , array( $this, 'GLOBAL_TWEAK_remove_XmlRpc_Methods' ) , 10 , 1 );
			add_action( 'xmlrpc_call' , array( $this, 'GLOBAL_TWEAK_disable_XmlRpc_Call' ) , 10 , 1 );
		}

		/* Hide XMLRPC options on the discussion page */
		if (get_option($prefix."_security_"."hide_xmlrpc") === $checked){
			$this->GLOBAL_TWEAK_xmlRpc_Set_Disabled_Header();
			add_action( 'admin_enqueue_scripts' , array( $this, 'GLOBAL_TWEAK_remove_XmlRpc_Hide_Options' ) , 10 , 1 );
		}
	
		/* remove WooCommerce version */
		if (get_option($prefix."_security_"."remove_woo_version") === $checked){
			if ( class_exists( 'WooCommerce' ) ) {
				remove_action( 'wp_head' , 'woo_version' );
			}
		}
		
		/* Clean meta generator for WordPress core */
		if (get_option($prefix."_security_"."remove_wp_version") === $checked){
			remove_action( 'wp_head' , 'wp_generator' );
			add_filter( 'the_generator' , '__return_empty_string' );
		}

		/* remove all the meta generator */
		if (get_option($prefix."_security_"."clean_meta_generators") === $checked){
			add_action( 'wp_head' , array( $this, 'GLOBAL_TWEAK_clean_meta_generators' ) , 100 , 0 );
		}

		/* Remove Short Link */
		if (get_option($prefix."_security_"."shortlink_header") === $checked){
			remove_action( 'wp_head' , 'wp_shortlink_wp_head' );
			remove_action( 'template_redirect' , 'wp_shortlink_header' , 11 );
		}

		/* Remove api.w.org relation link */
		if (get_option($prefix."_security_"."apiworg_restremove") === $checked){
			remove_action( 'wp_head' , 'rest_output_link_wp_head' , 10 );
			remove_action( 'wp_head' , 'wp_oembed_add_discovery_links' , 10 );
			remove_action( 'template_redirect' , 'rest_output_link_header' , 11 , 0 );
		}

		/* Hide the urls of feed */
		if (get_option($prefix."_security_"."hide_feed_links") === $checked){
			remove_action( 'wp_head', 'feed_links', 2 );
			remove_action( 'wp_head', 'feed_links_extra' , 3 );
		}

		/* Avoid anoumyous people get the information of author */
		if (get_option($prefix."_security_"."protect_author_get") === $checked){
			add_action( 'wp' , array( $this , 'GLOBAL_TWEAK_protect_Author_Get' ) );
		}

		/* remove XFN (XHTML Friends Network) links */
		if (get_option($prefix."_security_"."remove_xfnlink") === $checked){
			if ( ! is_admin() ) {
				add_filter( 'avf_profile_head_tag' , array( $this , 'GLOBAL_TWEAK_remove_XfnLink' ) );
			}
		}

		// *** RESOURCE *** //
		/* Disable emojis */
		if (get_option($prefix."_resource_"."disable_emojis") === $checked){
			add_action( 'init', array( $this , 'GLOBAL_TWEAK_disable_emojis' ) );
		}

		/* Remove Recent Comments Sytle */
		if (get_option($prefix."_resource_"."remove_recent_comments_style") === $checked){
			add_action( 'widgets_init' , array( $this , 'GLOBAL_TWEAK_remove_Recent_Comments_Style' ) );
		}

		/* remove query in urls */
		if (get_option($prefix."_resource_"."remove_query_strings") === $checked){
			add_action( 'init' , array( $this, 'GLOBAL_TWEAK_remove_query_strings' ) );
			add_action( 'init' , array( $this , 'GLOBAL_TWEAK_remove_query_strings_split' ) , 10 , 1 );
		}

		/* remove jquery migrate */
		if (get_option($prefix."_resource_"."remove_jquery_migrate") === $checked){
			// add_action( 'wp_default_scripts' , array( $this , 'GLOBAL_TWEAK_remove_jquery_migrate' ) , 10 , 1 );
		}

		/* add jquery if not exist */
		if (get_option($prefix."_resource_"."load_jquery") === $checked){
			add_action( 'wp_enqueue_scripts', array( $this, 'GLOBAL_TWEAK_load_jquery' ) , 10, 0 );
		}

		/* Remove Open Sans which WP already add at frontend */
		if (get_option($prefix."_resource_"."remove_open_sans") === $checked){
			add_action( 'init' , array( $this , 'GLOBAL_TWEAK_remove_open_sans' ) , 10 , 0 );
		}

		/* hook into the administrative header output */
		if (get_option($prefix."_resource_"."custom_admin_logo_adding") === $checked){
			add_action( 'admin_bar_menu', array( $this , 'GLOBAL_TWEAK_custom_admin_logo_adding' ) , 1 , 1 );
		}

		/* Global CSS */
		if (get_option($prefix."_resource_"."global_css") === $checked){
			add_action( 'wp_footer', array( $this , 'GLOBAL_TWEAK_global_css' ) , 10 , 0 );
		}

		/* Global Javascript */
		if (get_option($prefix."_resource_"."global_enqueue_script") === $checked){
			add_action( 'wp_enqueue_scripts' , array( $this , 'GLOBAL_TWEAK_global_enqueue_script' ) , 15 , 0 );
		}

		// *** Dashboard *** //
		/* remove tabor dashboard button */
		if (get_option($prefix."_dashboard_"."remove_tabor_help_button") === $checked){
			if ( ! defined( 'TABOR_DEBUG' ) ) {
				add_action('admin_enqueue_scripts', array( $this , 'GLOBAL_TWEAK_remove_tabor_help_button' ) , 20 , 1 );
			}
		}

		/* Remove dashboard metaboxes */
		if (get_option($prefix."_dashboard_"."disable_metaboxes") === $checked){
			add_action( 'wp_dashboard_setup', array( $this , 'GLOBAL_TWEAK_disable_metaboxes' ) , 20 , 0 );
		}

		/* Remove dashboard footer texts */
		if (get_option($prefix."_dashboard_"."disable_metaboxes_footer_texts") === $checked){
			add_filter( 'screen_options_show_screen' , array( $this , 'GLOBAL_TWEAK_remove_help_tabs' ) );
			add_action( 'admin_head' , array( $this , 'GLOBAL_TWEAK_remove_admin_tabs' ) );
			add_filter( 'admin_footer_text' , array( $this , 'GLOBAL_TWEAK_remove_footer_admin' ) , 10 , 0 );
			add_filter( 'admin_footer_text' , '__return_empty_string', 13 ); 
			add_filter( 'update_footer' , '__return_empty_string', 13 );
		}

		// *** NOTICES *** //
		/* Remove all dashboard messages */
		if (get_option($prefix."_notices_"."disable_admin_notices") === $checked){
			add_action( 'admin_print_scripts' , array( $this , 'GLOBAL_TWEAK_disable_admin_notices' ) , 10 , 0 );
		}

		// *** FRONTED *** //
		/* Hide admin bar front always */
		if (get_option($prefix."_fronted_"."global_admin_bar_front_hide") === $checked){
			add_action( 'init' , array( $this , 'GLOBAL_TWEAK_global_admin_bar_front_hide' ) , 10 , 0 );
		}

		// *** CONVINIENT FUNCTIONS *** //
		/* remove random post link */
		if (get_option($prefix."_convinient_functions_"."start_post_rel_link") === $checked){
			remove_action( 'wp_head' , 'start_post_rel_link' , 10 , 0);
		}

		/* remove parent post link */
		if (get_option($prefix."_convinient_functions_"."parent_post_rel_link") === $checked){
			remove_action( 'wp_head' , 'parent_post_rel_link', 10 , 0);
		}

		/* remove the links of the previous and next page */
		if (get_option($prefix."_convinient_functions_"."disable_metaboxes_footer_texts") === $checked){
			remove_action( 'wp_head' , 'adjacent_posts_rel_link' , 10 , 0 );
			remove_action( 'wp_head' , 'adjacent_posts_rel_link_wp_head' , 10 , 0 );
		}

		/* Stop geussing the url of the posts */
		if (get_option($prefix."_convinient_functions_"."stop_guessing_url") === $checked){
			add_filter( 'redirect_canonical' , array( $this , 'GLOBAL_TWEAK_stop_guessing_url' ) , 10 , 1 );
		}

		/* Return the only post when search result has only one */
		if (get_option($prefix."_convinient_functions_"."redirect_single_post") === $checked){
			add_action( 'template_redirect' , array( $this , 'GLOBAL_TWEAK_redirect_single_post' ) );
		}
	}

	// *** SECUTIRY *** ///

	/**
	 * Disable XML-RPC
	 * 
	 */
	public function GLOBAL_TWEAK_remove_XmlRpc_Pingback_Headers() {
		if( function_exists('header_remove') ) {
			header_remove('X-Pingback');
			header_remove('Server');
		}
	}

	/**
	 * Disable XML-RPC
	 * 
	 */
	public function GLOBAL_TWEAK_disable_XmlRpc_Pingback( $headers ) {
		unset($headers['X-Pingback']);
		return $headers;
	}

	/**
	 * link tag with the attribute value pingbak of rel
	 * 
	 */
	public function GLOBAL_TWEAK_remove_XmlRpc_Tag_Buffer_Start() {
		ob_start( array( $this , "GLOBAL_TWEAK_remove_XmlRpc_Tag" ) );
	}

	/**
	 * link tag with the attribute value pingbak of rel
	 * 
	 */
	public function GLOBAL_TWEAK_remove_XmlRpc_Tag($buffer) {
		preg_match_all('/(<link([^>]+)rel=("|\')pingback("|\')([^>]+)?\/?>)/im', $buffer, $founds);
	
		if( !isset($founds[0]) || count($founds[0]) < 1 ) return $buffer;
	
		if( count($founds[0]) > 0 ) {
			foreach($founds[0] as $found) {
				if( empty($found) ) {
					continue;
				}
	
				$buffer = str_replace($found, "", $buffer);
			}
		}
	
		return $buffer;
	}

	/**
	 * link tag with the attribute value pingbak of rel
	 * 
	 */
	public function GLOBAL_TWEAK_remove_XmlRpc_Tag_Buffer_End() {
		ob_flush();
	}

	/**
	 * Just disable pingback.ping functionality while leaving XMLRPC intact
	 * 
	 */
	public function GLOBAL_TWEAK_remove_XmlRpc_Methods( $methods ) {
		unset($methods['pingback.ping']);
		unset($methods['pingback.extensions.getPingbacks']);
		unset($methods['wp.getUsersBlogs']);
		unset($methods['system.multicall']);
		unset($methods['system.listMethods']);
		unset($methods['system.getCapabilities']);
		return $methods;
	}

	/**
	 * Just disable pingback.ping functionality while leaving XMLRPC intact
	 * 
	 */
	public function GLOBAL_TWEAK_disable_XmlRpc_Call( $method ) {
		if ( $method != 'pingback.ping' ) {
			return;
		}
		wp_die('This site does not have pingback.', 'Pingback not Enabled!', array('response' => 403));
	}

	/**
	 * Hide XMLRPC options on the discussion page
	 * 
	 */
	public function GLOBAL_TWEAK_remove_XmlRpc_Hide_Options($hook) {
		if ( 'options-discussion.php' !== $hook ) {
			return;
		}
		wp_add_inline_style('dashboard', '.form-table td label[for="default_pingback_flag"], .form-table td label[for="default_pingback_flag"] + br, .form-table td label[for="default_ping_status"], .form-table td label[for="default_ping_status"] + br { display: none; }');
	}

	/**
	 * Hide XMLRPC options on the discussion page
	 * 
	 */
	public function GLOBAL_TWEAK_xmlRpc_Set_Disabled_Header() {
		// Return immediately if SCRIPT_FILENAME not set
		if( !isset($_SERVER['SCRIPT_FILENAME']) ) {
			return;
		}
	
		$file = basename($_SERVER['SCRIPT_FILENAME']);
	
		// Break only if xmlrpc.php file was requested.
		if( 'xmlrpc.php' !== $file ) {
			return;
		}
	
		$header = 'HTTP/1.1 403 Forbidden';
	
		header($header);
		echo $header;
		die();
	}

	/**
	 * remove all the meta generator
	 * 
	 */
	public function GLOBAL_TWEAK_clean_meta_generators() {
		ob_start( array( $this , 'GLOBAL_TWEAK_replace_meta_generators' ) );
	}

	/**
	 * remove all the meta generator
	 * 
	 */
	public function GLOBAL_TWEAK_replace_meta_generators( $html ) {
		$raw_html = $html;
	
		$pattern = '/<meta[^>]+name=["\']generator["\'][^>]+>/i';
		$html    = preg_replace( $pattern, '', $html );
	
		// If replacement is completed with an error, user will receive a white screen.
		// We have to prevent it.
		if ( empty( $html ) ) {
			return $raw_html;
		}
	
		return $html;
	}

	/**
	 * Avoid anoumyous people get the information of author
	 * 
	 */
	public function GLOBAL_TWEAK_protect_Author_Get() {
		if( isset( $_GET[ 'author' ] ) ) {
			wp_redirect( home_url() , 301 ) ;
			die();
		}
	}

	/**
	 * Avoid anoumyous people get the information of author
	 * 
	 */
	public function GLOBAL_TWEAK_remove_XfnLink() {
		return false;
	}

	// *** RESOURCE *** //

	/**
	 * Disable emojis
	 * 
	 */
	public function GLOBAL_TWEAK_disable_emojis() {
		remove_action( 'wp_head' , 'print_emoji_detection_script' , 7 );
		remove_action( 'admin_print_scripts' , 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles' , 'print_emoji_styles' );
		remove_action( 'admin_print_styles' , 'print_emoji_styles' );
		remove_filter( 'the_content_feed' , 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss' , 'wp_staticize_emoji' );
		remove_filter( 'wp_mail' , 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url' , '__return_false' );
		add_filter( 'tiny_mce_plugins' , array( $this, 'GLOBAL_TWEAK_disable_Emojis_Tinymce' ) , 10 , 1 );
		add_filter( 'wp_resource_hints' , array( $this, 'GLOBAL_TWEAK_disable_Emojis_Remove_Dns_Prefetch' ) , 10 , 2 );
	}

	/**
	 * Disable emojis
	 * 
	 */
	public function GLOBAL_TWEAK_disable_Emojis_Tinymce( $plugins ) {
		return ( is_array( $plugins ) ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
	}

	/**
	 * Disable emojis
	 * 
	 */
	public function GLOBAL_TWEAK_disable_Emojis_Remove_Dns_Prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' == $relation_type ) {
			// Strip out any URLs referencing the WordPress.org emoji location
			$emoji_svg_url_bit = 'https://s.w.org/images/core/emoji/';
			foreach ( $urls as $key => $url ) {
				if ( strpos( $url, $emoji_svg_url_bit ) !== false ) {
					unset( $urls[ $key ] );
				}
			}
		}
		return $urls;
	}

	/**
	 * Remove Recent Comments Sytle
	 * 
	 */
	public function GLOBAL_TWEAK_remove_Recent_Comments_Style() {
		global $wp_widget_factory;
	
		$widget_recent_comments = isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ? $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] : null;
	
		if ( ! empty( $widget_recent_comments ) ) {
			remove_action( 'wp_head', [
				$wp_widget_factory->widgets['WP_Widget_Recent_Comments'],
				'recent_comments_style'
			] );
		}
	}
	
	/**
	 * remove query in urls
	 * 
	 * @https://kinsta.com/knowledgebase/remove-query-strings-static-resources/
	 * @https://www.sourcewp.com/remove-query-strings-static-resources/
	 * @https://yungke.me/how-remove-query-string-from-url/
	 * 
	 */
	public function GLOBAL_TWEAK_remove_query_strings() {
		if ( !is_admin() ) {
			add_filter( 'script_loader_src' , array( $this , 'GLOBAL_TWEAK_remove_query_strings_split' ) , 15, 1 );
			add_filter( 'style_loader_src' , array( $this , 'GLOBAL_TWEAK_remove_query_strings_split' ) , 15 , 1 );
		}
	}

	/**
	 * remove query in urls
	 * 
	 * @https://kinsta.com/knowledgebase/remove-query-strings-static-resources/
	 * @https://www.sourcewp.com/remove-query-strings-static-resources/
	 * @https://yungke.me/how-remove-query-string-from-url/
	 * 
	 */
	public function GLOBAL_TWEAK_remove_query_strings_split ( $src ) {
		$output = preg_split("/(&ver|\?ver|\?x)/", $src);
		//$output = explode( '?', $src ); 
		return $output[0];
	}

	/**
	 * remove jquery migrate
	 * 
	 * @https://www.narga.net/how-to-remove-jquery-migrate/
	 * -- close due to causing fronted meanu bar being not clickable
	 * 
	 */
	public function GLOBAL_TWEAK_remove_jquery_migrate( $scripts ) {
		if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
			$script = $scripts->registered['jquery'];

			// Check whether the script has any dependencies
			if ( $script->deps ) {
				$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
			}
		}
	}

	/**
	 * add jquery if not exist
	 * 
	 * @https://wordpress.stackexchange.com/questions/25273/check-if-jquery-library-exist
	 * 
	 */
	public function GLOBAL_TWEAK_load_jquery() {
		if ( ! wp_script_is( 'jquery', 'enqueued' )) {
	
			// Enqueue
			wp_enqueue_script( 'jquery' , 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js' , array() , false , true );
	
		}
	}

	/**
	 * Remove Open Sans which WP already add at frontend
	 * 
	 */
	public function GLOBAL_TWEAK_remove_open_sans() {
		wp_deregister_style( 'open-sans' );
		wp_register_style( 'open-sans' , false );
		wp_enqueue_style( 'open-sans' , '' );
	}

	/**
	 * hook into the administrative header output
	 * 
	 */
	public function GLOBAL_TWEAK_custom_admin_logo_adding( $wp_admin_bar ) {
		$custom_logo_url = wp_upload_dir()['baseurl'] . '/A00_@ntnusmil_homeoage-logo@_mod.png';
		$args = array(
			'id'    => 'custom_logo_admin',
			'title' => '&nbsp;',
			'meta'  => array( 'html' => '<li id="custom-logo" class="menupop"><a class="ab-item" aria-haspopup="true" href="'.home_url().'"><span class="ab-icon" style="position:relative;height:25px;width:20px;"><img style="height:inherit" src="'.$custom_logo_url.'" /></span><span class="screen-reader-text">關於</span></a></li>' )
		);
		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Global CSS
	 * 
	 * @https://stackoverflow.com/questions/7717378/how-can-you-vertically-align-multi-line-text-within-a-list
	 * 
	 */
	function GLOBAL_TWEAK_global_css() {
		if ( is_page() ) {
		?><style>
			.has-sidebar .site-content{margin:0 auto;max-width:100%;}
					</style><?php
				}
				if (is_page('contact')) {
					?><style>
			.site-footer{display:none}
					</style><?php
				} else {
					?><style>
			@media(max-width:1423px){.site-footer{display:block}}
			@media(min-width:1424px){.site-footer{display:none}}
					</style><?php
				}
				?><style>
			@media(max-width:1423px){.site-header .social-navigation,.sep{display:none!important}}
			@media(max-width:768px){h1.h3.site-title.site-logo,span.site-header.sep{display:none!important}}
			.site-content{padding-top:0em}
			.social-box.top-bar-right {line-height:20px;margin-top:-10px;}
			.social-box ul {display: table;border-collapse: collapse;width: 100%;}
			.social-box li {display: table-row;}
			.fa-envelope::before {content:"\f0e0";box-sizing:border-box;color:#393A10;}
			.fa-phone::before {content:"\f095";box-sizing:border-box;color:#393A10;}
			.fa-fax::before {content:"\f1ac";box-sizing:border-box;color:#393A10;}
			.fa-address-book::before {content:"\f2b9";box-sizing:border-box;color:#393A10;}
			.clr:after {content: "";display: table;clear: both;}
		</style><?php
	}

	/**
	 * Global Javascript
	 * 
	 */
	public function GLOBAL_TWEAK_global_enqueue_script() {
		wp_enqueue_style( 'fontawesomecss' , 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css' , array() , false , 'all' );
	}


	// *** Dashboard *** //

	/**
	 * remove tabor dashboard button
	 * 
	 */
	public function GLOBAL_TWEAK_remove_tabor_help_button( $hook ) {
		wp_add_inline_style( 'themebeans-dashboard-doc' , '.huh-launcher{display:none!important}' );
	}

	/**
	 * Remove dashboard metaboxes
	 * 
	 */
	public function GLOBAL_TWEAK_disable_metaboxes(){
		remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
		remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
		remove_meta_box('dashboard_primary', 'dashboard', 'side');
		remove_meta_box('dashboard_secondary', 'dashboard', 'side' );
	}

	/**
	 * Remove dashboard footer texts
	 * 
	 */
	function GLOBAL_TWEAK_remove_help_tabs(){
		return false;
	}

	/**
	 * Remove dashboard footer texts
	 * 
	 */
	function GLOBAL_TWEAK_remove_admin_tabs() {
		$screen = get_current_screen();
		$screen->remove_help_tabs();
	}

	/**
	 * Remove dashboard footer texts
	 * 
	 */
	function GLOBAL_TWEAK_remove_footer_admin() {
		return false;
	}


	// *** NOTICES *** //

	/**
	 * Remove all dashboard messages
	 * 
	 */
	public function GLOBAL_TWEAK_disable_admin_notices() { 
		global $wp_filter, $pagenow;
		if ( in_array( $pagenow , array( 'profile.php' , 'user-edit.php' ) ) ) {
			return;
		}
		if ( is_user_admin() ) { 
			if ( isset( $wp_filter['user_admin_notices'] ) ) { 
				unset( $wp_filter['user_admin_notices'] ); 
			} 
		} else if ( isset( $wp_filter['admin_notices'] ) ) { 
			unset( $wp_filter['admin_notices'] ); 
		} 
		if ( isset( $wp_filter['all_admin_notices'] ) ) { 
			unset( $wp_filter['all_admin_notices'] ); 
		}
	}


	// *** FRONTED *** //

	/**
	 * Hide admin bar front always
	 * 
	 */
	public function GLOBAL_TWEAK_global_admin_bar_front_hide() {
		if (is_user_logged_in()) {
			add_filter( 'show_admin_bar', '__return_false' , 1000 );
		}
	}


	// *** CONVINIENT FUNCTIONS *** //

	/**
	 * Stop geussing the url of the posts
	 * 
	 */
	public function GLOBAL_TWEAK_stop_guessing_url( $url ) {
		if (is_404()) {
			return false;
		}
		return $url;
	}

	/**
	 * Return the only post when search result has only one
	 * 
	 */
	public function GLOBAL_TWEAK_redirect_single_post() {
		if (is_search()) {
			global $wp_query;
			if ($wp_query->post_count == 1 && $wp_query->max_num_pages == 1) {
				wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );
				exit;
			}
		}
	}

}

GLOBAL_TWEAK::register();