<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              #
 * @since             1.0.0
 * @package           Heyou
 *
 * @wordpress-plugin
 * Plugin Name:       Heyou video bubble reviews - FREE
 * Plugin URI:        https://www.heyou.io
 * Description:       Increase conversion rate with video bubbles. <strong>Go PRO for more features.</strong>
 * Version:           1.0.1
 * Author:            2DIGIT d.o.o.
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       heyou
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'HEYOU_VERSION', '1.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-heyou-activator.php
 */
function activate_heyou() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-heyou-activator.php';
	Heyou_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-heyou-deactivator.php
 */
function deactivate_heyou() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-heyou-deactivator.php';
	Heyou_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_heyou' );
register_deactivation_hook( __FILE__, 'deactivate_heyou' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-heyou.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_heyou() {

	$plugin = new Heyou();
	$plugin->run();

}
run_heyou();
