<?php

/* Equivalent DDL statement for database table for Croissanga plugin
 * consistent for Croissanga, version 1.1 and up
CREATE TABLE xanga_wp_post_map (
        ID bigint(20) unsigned NOT NULL auto_increment,
        post_ID bigint(20) unsigned NOT NULL,
        xanga_ID text NOT NULL,
        PRIMARY KEY (ID)
);
 */
function croissanga_install_table() {
    global $wpdb;
    
    if (!croissanga_table_exists())
        $wpdb->query("CREATE TABLE xanga_wp_post_map ( ID bigint(20) unsigned NOT NULL auto_increment, post_ID bigint(20) unsigned NOT NULL, xanga_ID text NOT NULL, PRIMARY KEY (ID) )");
}

function croissanga_table_exists() {
    global $wpdb;
    $exists = false;
    $q = $wpdb->query("SHOW TABLES LIKE 'xanga_wp_post_map'");
    if ($q == 1) {
        $exists = true;
    }
    return $exists;
}

?>
