<?php

/**
 * Fired during plugin activation / Desactivation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0
 */
class WC_Print_Installer
{
    /**
     *
     */
    public static function activate()
    {
        global $wpdb;
        $tables[] = "CREATE TABLE {$wpdb->prefix}wc_print_logs (
				`log_id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`endpoint` text,
				`body`      longtext,
				`order_id`  mediumint(191) DEFAULT NULL,
				`user_id`   mediumint(9) DEFAULT NULL,
				`log_date`  datetime DEFAULT NULL,
				`status`    boolean DEFAULT NULL,
				`response`  longtext  DEFAULT NULL,
				UNIQUE KEY `log_id` (`log_id`)
			);";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($tables as $table) {
            dbDelta($table);
        }
    }
    /**
     *
     */
    public static function deactivate()
    {   global $wpdb, $wp_version;

        // Tables.
        $tables = array(
            "{$wpdb->prefix}wc_print_logs"
        );
        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
        // Delete options.
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wc_print\_%';" );
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_wc_print\_%';" );
        // Delete postmeta.
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'wc_print\_%';" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_wc_print\_%';" );
    }
}
