<?php
/**
 * Plugin Name: GUC Team Members
 * Plugin URI:  https://github.com/guc-team
 * Description: Team member management with category filtering, custom per-category sorting, and lightbox modal.
 * Version:     1.0.0
 * Author:      GUC Team
 * Text Domain: guc-team
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GUC_TEAM_VERSION', '1.0.0' );
define( 'GUC_TEAM_PATH', plugin_dir_path( __FILE__ ) );
define( 'GUC_TEAM_URL', plugin_dir_url( __FILE__ ) );

require_once GUC_TEAM_PATH . 'includes/class-cpt.php';
require_once GUC_TEAM_PATH . 'includes/class-taxonomy.php';
require_once GUC_TEAM_PATH . 'includes/class-metabox.php';
require_once GUC_TEAM_PATH . 'includes/class-admin-sorting.php';
require_once GUC_TEAM_PATH . 'includes/class-shortcode.php';

add_action( 'plugins_loaded', function () {
	GUC_Team_CPT::init();
	GUC_Team_Taxonomy::init();
	GUC_Team_Metabox::init();
	GUC_Team_Admin_Sorting::init();
	GUC_Team_Shortcode::init();
} );
