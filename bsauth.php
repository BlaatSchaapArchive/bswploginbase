<?php

/*
Plugin Name: BlaatLogin Base
Plugin URI: http://code.blaatschaap.be
Description: Common code for all BlaatLogin plugins
Version: 0.3
Author: André van Schoubroeck
Author URI: http://www.andrevanschoubroeck.name
License: BSD
*/



//------------------------------------------------------------------------------
if (!isset($BSLOGIN_PLUGINS)) $BSLOGIN_PLUGINS = array();

require_once("classes/BlaatLogin.class.php");
require_once("classes/BlaatLoginService.class.php");
require_once("classes/AuthService.class.php");
require_once("db.php");
add_action("admin_menu",            "BlaatLogin::init");
register_activation_hook(__FILE__,"blaatlogin_install_database");

//------------------------------------------------------------------------------
if (!function_exists("bsauth_register_options")) {
  function bsauth_register_options(){
    register_setting( 'bs_auth_pages', 'login_page' );
    register_setting( 'bs_auth_pages', 'register_page' );
    register_setting( 'bs_auth_pages', 'link_page' );
    register_setting( 'bs_auth_pages', 'logout_frontpage' );
    register_setting( 'bs_auth_pages', 'bsauth_custom_button' );

    register_setting( 'bs_auth_pages', 'bs_auth_hide_local' );

    register_setting( 'bs_auth_pages', 'bs_auth_signup_user_url' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_user_email' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_display_name' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_nickname' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_first_name' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_last_name' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_description' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_jabber' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_aim' );
    register_setting( 'bs_auth_pages', 'bs_auth_signup_yim' );


  }
}
//------------------------------------------------------------------------------
if (!function_exists("bsauth_display")) {
  function bsauth_display($content) {

    if (get_option("blaatlogin_page")==$GLOBALS['post']->ID) {
      BlaatLogin::generateLoginPage();
      //bsauth_view();
    } else {
      return $content;
    }
    
  }
}
//------------------------------------------------------------------------------
// When a WordPress user is deleted, remove any external linked accounts
if (!function_exists("bsauth_delete_user")) {
  function bsauth_delete_user($user_id) {
    global $BSLOGIN_PLUGINS;
    // For each service, delete the linked service
    foreach ($BSLOGIN_PLUGINS as $service) {
      //$service->Delete($user_id);
      $service->delUser($user_id);

    }
  }
  // Call the delete user function when a WordPress user is deleted.
  add_action( 'deleted_user', 'bsauth_delete_user' );
}


// go frontpage
// -- general auth related support

if (!function_exists("bsauth_logout_hook")){
  function bsauth_logout_hook() {
    session_destroy();
    if (get_option("logout_frontpage")) go_frontpage();
  }
}


if (!function_exists("go_frontpage")) {
  function go_frontpage(){
    wp_redirect( home_url() );
    exit();
  }
}

  add_action('wp_logout','bsauth_logout_hook');

  add_action('wp_loaded','BlaatLogin::processLogin', 11); // do it earlier?

//------------------------------------------------------------------------------


?>
