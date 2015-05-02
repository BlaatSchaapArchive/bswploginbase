<?php

  class BlaatLogin{
//------------------------------------------------------------------------------
  public function  install() {
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

    function init(){
    // NOTE we cannot use self:: in function calls outside the class

    if (!BlaatSchaap::isPageRegistered('blaat_plugins')){
      add_menu_page('BlaatSchaap', 'BlaatSchaap', 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
    }

      add_submenu_page('blaat_plugins' ,  __('BlaatLogin Services',"blaat_auth"),   
                                          __('BlaatLogin Services',"blaat_auth"), 
                                          'manage_options', 
                                          'blaatlogin_configure_services', 
                                          'BlaatLogin::generateServiceConfigPage' );

      add_submenu_page('blaat_plugins' ,  __('BlaatLogin Pages',"blaat_auth"),   
                                          __('BlaatLogin Pages',"blaat_auth"), 
                                          'manage_options', 
                                          'blaatlogin_configure_pages', 
                                          'BlaatLogin::generatePageConfigPage' );

      add_action("admin_enqueue_scripts", "BlaatLogin::enqueueAdminCSS" );


    }
//------------------------------------------------------------------------------
    function enqueueAdminCSS(){
      wp_register_style("BlaatLoginConfig" , plugin_dir_url(__FILE__) . "../css/BlaatLoginConfig.css");
      wp_enqueue_style( "BlaatLoginConfig");
    }
//------------------------------------------------------------------------------
    function generatePageConfigPage($echo=true){
      //TODO implement me
    }

//------------------------------------------------------------------------------
    function displayUpdatedNotice() { 
      // sample code from WordPress Codex
      // should this be rewritten?
      // TODO message?
        ?> 
        <div class="updated">
            <p><?php _e("Updated"); ?></p>
        </div>
        <?php
    }


//------------------------------------------------------------------------------
  public function getMaxOrder(){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_login_generic_options";
    return $wpdb->get_var( "SELECT MAX(sortorder) FROM $table_name");
  }
//------------------------------------------------------------------------------
  public function moveUp($login_options_id){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_login_generic_options";
    $query = $wpdb->prepare("SELECT sortorder FROM $table_name WHERE login_options_id = %d",$login_options_id);
    $current_order =  $wpdb->get_var($query);
    $query = $wpdb->update($table_name, array("sortorder" => $current_order), array("sortorder" => $current_order + 1) );
    $query = $wpdb->update($table_name, array("sortorder" => $display_order + 1), array("login_options_id" => $login_options_id) );
  }
//------------------------------------------------------------------------------
  public function moveDown($login_options_id){
    global $wpdb;
    $table_name = $wpdb->prefix . "bs_login_generic_options";
    $query = $wpdb->prepare("SELECT sortorder FROM $table_name WHERE login_options_id = %d",$login_options_id);
    $current_order =  $wpdb->get_var($query);
    $query = $wpdb->update($table_name, array("sortorder" => $current_order), array("sortorder" => $current_order - 1) );
    $query = $wpdb->update($table_name, array("sortorder" => $display_order - 1), array("login_options_id" => $login_options_id) );
  }


//------------------------------------------------------------------------------
    function addConfig($data=NULL){
      global $wpdb;
      $table_name = $wpdb->prefix . "bs_login_generic_options";
      
      if ($data==NULL){  
        $data=array();
        $data['enabled'] = $_POST['enabled']; 
        unset($_POST['enabled']);
        $data['display_name'] = $_POST['display_name']; 
        unset($_POST['display_name']);
      }

      $data['sortorder'] = 1 + self::getMaxOrder() ;

      $wpdb->insert($table_name,$data);
      return $wpdb->insert_id;

    }
//------------------------------------------------------------------------------
    function generateServiceConfigPage($echo=true){
      $edit   = isset($_POST['bsauth_edit']);
      $delete   = isset($_POST['bsauth_delete']);
      $add   = isset($_POST['bsauth_add']);

      if (isset($_POST["bsauth_edit_save"])) {
        global $BSAUTH_SERVICES;
        $plugin_id = $_POST['plugin_id'];
        $service_id = $_POST['service_id'];
        unset($_POST['plugin_id']);
        unset($_POST['service_id']);
        unset($_POST['bsauth_edit_save']);
        $service = $BSAUTH_SERVICES[$plugin_id];
        $service->setConfig($service_id);        
        self::displayUpdatedNotice();
      }

      if (isset($_POST["bsauth_add_save"])) {
        global $BSAUTH_SERVICES;
        $plugin_id = $_POST['plugin_id'];
        unset($_POST['plugin_id']);
        unset($_POST['bsauth_add_save']);
        $service = $BSAUTH_SERVICES[$plugin_id];
        $_POST['login_options_id']=self::addConfig();
        $service->addConfig();        
        self::displayUpdatedNotice();
      }


      // rewrite?
      if ($edit) {
       if ( isset($_POST['bsauth_edit'])){
          $login = explode ("-", $_POST['bsauth_edit']);
          $_SESSION['bsauth_edit']=$_POST['bsauth_edit'];
        } else {
          $login = explode ("-", $_SESSION['bsauth_edit']);
        }
        $plugin_id = $login[0];
        $service_id = $login[1];
        self::generatePageSetupEditPage($plugin_id, $service_id); 
      } elseif ($delete) {
       if ( isset($_POST['bsauth_delete'])){
          $login = explode ("-", $_POST['bsauth_delete']);
          $_SESSION['bsauth_delete']=$_POST['bsauth_delete'];
        } else {
          $login = explode ("-", $_SESSION['bsauth_delete']);
        }
        $plugin_id = $login[0];
        $service_id = $login[1];
          self::generatePageSetupDeletePage($plugin_id, $service_id); 
      } elseif ($add) {
       if ( isset($_POST['bsauth_add'])){
          $login = explode ("-", $_POST['bsauth_add']);
          $_SESSION['bsauth_add']=$_POST['bsauth_add'];
          unset($_POST['bsauth_add']);
        } else {
          $login = explode ("-", $_SESSION['bsauth_add']);
        }
        $plugin_id = $login[0];
        $config_id = $login[1];
          self::generatePageSetupAddPage($plugin_id, $config_id); 
      } else self::generatePageSetupOverviewPage(); 
    }
//------------------------------------------------------------------------------
  function generatePageSetupAddPage($plugin_id, $config_id){
    global $BSAUTH_SERVICES;
    $service = $BSAUTH_SERVICES[$plugin_id];

    if ($config_id) {
      $service_id = $service->addPreconfiguredService($config_id);
      self::generatePageSetupEditPage($plugin_id, $service_id);
      // TODO: possibly hide preconfigured values for preconfigures services
    } else {
      BlaatSchaap::GenerateOptions($service->getConfigOptions(),  NULL , __("BlaatLogin Service Configuration","BlaatLogin"),"bsauth_add_save");
    }
  }
//------------------------------------------------------------------------------
  function generatePageSetupEditPage($plugin_id, $service_id){
    global $BSAUTH_SERVICES;
    $service = $BSAUTH_SERVICES[$plugin_id];
    BlaatSchaap::GenerateOptions($service->getConfigOptions(), $service->getConfig($service_id), __("BlaatLogin Service Configuration","BlaatLogin"),"bsauth_edit_save");
  }
//------------------------------------------------------------------------------
  function generatePageSetupDeletePage($plugin_id, $service_id){}
//------------------------------------------------------------------------------
  function generatePageSetupOverviewPage(){
    global $BSAUTH_SERVICES;
    $configuredServices = array();
    $preConfiguredServices = array();
    $xmlroot = new SimpleXMLElement('<div />');

    $xmlroot->addChild("h1", __("BlaatLogin Service Configuration","BlaatLogin"));


    $xmlAddServices = $xmlroot->addChild("div");
    $xmlAddServices->addAttribute("class", "ServicesList");
    $xmlAddServices->addChild("h2",__("Add services","BlaatLogin"));


    foreach ($BSAUTH_SERVICES as $plugin_id =>$plugin) {
      $configuredServices_new = array_merge ( $configuredServices , 
        $plugin->getServices(false));
      $configuredServices=$configuredServices_new;

      /*
      $preConfiguredServices_new = array_merge ( $preConfiguredServices , 
        $service->getPreConfiguredServices());
      $preConfiguredServices=$preConfiguredServices_new;
      */
      $xmlService = $xmlAddServices->addChild("div");

      $xmlService->addAttribute("class", "BlaatLoginServiceConfig");
      $xmltable = $xmlService->addChild("table");  

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th", __("Plugin","BlaatLogin"));
      $xmltr->addChild("td", $plugin_id);

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th", __("Service","BlaatLogin"));

      $xmlform= $xmltr->addChild("td")->addChild("form");
      $xmlform->addAttribute("method","post");
      $xmlselect = $xmlform->addChild("select");
      $xmlselect->addAttribute("name", "bsauth_add");
      foreach ($plugin->getPreConfiguredServices() as $preConfiguredService) {
      //$preConfiguredService
        $xmloption = $xmlselect->addChild("option", $preConfiguredService->display_name );  
        $xmloption->addAttribute("value" , $preConfiguredService->plugin_id."-".$preConfiguredService->service_id);
      }
      $xmlform->addChild("td")->addChild("Button",__("Add"));
    }
    $xmltr = $xmltable->addChild("tr"); 
    $xmltr->addChild("td");
    $xmlform= $xmltr->addChild("td")->addChild("form");
    $xmlform->addAttribute("method","post");
    $xmlAddCustomButton = $xmlform->addChild("Button",__("Add Custom", "BlaatLogin"));
    $xmlAddCustomButton->addAttribute("value" , $preConfiguredService->plugin_id."-0");
    $xmlAddCustomButton->addAttribute("name", "bsauth_add");


    usort($configuredServices, "self::sortServices"); 
    $xmlroot->addChild("br");
    $xmlEditServices = $xmlroot->addChild("div");
    $xmlEditServices->addAttribute("class", "ServicesList");
    $xmlEditServices->addChild("h2",__("Edit services","BlaatLogin"));

    foreach ($configuredServices as $configuredService) {
      $xmlService = $xmlEditServices->addChild("form");
      $xmlService->addAttribute("method","post");
      $xmlService->addAttribute("class", "BlaatLoginServiceConfig");
      $xmltable = $xmlService->addChild("table");  

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th", __("Plugin","BlaatLogin"));
      $xmltr->addChild("td", $configuredService->plugin_id);

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th", __("Display Name","BlaatLogin"));
      $xmltr->addChild("td", $configuredService->display_name);

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th", __("Enabled","BlaatLogin"));
      $xmltr->addChild("td", $configuredService->enabled ? __("Yes") : __("No") ); 

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th", __("Button Preview","BlaatLogin"));
      self::generateButton($configuredService, $xmltr->addChild("td"));

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th");
      $xmlBtn = $xmltr->addChild("td");
      $xmlUpBtn =  $xmlBtn->addChild("button", __("Move Up","BlaatLogin"));
      $xmlUpBtn->addAttribute("name", "bsauth_moveup");
      $xmlUpBtn->addAttribute("value", $configuredService->plugin_id ."-". $configuredService->service_id);
      $xmlUpBtn->addAttribute("class", "BlaatLoginConfigButton");
      $xmlDownBtn  =$xmlBtn->addChild("button", __("Move Down","BlaatLogin"));
      $xmlDownBtn->addAttribute("name", "bsauth_movedown");
      $xmlDownBtn->addAttribute("value", $configuredService->plugin_id ."-". $configuredService->service_id);
      $xmlDownBtn->addAttribute("class", "BlaatLoginConfigButton");

      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("th");
      $xmlBtn = $xmltr->addChild("td");
      $xmlEditBtn =  $xmlBtn->addChild("button", __("Edit"));
      $xmlEditBtn->addAttribute("name", "bsauth_edit");
      $xmlEditBtn->addAttribute("value", $configuredService->plugin_id ."-". $configuredService->service_id);
      $xmlEditBtn->addAttribute("class", "BlaatLoginConfigButton");
      $xmlDelBtn  =$xmlBtn->addChild("button", __("Delete"));
      $xmlDelBtn->addAttribute("name", "bsauth_delete");
      $xmlDelBtn->addAttribute("value", $configuredService->plugin_id ."-". $configuredService->service_id);
      $xmlDelBtn->addAttribute("class", "BlaatLoginConfigButton");


    }
  return BlaatSchaap::xml2html($xmlroot); 
  }
//------------------------------------------------------------------------------
  function generateButton($configuredService, $xmlroot, $action=NULL){


    $xmlbutton = $xmlroot->addChild("button");
    $xmlbutton->addAttribute("class",'bs-auth-btn');
    if ($action) {
      $xmlbutton->addAttribute("name", "bsauth_$action");
      $xmlbutton->addAttribute("value", $configuredService->plugin ."-". $configuredService->id);
      $xmlbutton->addAttribute("type", "submit");
    }

    
    $xmllogo = $xmlbutton->addChild("span"," "); //HTML5/XHTML incompatibility, no <span /> allowed?
                                                  // might not be needed with the new generation code
    $xmllogo->addAttribute("class", "bs-auth-btn-logo");
    $xmllogo->addAttribute("style", "background-image:url(\"" .$configuredService->icon. "\");");
  
    $xmltext = $xmlbutton->addChild("span", $configuredService->display_name);
    $xmltext->addAttribute("class",'bs-auth-btn-text');

    
  }

//------------------------------------------------------------------------------
  function sortServices($a, $b) {
    if ($a->order == $b->order) return 0;
    return ($a->order < $b->order) ? -1 : 1;
  }
//------------------------------------------------------------------------------
}

  
?>
