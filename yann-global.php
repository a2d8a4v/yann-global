<?php
/**
 * Plugin Name:       Yann Global
 * Plugin URI:        https://yannyann.com/
 * Description:       Custom for NTNU SMIL LAB Global components.
 * Version:           0.0.1
 * Requires at least: 4.6
 * Requires PHP:      5.3
 * Author:            YANNYANN
 * Author URI:        https://yannyann.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yann-global
 */

require_once dirname( __FILE__ ) . '/includes/class-global-tweak.php';
require_once dirname( __FILE__ ) . '/includes/class-cpt-initial.php';
require_once dirname( __FILE__ ) . '/includes/class-cpt-utils.php';
require_once dirname( __FILE__ ) . '/includes/class-cpt-metaboxes.php';
require_once dirname( __FILE__ ) . '/includes/class-cpt-save-delete.php';
require_once dirname( __FILE__ ) . '/includes/class-cpt-custom.php';
require_once dirname( __FILE__ ) . '/includes/class-utils.php';
require_once dirname( __FILE__ ) . '/includes/class-db-sql.php';
require_once dirname( __FILE__ ) . '/includes/class-upload-box.php';
require_once dirname( __FILE__ ) . '/includes/class-upload-files.php';
require_once dirname( __FILE__ ) . '/includes/class-delete-files.php';
require_once dirname( __FILE__ ) . '/includes/class-donwload-api.php';
require_once dirname( __FILE__ ) . '/includes/class-gpumonitor-api.php';
require_once dirname( __FILE__ ) . '/includes/class-global-control.php';
require_once dirname( __FILE__ ) . '/includes/class-global-control-gpu-monitor.php';
require_once dirname( __FILE__ ) . '/includes/class-images-upload.php';

/**
 * Include classes for Arxiv API
 * 
 */

require_once dirname( __FILE__ ) . '/includes/arxivapi/ArxivAPI.php';
require_once dirname( __FILE__ ) . '/includes/arxivapi/Paper.php';
require_once dirname( __FILE__ ) . '/includes/arxivapi/SearchQueryBuilder.php';

/**
 * Include Composer and php library - phpexif for global usage
 * 
 */
if ( is_readable( plugin_dir_path(__FILE__) . '/vendor/autoload.php' ) ) {
    require_once( plugin_dir_path(__FILE__) . '/vendor/autoload.php' );
}

// /**
//  * Init the CPT of others.
//  */
// global $yann_cpt;
// $yann_cpt = new YANN_CPT();

/**
 * Init the UTILS.
 */
global $utils;
$utils = new UTILS();

/**
 * Init the UTILS.
 */
global $cpt_utils;
$cpt_utils = new CPT_UTILS();

/**
 * Functions
 */
function wc_clean( $var ) {
    if ( is_array( $var ) ) {
        return array_map( 'wc_clean', $var );
    } else {
        return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
    }
}
