<?php
/**
 * Class: GLOBAL_CONTROL_GPU_MONITOR
 * Custom for global option.
 */

class GLOBAL_CONTROL_GPU_MONITOR {

	/**
	 * This plugin's instance.
	 *
	 * @var GLOBAL_CONTROL_GPU_MONITOR
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new GLOBAL_CONTROL_GPU_MONITOR();
		}
	}

	/**
	 * Set up the hooks and default values
	 */
	public function __construct() {
		$this->plugin = self::class;
		$this->optiongroupname = $this->plugin."_group";
		$this->section_id = $this->plugin."_setting_section";

		// fields
		$this->gpumonitor_domainname = $this->plugin."_setting_section_gpumonitor_domain_name";
		$this->gpumonitor_port = $this->plugin."_setting_section_gpumonitor_port";
		$this->gpumonitor_slugpage = $this->plugin."_setting_section_gpumonitor_slug";
		$this->gpumonitor_timeintervalupdate = $this->plugin."_setting_section_gpumonitor_timeupdateinterval";

		$this->YANN_root_user_id = 1;
		$this->add_hooks();
	}

	/**
	 * Register actions and filters.
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, $this->plugin.'_register_subpage_menu' ) );
		add_action( 'admin_init', array($this, $this->plugin.'_add_settings_section' ) );
	}


	/**
	 * Register subpage menu.
	 */
	public function GLOBAL_CONTROL_GPU_MONITOR_register_subpage_menu() {
		add_submenu_page(
			'options-general.php',
			'設定 GPU Monitor', // page <title>Title</title>
			'設定 GPU Monitor', // menu link text
			'manage_options', // capability to access the page
			'options-general.php#'.$this->section_id, // page URL slug
			'',
		);
	}


	/**
	 * Function for generating table to recording uploaded files
	 *
	 */
	public function GLOBAL_CONTROL_GPU_MONITOR_add_settings_section() {

		// GPU monitor section
		add_settings_section(
			$this->section_id,
			__( "<div id={$this->section_id}>GPU Monitor</div>", 'textdomain' ),
			array($this, $this->plugin.'_section_callback'),
			'general'
		);
		add_settings_field(
			$this->gpumonitor_domainname,
			'API domain name',
			array( $this , $this->plugin.'_section_callback_gpumonitor_domain' ), // function which prints the field
			'general', // page slug
			$this->section_id, // section ID
			array( 
				'label_for' => $this->plugin.'_section_callback_gpumonitor_domain',
				'class' => 'row-'.$this->plugin.'_section_callback_gpumonitor_domain', // for <tr> element
			)
		);
		add_settings_field(
			$this->gpumonitor_port,
			'API Port',
			array( $this , $this->plugin.'_section_callback_gpumonitor_port' ), // function which prints the field
			'general', // page slug
			$this->section_id, // section ID
			array( 
				'label_for' => $this->plugin.'_section_callback_gpumonitor_port',
				'class' => 'row-'.$this->plugin.'_section_callback_gpumonitor_port', // for <tr> element
			)
		);
		add_settings_field(
			$this->gpumonitor_slugpage,
			'API Slug Page',
			array( $this , $this->plugin.'_section_callback_gpumonitor_slugpage' ), // function which prints the field
			'general', // page slug
			$this->section_id, // section ID
			array( 
				'label_for' => $this->plugin.'_section_callback_gpumonitor_slugpage',
				'class' => 'row-'.$this->plugin.'_section_callback_gpumonitor_slugpage', // for <tr> element
			)
		);
		add_settings_field(
			$this->gpumonitor_timeintervalupdate,
			'Update Time Interval (seconds)',
			array( $this , $this->plugin.'_section_callback_gpumonitor_timeintervalupdate' ), // function which prints the field
			'general', // page slug
			$this->section_id, // section ID
			array( 
				'label_for' => $this->plugin.'_section_callback_gpumonitor_timeintervalupdate',
				'class' => 'row-'.$this->plugin.'_section_callback_gpumonitor_timeintervalupdate', // for <tr> element
			)
		);

		// register setting to db
		register_setting('general', $this->gpumonitor_domainname, 'esc_url' );
		register_setting('general', $this->gpumonitor_port, 'sanitize_text_field');
		register_setting('general', $this->gpumonitor_slugpage, 'sanitize_text_field');
		register_setting('general', $this->gpumonitor_timeintervalupdate, 'sanitize_text_field');
	}

	public function GLOBAL_CONTROL_GPU_MONITOR_section_callback() {
		echo "Setting section for using GPU Monitor.";
	}

	public function GLOBAL_CONTROL_GPU_MONITOR_section_callback_gpumonitor_domain() {
		$option = get_option( $this->gpumonitor_domainname, "");
		echo '<input type="url" id="'.$this->gpumonitor_domainname.'" name="'.$this->gpumonitor_domainname.'" value="'.$option.'" placeholder="請輸入 URL"></input><code>/</code>';
	}

	public function GLOBAL_CONTROL_GPU_MONITOR_section_callback_gpumonitor_port() {
		$option = get_option( $this->gpumonitor_port, "");
		echo '<input type="url" id="'.$this->gpumonitor_port.'" name="'.$this->gpumonitor_port.'" value="'.$option.'" placeholder="請輸入 Port"></input>';
	}

	public function GLOBAL_CONTROL_GPU_MONITOR_section_callback_gpumonitor_slugpage() {
		$option = get_option( $this->gpumonitor_slugpage, "");
		echo '<code>/</code><input type="url" id="'.$this->gpumonitor_slugpage.'" name="'.$this->gpumonitor_slugpage.'" value="'.$option.'" placeholder="請輸入 Slug"></input><code>/</code>';
	}

	public function GLOBAL_CONTROL_GPU_MONITOR_section_callback_gpumonitor_timeintervalupdate() {
		$option = get_option( $this->gpumonitor_timeintervalupdate, "");
		echo '<input type="text" id="'.$this->gpumonitor_timeintervalupdate.'" name="'.$this->gpumonitor_timeintervalupdate.'" value="'.$option.'" placeholder="請輸入更新時間間隔"></input>';
	}
}

GLOBAL_CONTROL_GPU_MONITOR::register();