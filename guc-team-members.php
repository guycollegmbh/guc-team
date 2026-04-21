<?php
/**
 * Plugin Name: GUC Team Members
 * Plugin URI:  https://github.com/guycollegmbh/guc-team
 * Description: Team member management with category filtering, custom per-category sorting, and lightbox modal.
 * Version:     1.1.1
 * Author:      GUC Team
 * Text Domain: guc-team
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GUC_TEAM_VERSION', '1.1.1' );
define( 'GUC_TEAM_PATH', plugin_dir_path( __FILE__ ) );
define( 'GUC_TEAM_URL', plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// Automatic updates via GitHub (Plugin Update Checker v5)
// ---------------------------------------------------------------------------
require_once GUC_TEAM_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';

$guc_team_updater = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/guycollegmbh/guc-team/',
	__FILE__,
	'guc-team-members'
);

// Tell the checker to use GitHub Releases (tags) as the version source.
$guc_team_updater->setBranch( 'main' );
$guc_team_updater->getVcsApi()->enableReleaseAssets();

// ---------------------------------------------------------------------------
// Load plugin classes
// ---------------------------------------------------------------------------
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
