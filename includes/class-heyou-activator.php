<?php

/**
 * Fired during plugin activation
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Heyou
 * @subpackage Heyou/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Heyou
 * @subpackage Heyou/includes
 * @author     2DIGIT d.o.o. <florjan@2digit.eu>
 */
class Heyou_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		deactivate_plugins('heyou-pro/heyou-pro.php');

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		// heyou_stats tabela
		$tablename = $wpdb->prefix.'heyou_stats'; 

		$main_sql_create = 'CREATE TABLE `'.$tablename.'` ( `id` INT NOT NULL AUTO_INCREMENT , `key` VARCHAR(25) NOT NULL , `value` VARCHAR(10) , `date` DATETIME , `post_ids` VARCHAR(100) , PRIMARY KEY (`id`));';

		maybe_create_table( $wpdb->prefix . $tablename, $main_sql_create );

	}

}
