<?php
  function  blaatlogin_install_database() {
  global $wpdb;

  // dbver in sync with plugin ver
  $dbver = 1;
  $live_dbver = get_option( "blaat_login_dbversion" );

  if (($dbver != $live_dbver) || get_option("bs_debug_updatedb") ) {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    $table_name = $wpdb->prefix . "bs_login_generic_options";
    $query = "CREATE TABLE $table_name (
              `login_options_id` INT NOT NULL AUTO_INCREMENT   ,
              `sortorder` INT NOT NULL,
              `enabled` BOOLEAN NOT NULL DEFAULT TRUE ,
              `display_name` TEXT NOT NULL ,
              `display_order` INT NOT NULL DEFAULT 1,
              `custom_icon_url` TEXT NULL DEFAULT NULL,
              `custom_icon_filename` TEXT NULL DEFAULT NULL,
              `custom_icon_enabled` BOOLEAN DEFAULT FALSE,
              `default_icon`  TEXT NULL DEFAULT NULL,
              `auto_register` BOOL NOT NULL DEFAULT FALSE,
              PRIMARY KEY  (login_options_id)
              );";
    dbDelta($query);
    }
}
?>
