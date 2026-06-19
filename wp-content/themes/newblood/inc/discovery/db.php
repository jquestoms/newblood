<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NB_DISCOVERY_DB_VERSION', '1' );

function nb_discovery_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'nb_discovery_responses';
}

/**
 * Create/upgrade the responses table. Idempotent (dbDelta).
 */
function nb_discovery_install_table() {
    global $wpdb;
    $table   = nb_discovery_table_name();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        instance VARCHAR(64) NOT NULL DEFAULT '',
        respondent_name VARCHAR(191) NOT NULL DEFAULT '',
        respondent_email VARCHAR(191) NOT NULL DEFAULT '',
        payload LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        PRIMARY KEY (id),
        KEY instance (instance),
        KEY created_at (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    update_option( 'nb_discovery_db_version', NB_DISCOVERY_DB_VERSION );
}

/**
 * Run the migration only when the stored version is behind.
 */
function nb_discovery_maybe_migrate() {
    if ( get_option( 'nb_discovery_db_version' ) !== NB_DISCOVERY_DB_VERSION ) {
        nb_discovery_install_table();
    }
}
add_action( 'after_setup_theme', 'nb_discovery_maybe_migrate' );
