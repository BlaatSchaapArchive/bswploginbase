<?php
  class BlaatLogin{
    function init(){

    if (!BlaatSchaap::isPageRegistered('blaat_plugins')){
      add_menu_page('BlaatSchaap', 'BlaatSchaap', 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
    }

      add_submenu_page('blaat_plugins' ,  __('BlaatLogin Services',"blaat_auth"),   
                                          __('BlaatLogin Services',"blaat_auth"), 
                                          'manage_options', 
                                          'blaatlogin_overview', 
                                          'BlaatLogin::generateConfigPage' );

      add_submenu_page('blaat_plugins' ,  __('BlaatLogin Pages',"blaat_auth"),   
                                          __('BlaatLogin Pages',"blaat_auth"), 
                                          'manage_options', 
                                          'blaatlogin_pages', 
                                          'BlaatLogin::generatePageSetupPage' );

    }

    function generatePageSetupPage($echo=true){
      //TODO implement me
    }
    function generateConfigPage($echo=true){




      $edit   = isset($_POST['bsauth_edit']);
      $delete   = isset($_POST['bsauth_delete']);
      $add   = isset($_POST['bsauth_add']);

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
        BlaatLogin::generatePageSetupEditPage($plugin_id, $service_id); 
      } elseif ($delete) {
       if ( isset($_POST['bsauth_delete'])){
          $login = explode ("-", $_POST['bsauth_delete']);
          $_SESSION['bsauth_delete']=$_POST['bsauth_delete'];
        } else {
          $login = explode ("-", $_SESSION['bsauth_delete']);
        }
        $plugin_id = $login[0];
        $service_id = $login[1];
          BlaatLogin::generatePageSetupDeletePage($plugin_id, $service_id); 
      } elseif ($add) {
       if ( isset($_POST['bsauth_add'])){
          $login = explode ("-", $_POST['bsauth_add']);
          $_SESSION['bsauth_add']=$_POST['bsauth_add'];
        } else {
          $login = explode ("-", $_SESSION['bsauth_add']);
        }
        $plugin_id = $login[0];
        $config_id = $login[1];
          BlaatLogin::generatePageSetupAddPage($plugin_id, $config_id); 
      } else BlaatLogin::generatePageSetupOverviewPage(); 
    }
//------------------------------------------------------------------------------
  function generatePageSetupAddPage($plugin_id, $config_id){
    global $BSAUTH_SERVICES;
    $service = $BSAUTH_SERVICES[$plugin_id];
    if ($config_id) {
      $service_id = $service->addPreconfiguredService($config_id);
      generatePageSetupEditPage($plugin_id, $service_id);
      // TODO: possibly hide preconfigured values for preconfigures services
    } else {
      BlaatSchaap::GenerateOptions($service->getConfigOptions());
    }
  }
//------------------------------------------------------------------------------
  function generatePageSetupEditPage($plugin_id, $service_id){
    global $BSAUTH_SERVICES;
    $service = $BSAUTH_SERVICES[$plugin_id];
    BlaatSchaap::GenerateOptions($service->getConfigOptions(), $service->getConfig($service_id));
  }
//------------------------------------------------------------------------------
  function generatePageSetupDeletePage($plugin_id, $service_id){}
//------------------------------------------------------------------------------
  function generatePageSetupOverviewPage(){
    global $BSAUTH_SERVICES;
    $configuredServices = array();
    $preConfiguredServices = array();
    $xmlroot = new SimpleXMLElement('<div />');



    foreach ($BSAUTH_SERVICES as $service) {
      $configuredServices_new = array_merge ( $configuredServices , 
        $service->getServices(false));
      $configuredServices=$configuredServices_new;

      $preConfiguredServices_new = array_merge ( $preConfiguredServices , 
        $service->getPreConfiguredServices());
      $preConfiguredServices=$preConfiguredServices_new;
    }
    echo "<pre>"; print_r($service->getPreConfiguredServices()); echo "</pre>";

    usort($configuredServices, "BlaatLogin::sortServices"); 

    foreach ($configuredServices as $configuredService) {
      $xmlService = $xmlroot->addChild("form");
      $xmlService->addAttribute("method","post");
      $xmlService->addAttribute("class", "BlaatLoginService");
      $xmlService->addChild("span", $configuredService->display_name);

      BlaatLogin::generateButton($configuredService, $xmlroot);

      $xmlEditBtn = $xmlService->addChild("button", "Edit");
      $xmlEditBtn->addAttribute("name", "bsauth_edit");
      $xmlEditBtn->addAttribute("value", $configuredService->plugin ."-". $configuredService->id);

      $xmlDelBtn  =$xmlService->addChild("button", "Delete");
      $xmlDelBtn->addAttribute("name", "bsauth_delete");
      $xmlDelBtn->addAttribute("value", $configuredService->plugin ."-". $configuredService->id);
    }

    echo $xmlroot->AsXML();
    return $xmlroot;
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
    $xmllogo->addAttribute("class", "bs-auth-btn-logo");
    $xmllogo->addAttribute("style", "background-image:url(\"" .$configuredService->icon. "\");");
  
    $xmltext = $xmlbutton->addChild("span", $configuredService->display_name);
    $xmltext->addAttribute("class",'bs-auth-btn-text');

    
  }


  function sortServices($a, $b) {
    if ($a->order == $b->order) return 0;
    return ($a->order < $b->order) ? -1 : 1;
  }
}

  
?>
