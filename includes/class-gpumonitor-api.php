<?php
/**
 * Class: GPU_MONITOR_API
 * Custom for NTNU SMIL LAB GPU_MONITOR_API components.
 */

class GPU_MONITOR_API {

	private $labname;
	public $params;

	/**
	 * This plugin's instance.
	 *
	 * @var GPU_MONITOR_API
	 */
	private static $instance;

	/**
	 * Registers the plugin.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new GPU_MONITOR_API();
		}
	}

	/**
	 * Set up the hooks and default values
	 */
	public function __construct( $params = array() ) {
		$this->labname = "NTNUSMIL";
		$this->postid_userid = "userid";
		$this->function = "gpumonitor";
		$this->cacheGPUMONITOR = "";
		$this->params = $params;
		foreach ($params as $k => $v) {
            $this->$k = $v;
        }
		$this->add_hooks();
	}

	/**
	 * Register actions and filters.
	 */
	public function add_hooks() {
		add_action( 'rest_api_init', array( $this , 'GPU_MONITOR_API_register_restapi' ) );
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, * or null if none.
	 */
	// @https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	// @https://stackoverflow.com/questions/47455745/wordpress-api-permission-callback-check-if-user-is-logged-in
	// @https://wordpress.stackexchange.com/questions/323637/verify-nonce-in-rest-api
	// @https://wordpress.stackexchange.com/questions/296691/adding-wordpress-api-endpoint-with-multiple-parameters
	// @https://wordpress.stackexchange.com/questions/210597/query-wp-rest-api-v2-by-multiple-meta-keys
	public function GPU_MONITOR_API_register_restapi() {
		$routes = '/'.$this->function;
		register_rest_route( 'wp/v2', $routes, array(
					'methods' => 'GET',
					'callback' => array( $this , 'GPU_MONITOR_API_restapi_callback' ),
					'permission_callback' => '__return_true',
				)
		);
	}


	/**
	 * Callback for registered restapi of gpumonitor
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, or null if none.
	 */
	public function GPU_MONITOR_API_restapi_callback( $data ) {
		return $this->GPU_MONITOR_API_handle();
	}


	/**
	 * Main function for getting GPUs information from servers
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest, or null if none.
	 */
	public function GPU_MONITOR_API_handle() {

		// Check the transient to sidestep useless API requests
		$cachedGPUMONITOR = get_transient( $this->cacheGPUMONITOR );

		if ( !$cachedGPUMONITOR ) {
			
			// Get options of global option
			$tmp_global_control_gpu_monitor = new GLOBAL_CONTROL_GPU_MONITOR();
			$url  = get_option($tmp_global_control_gpu_monitor->gpumonitor_domainname, "");
			$port = get_option($tmp_global_control_gpu_monitor->gpumonitor_port, "");
			$slug = get_option($tmp_global_control_gpu_monitor->gpumonitor_slugpage, "");
			$time = get_option($tmp_global_control_gpu_monitor->gpumonitor_timeintervalupdate, "");

			// if option do not saved, return error content
			if (empty($url)||empty($port)||empty($slug)) return array( 'error' => true, 'errmessage' => '請先於後台設定 API 的資訊。' );

			$_apiURL = "{$url}:{$port}/{$slug}";
			$_args   = array(
				'timeout' => '5',
				'method'  => 'GET',
				'headers' => [
					'Content-Type' => 'application/json',
				],
			);
			$response = wp_remote_get( $_apiURL, $_args );

			// API connection
			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
				return array( 'error' => true, 'errmessage' => 'API 伺服器連線錯誤，或後台設定 API 的資訊有錯誤。' );
			}

			// data empty
			if ( empty( wp_remote_retrieve_body( $response ) ) ) {
				return array( 'error' => true, 'errmessage' => 'API 伺服器連線成功，但是 API 伺服器設定可能有問題。' );
			}

			// Cache $response to sidestep useless API requests
			if (intval($time) != 0) {
				set_transient( $this->cacheGPUMONITOR, $response, intval($time) );
			}
		}

		if ( $cachedGPUMONITOR ) {
			$response = $cachedGPUMONITOR;
		}

		$content = wp_remote_retrieve_body( $response );
		$json_content = json_decode( $content, true ); // true for convert to array type

		return $json_content;
	}

}

GPU_MONITOR_API::register();
