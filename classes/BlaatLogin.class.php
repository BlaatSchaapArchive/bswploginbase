<?php
if (class_exists("BlaatSchaap")) {

  class BlaatLogin {

//------------------------------------------------------------------------------


    function init() {
      // NOTE we cannot use self:: in function calls outside the class



      if (!BlaatSchaap::isPageRegistered('blaat_plugins')) {
        add_menu_page('BlaatSchaap', 'BlaatSchaap', 'manage_options', 'blaat_plugins', 'blaat_plugins_page');
      }

      add_submenu_page('blaat_plugins', __('BlaatLogin Configuration', "BlaatLogin"), __('BlaatLogin Configuration', "BlaatLogin"), 'manage_options', 'blaatlogin_configure_pages', 'BlaatLogin::generateGenericConfigPage');

      add_submenu_page('blaat_plugins', __('BlaatLogin Services', "BlaatLogin"), __('BlaatLogin Services', "BlaatLogin"), 'manage_options', 'blaatlogin_configure_services', 'BlaatLogin::generateServiceConfigPage');



      add_action("admin_enqueue_scripts", "BlaatLogin::enqueueAdminCSS");
      ;
      if (get_option("login_page") || get_option("register_page") || get_option("link_page")) {
        add_submenu_page('blaat_plugins', __('BlaatLogin Migration', "BlaatLogin"), __('BlaatLogin Migration', "BlaatLogin"), 'manage_options', 'blaatlogin_configure_migration', 'BlaatLogin::generateMigrationPage');
        add_action('admin_notices', 'BlaatLogin::generateMigrationPageNotice');
      }

      global $BSLOGIN_PLUGINS;
      foreach ($BSLOGIN_PLUGINS as $plugin) {
        if (method_exists($plugin, "init"))
          $plugin->init();
      }
    }

    //------------------------------------------------------------------------------
    function generateMigrationPage() {
      $xmlroot = new SimpleXMLElement('<div />');
      $xmlroot->addChild("h1", __('BlaatLogin Migration', "BlaatLogin"));
      if (isset($_POST['blaatlogin_page_migration'])) {

        $blaatlogin_page = get_page_by_title($_POST["blaatlogin_page"]);
        $blaatlogin_id = $blaatlogin_page->ID;

        update_option("blaatlogin_page", $blaatlogin_page_id);
        if (isset($_POST["blaatlogin_delete_other_pages"])) {

          $pages_to_delete = array();
          if ($_POST['blaatlogin_page'] != get_option("login_page"))
            $pages_to_delete[] = get_option("login_page");
          if ($_POST['blaatlogin_page'] != get_option("link_page"))
            $pages_to_delete[] = get_option("link_page");
          if ($_POST['blaatlogin_page'] != get_option("register_page"))
            $pages_to_delete[] = get_option("register_page");

          foreach ($pages_to_delete as $delete_me_title) {
            $delete_me_page = get_page_by_title($delete_me_title);
            $delete_me_id = $delete_me_page->ID;
            wp_delete_post($delete_me_id);
          }
        }

        delete_option("login_page");
        delete_option("link_page");
        delete_option("register_page");

        $xmlroot->addChild("div", __("Page migration completed.", "BlaatLogin"));
        $xmlroot->addChild("div", __("Please update your menus if required.", "BlaatLogin"));
        $xmlroot->addChild("div", __("Thank you for using BlaatLogin", "BlaatLogin"));
      } else {

        $xmlroot->addChild("div", __("In previous versions there where three 
          distinct pages, 'link', 'login' and 'register'.  
          These pages have been unified into a single page.", "BlaatLogin"));
        $xmlroot->addChild("div", __("Your configuration is still configured
          for the distinct pages. Please select the page you wish to use for BlaatLogin.
          The other pages can be deleted.", "BlaatLogin"));
        $xmlform = $xmlroot->addChild("form");
        $xmlform->addAttribute("method", "post");

        $xmltable = $xmlform->addChild("table");
        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("BlaatLogin page", "BlaatLogin"));

        $xmlselect = $xmltr->addChild("td")->addChild("select");
        $xmlselect->addAttribute("name", "blaatlogin_page");
        $xmlselect->addAttribute("id", "blaatlogin_page");
        $xmloption = $xmlselect->addChild("option", get_option("login_page"));
        $xmloption->addAttribute("value", get_option("login_page"));
        $xmloption = $xmlselect->addChild("option", get_option("register_page"));
        $xmloption->addAttribute("value", get_option("register_page"));
        $xmloption = $xmlselect->addChild("option", get_option("link_page"));
        $xmloption->addAttribute("value", get_option("link_page"));

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("Delete other pages", "BlaatLogin"));
        $xmlinput = $xmltr->addChild("td")->addChild("input");
        $xmlinput->addAttribute("name", "blaatlogin_delete_other_pages");
        $xmlinput->addAttribute("value", "1");
        $xmlinput->addAttribute("type", "checkbox");
        $xmlinput->addAttribute("checked", "1");

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th");
        $xmlbutton = $xmltr->addChild("td")->addChild("button", __("Save"));
        $xmlbutton->addAttribute("name", "blaatlogin_page_migration");
        $xmlbutton->addAttribute("value", "1");
      }
      BlaatSchaap::xml2html($xmlroot);
    }

    //------------------------------------------------------------------------------
    function generateMigrationPageNotice() {
      // TODO: how to get link to the page?
      $class = "update-nag";
      $href = "admin.php?page=blaatlogin_configure_migration";
      $message = __("The structure of the pages generated by BlaatLogin has changed.", "BlaatLogin");
      $message .= " " . sprintf(__("Please consult the <a href='%s'>migration settings</a>", "BlaatLogin"), $href);
      $title = __("BlaatLogin", "BlaatLogin");
      echo"<div class=\"$class\"> <h1>$title</h1><p>$message</p></div>";
    }

    //------------------------------------------------------------------------------
    function enqueueAdminCSS() {
      wp_register_style("BlaatLoginConfig", plugin_dir_url(__DIR__) . "css/BlaatLoginConfig.css");
      wp_enqueue_style("BlaatLoginConfig");
    }

    //------------------------------------------------------------------------------
    function generateGenericConfigPage() {
      if (isset($_POST['blaatlogin_config_save'])) {
        update_option("blaatlogin_page", $_POST['blaatlogin_page']);

        update_option("blaatlogin_login_enabled", $_POST['blaatlogin_login_enabled']);
        update_option("blaatlogin_register_enabled", $_POST['blaatlogin_register_enabled']);
        update_option("blaatlogin_link_enabled", $_POST['blaatlogin_link_enabled']);
        update_option("blaatlogin_fetch_enabled", $_POST['blaatlogin_fetch_enabled']);
        update_option("blaatlogin_auto_enabled", $_POST['blaatlogin_auto_enabled']);
      }

      $GenericTab = new BlaatConfigTab("generic", __("Generic configuration", "blaat_oauth"));

      $pageSelector = new BlaatConfigOption("blaatlogin_page", __("BlaatLogin Page", "BlaatLogin"), "select", true);
      BlaatSchaap::setupPageSelect($pageSelector);
      $GenericTab->addOption($pageSelector);


      $loginSelector = new BlaatConfigOption("blaatlogin_login_enabled", __("Login Enabled", "BlaatLogin"), "select", true, get_option("blaatlogin_login_enabled"));
      $loginSelector->addOption(new BlaatConfigSelectOption("Disabled", __("Disabled")));
      $loginSelector->addOption(new BlaatConfigSelectOption("LocalOnly", __("Local Only", "BlaatLogin")));
      $loginSelector->addOption(new BlaatConfigSelectOption("RemoteOnly", __("Remote Only", "BlaatLogin")));
      $loginSelector->addOption(new BlaatConfigSelectOption("Both", __("Both", "BlaatLogin")));
      $GenericTab->addOption($loginSelector);

      $registerSelector = new BlaatConfigOption("blaatlogin_register_enabled", __("Register Enabled", "BlaatLogin"), "select", true, get_option("blaatlogin_register_enabled"));
      $registerSelector->addOption(new BlaatConfigSelectOption("Disabled", __("Disabled")));
      $registerSelector->addOption(new BlaatConfigSelectOption("LocalOnly", __("Local Only", "BlaatLogin")));
      $registerSelector->addOption(new BlaatConfigSelectOption("RemoteOnly", __("Remote Only", "BlaatLogin")));
      $registerSelector->addOption(new BlaatConfigSelectOption("Both", __("Both", "BlaatLogin")));
      $registerSelector->addOption(new BlaatConfigSelectOption("HonourGlobal", __("Honour global 'users_can_register'", "BlaatLogin")));
      $GenericTab->addOption($registerSelector);

      $linkSelector = new BlaatConfigOption("blaatlogin_link_enabled", __("Link Enabled", "BlaatLogin"), "select", true, get_option("blaatlogin_link_enabled"));
      $linkSelector->addOption(new BlaatConfigSelectOption("Disabled", __("Disabled")));
      $linkSelector->addOption(new BlaatConfigSelectOption("Enabled", __("Enabled")));
      $GenericTab->addOption($linkSelector);

      $fetchSelector = new BlaatConfigOption("blaatlogin_fetch_enabled", __("Fetch User Data", "BlaatLogin"), "select", true, get_option("blaatlogin_fetch_enabled"));
      $fetchSelector->addOption(new BlaatConfigSelectOption("Disabled", __("Disabled")));
      $fetchSelector->addOption(new BlaatConfigSelectOption("Enabled", __("Enabled")));
      $GenericTab->addOption($fetchSelector);


      $autoSelector = new BlaatConfigOption("blaatlogin_auto_enabled", __("Attempt Auto Register", "BlaatLogin"), "select", true, get_option("blaatlogin_auto_enabled"));
      $autoSelector->addOption(new BlaatConfigSelectOption("Disabled", __("Disabled")));
      $autoSelector->addOption(new BlaatConfigSelectOption("Enabled", __("Enabled")));
      $GenericTab->addOption($autoSelector);




      BlaatSchaap::GenerateOptions(array($GenericTab), NULL, __("BlaatLogin Generic Configuration", "BlaatLogin"), "blaatlogin_config_save");
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
    public function getMaxOrder() {
      global $wpdb;
      $table_name = $wpdb->prefix . "bs_login_generic_options";
      return $wpdb->get_var("SELECT MAX(sortorder) FROM $table_name");
    }

    //------------------------------------------------------------------------------
    public function moveDown($login_options_id) {
      global $wpdb;
      $table_name = $wpdb->prefix . "bs_login_generic_options";
      $query = $wpdb->prepare("SELECT sortorder FROM $table_name WHERE login_options_id = %d", $login_options_id);
      $current_order = $wpdb->get_var($query);
      $query = $wpdb->update($table_name, array("sortorder" => $current_order), array("sortorder" => $current_order + 1));
      $query = $wpdb->update($table_name, array("sortorder" => $current_order + 1), array("login_options_id" => $login_options_id));
    }

    //------------------------------------------------------------------------------
    public function moveUp($login_options_id) {
      global $wpdb;
      $table_name = $wpdb->prefix . "bs_login_generic_options";
      $query = $wpdb->prepare("SELECT sortorder FROM $table_name WHERE login_options_id = %d", $login_options_id);
      $current_order = $wpdb->get_var($query);
      $query = $wpdb->update($table_name, array("sortorder" => $current_order), array("sortorder" => $current_order - 1));
      $query = $wpdb->update($table_name, array("sortorder" => $current_order - 1), array("login_options_id" => $login_options_id));
    }

    //------------------------------------------------------------------------------
    function addConfig($data = NULL) {
      global $wpdb;
      $table_name = $wpdb->prefix . "bs_login_generic_options";

      if ($data == NULL) {
        $data = array();
        $data['enabled'] = $_POST['enabled'];
        unset($_POST['enabled']);
        $data['display_name'] = $_POST['display_name'];
        unset($_POST['display_name']);
      }

      $data['sortorder'] = 1 + self::getMaxOrder();

      $wpdb->insert($table_name, $data);
      return $wpdb->insert_id;
    }

    function setConfig() {
      global $wpdb;
      $table_name = $wpdb->prefix . "bs_login_generic_options";
      $login_options_id = $_POST['login_options_id'];
      unset($_POST['login_options_id']);
      $globalconfig = array();
      $globalconfig['enabled'] = $_POST['enabled'];
      unset($_POST['enabled']);
      $globalconfig['display_name'] = $_POST['display_name'];
      unset($_POST['display_name']);
      $globalconfig['auto_register'] = $_POST['auto_register'];
      unset($_POST['auto_register']);
      $query = $wpdb->update($table_name, $globalconfig, array("login_options_id" => $login_options_id));
    }

    //------------------------------------------------------------------------------
    function delConfig() {
      global $wpdb;
      $table_name = $wpdb->prefix . "bs_login_generic_options";
      $login_options_id = $_POST['login_options_id'];
      $wpdb->delete($table_name, array("login_options_id" => $login_options_id));
    }

    //------------------------------------------------------------------------------
    function generateServiceConfigPage($echo = true) {
      $edit = isset($_POST['bsauth_edit']);
      $delete = isset($_POST['bsauth_delete']);
      $add = isset($_POST['bsauth_add']);

      if (isset($_POST["bsauth_edit_save"])) {
        global $BSLOGIN_PLUGINS;
        $plugin_id = $_POST['plugin_id'];
        unset($_POST['plugin_id']);
        unset($_POST['bsauth_edit_save']);
        $plugin = $BSLOGIN_PLUGINS[$plugin_id];
        self::setconfig();      // save generic options
        $plugin->setConfig();  // sae plugin options      
        self::displayUpdatedNotice();
      }

      if (isset($_POST["bsauth_add_save"])) {
        global $BSLOGIN_PLUGINS;
        $plugin_id = $_POST['plugin_id'];
        unset($_POST['plugin_id']);
        unset($_POST['bsauth_add_save']);
        $service = $BSLOGIN_PLUGINS[$plugin_id];
        $_POST['login_options_id'] = self::addConfig();
        $service->addConfig();
        self::displayUpdatedNotice();
      }

      if (isset($_POST["bsauth_delete_save"])) {
        global $BSLOGIN_PLUGINS;
        $plugin_id = $_POST['plugin_id'];
        unset($_POST['plugin_id']);
        unset($_POST['bsauth_add_save']);
        $service = $BSLOGIN_PLUGINS[$plugin_id];
        $_POST['login_options_id'] = self::delConfig();
        $service->delConfig();
        self::displayUpdatedNotice();
      }


      if (isset($_POST["bsauth_moveup"]))
        self::moveUp($_POST["bsauth_moveup"]);
      if (isset($_POST["bsauth_movedown"]))
        self::moveDown($_POST["bsauth_movedown"]);



      // rewrite?
      if ($edit) {
        if (isset($_POST['bsauth_edit'])) {
          $login = explode("-", $_POST['bsauth_edit']);
          $_SESSION['bsauth_edit'] = $_POST['bsauth_edit'];
        } else {
          $login = explode("-", $_SESSION['bsauth_edit']);
        }
        $plugin_id = $login[0];
        $service_id = $login[1];
        self::generatePageSetupEditPage($plugin_id, $service_id);
      } elseif ($delete) {
        if (isset($_POST['bsauth_delete'])) {
          $login = explode("-", $_POST['bsauth_delete']);
          $_SESSION['bsauth_delete'] = $_POST['bsauth_delete'];
        } else {
          $login = explode("-", $_SESSION['bsauth_delete']);
        }
        $plugin_id = $login[0];
        $service_id = $login[1];
        self::generatePageSetupDeletePage($plugin_id, $service_id);
      } elseif ($add) {
        if (isset($_POST['bsauth_add'])) {
          $login = explode("-", $_POST['bsauth_add']);
          $_SESSION['bsauth_add'] = $_POST['bsauth_add'];
          unset($_POST['bsauth_add']);
        } else {
          $login = explode("-", $_SESSION['bsauth_add']);
        }
        $plugin_id = $login[0];
        $config_id = $login[1];
        self::generatePageSetupAddPage($plugin_id, $config_id);
      } else
        self::generatePageSetupOverviewPage();
    }

    //------------------------------------------------------------------------------
    function generatePageSetupAddPage($plugin_id, $config_id) {
      global $BSLOGIN_PLUGINS;
      $plugin = $BSLOGIN_PLUGINS[$plugin_id];

      if ($config_id) {
        $service_id = $plugin->addPreconfiguredService($config_id);
        self::generatePageSetupEditPage($plugin_id, $service_id);
        // TODO: possibly hide preconfigured values for preconfigures services
      } else {
        $configoptions = array();
        self::getConfigOptions($configoptions);
        $plugin->getConfigOptions($configoptions);
        BlaatSchaap::GenerateOptions($configoptions, NULL, __("BlaatLogin Service Configuration", "BlaatLogin"), "bsauth_add_save");
      }
    }

    //------------------------------------------------------------------------------
    function generatePageSetupEditPage($plugin_id, $service_id) {
      global $BSLOGIN_PLUGINS;
      $plugin = $BSLOGIN_PLUGINS[$plugin_id];
      $configoptions = array();
      self::getConfigOptions($configoptions);
      $plugin->getConfigOptions($configoptions);
      BlaatSchaap::GenerateOptions($configoptions, $plugin->getConfig($service_id), __("BlaatLogin Service Configuration", "BlaatLogin"), "bsauth_edit_save");
    }

    //------------------------------------------------------------------------------
    function generatePageSetupDeletePage($plugin_id, $service_id) {
      // TODO: MESSAGE are you sure?
      $xmlroot = new SimpleXMLElement('<div />');
      $xmlroot->addChild("h1", __("BlaatLogin Service Configuration", "BlaatLogin"));

      global $BSLOGIN_PLUGINS;
      $plugin = $BSLOGIN_PLUGINS[$plugin_id];
      $config = $plugin->getConfig($service_id);
      $login_options_id = $config['login_options_id'];
      $message = sprintf(__("Are you sure you want to delete %s", "BlaatLogin"), $config['display_name']);

      $xmlroot->addChild("div", $message);
      $xmlform = $xmlroot->addChild("form");
      $xmlform->addAttribute("method", "post");
      $xmlplugin_id = $xmlform->addChild("input");
      $xmlplugin_id->addAttribute("name", "plugin_id");
      $xmlplugin_id->addAttribute("value", $plugin_id);
      $xmlplugin_id->addAttribute("type", "hidden");

      $xmlservice_id = $xmlform->addChild("input");
      $xmlservice_id->addAttribute("name", "service_id");
      $xmlservice_id->addAttribute("value", $service_id);
      $xmlservice_id->addAttribute("type", "hidden");


      $xmlservice_id = $xmlform->addChild("input");
      $xmlservice_id->addAttribute("name", "login_options_id");
      $xmlservice_id->addAttribute("value", $login_options_id);
      $xmlservice_id->addAttribute("type", "hidden");



      $xmlyes = $xmlform->addChild("button", __("Yes"));
      $xmlyes->addAttribute("name", "bsauth_delete_save");
      //$xmlyes->addAttribute("value", $plugin_id ."-". $service_id);
      $xmlno = $xmlform->addChild("button", __("No"));
      BlaatSchaap::xml2html($xmlroot);
    }

    //------------------------------------------------------------------------------
    function generatePageSetupOverviewPage() {
      global $BSLOGIN_PLUGINS;
      $configuredServices = array();
      $preConfiguredServices = array();




      $xmlroot = new SimpleXMLElement('<div />');

      $xmlroot->addChild("h1", __("BlaatLogin Service Configuration", "BlaatLogin"));


      $xmlAddServices = $xmlroot->addChild("div");
      $xmlAddServices->addAttribute("class", "ServicesList");
      $xmlAddServices->addChild("h2", __("Add services", "BlaatLogin"));


      foreach ($BSLOGIN_PLUGINS as $plugin_id => $plugin) {
        $configuredServices_new = array_merge($configuredServices, $plugin->getServices(false));
        $configuredServices = $configuredServices_new;

        /*
          $preConfiguredServices_new = array_merge ( $preConfiguredServices ,
          $service->getPreConfiguredServices());
          $preConfiguredServices=$preConfiguredServices_new;
         */
        $xmlService = $xmlAddServices->addChild("div");

        $xmlService->addAttribute("class", "BlaatLoginServiceConfig");
        $xmltable = $xmlService->addChild("table");

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("Plugin", "BlaatLogin"));
        $xmltr->addChild("td", $plugin_id);

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("Service", "BlaatLogin"));

        $xmlform = $xmltr->addChild("td")->addChild("form");
        $xmlform->addAttribute("method", "post");
        $xmlselect = $xmlform->addChild("select");
        $xmlselect->addAttribute("name", "bsauth_add");
        foreach ($plugin->getPreConfiguredServices() as $preConfiguredService) {
          //$preConfiguredService
          $xmloption = $xmlselect->addChild("option", $preConfiguredService->display_name);
          $xmloption->addAttribute("value", $preConfiguredService->plugin_id . "-" . $preConfiguredService->service_id);
        }
        $xmlform->addChild("td")->addChild("Button", __("Add"));
      }
      $xmltr = $xmltable->addChild("tr");
      $xmltr->addChild("td");
      $xmlform = $xmltr->addChild("td")->addChild("form");
      $xmlform->addAttribute("method", "post");
      $xmlAddCustomButton = $xmlform->addChild("Button", __("Add Custom", "BlaatLogin"));
      $xmlAddCustomButton->addAttribute("value", $preConfiguredService->plugin_id . "-0");
      $xmlAddCustomButton->addAttribute("name", "bsauth_add");


      usort($configuredServices, "self::sortServices");
      $xmlroot->addChild("br");
      $xmlEditServices = $xmlroot->addChild("div");
      $xmlEditServices->addAttribute("class", "ServicesList");
      $xmlEditServices->addChild("h2", __("Edit services", "BlaatLogin"));


      $maxOrder = self::getMaxOrder();
      foreach ($configuredServices as $configuredService) {
        $xmlService = $xmlEditServices->addChild("form");
        $xmlService->addAttribute("method", "post");
        $xmlService->addAttribute("class", "BlaatLoginServiceConfig");
        $xmltable = $xmlService->addChild("table");

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("Plugin", "BlaatLogin"));
        $xmltr->addChild("td", $configuredService->plugin_id);

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("Display Name", "BlaatLogin"));
        $xmltr->addChild("td", $configuredService->display_name);

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("Enabled", "BlaatLogin"));
        $xmltr->addChild("td", $configuredService->enabled ? __("Yes") : __("No") );

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th", __("Button Preview", "BlaatLogin"));
        self::generateButton($configuredService, $xmltr->addChild("td"));

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th");
        $xmlBtn = $xmltr->addChild("td");
        $xmlUpBtn = $xmlBtn->addChild("button", __("Move Up", "BlaatLogin"));
        $xmlUpBtn->addAttribute("name", "bsauth_moveup");
        $xmlUpBtn->addAttribute("value", $configuredService->login_options_id);
        // Note: order is decreasing, so moving up is lower sort order value
        if ($configuredService->order == 1)
          $xmlUpBtn->addAttribute("disabled", "true");
        $xmlUpBtn->addAttribute("class", "BlaatLoginConfigButton");
        $xmlDownBtn = $xmlBtn->addChild("button", __("Move Down", "BlaatLogin"));
        $xmlDownBtn->addAttribute("name", "bsauth_movedown");
        $xmlDownBtn->addAttribute("value", $configuredService->login_options_id);
        // Note: order is decreasing, so moving down is higher sort order value
        if ($configuredService->order == $maxOrder)
          $xmlDownBtn->addAttribute("disabled", "true");
        $xmlDownBtn->addAttribute("class", "BlaatLoginConfigButton");

        $xmltr = $xmltable->addChild("tr");
        $xmltr->addChild("th");
        $xmlBtn = $xmltr->addChild("td");
        $xmlEditBtn = $xmlBtn->addChild("button", __("Edit"));
        $xmlEditBtn->addAttribute("name", "bsauth_edit");
        $xmlEditBtn->addAttribute("value", $configuredService->plugin_id . "-" . $configuredService->service_id);
        $xmlEditBtn->addAttribute("class", "BlaatLoginConfigButton");
        $xmlDelBtn = $xmlBtn->addChild("button", __("Delete"));
        $xmlDelBtn->addAttribute("name", "bsauth_delete");
        $xmlDelBtn->addAttribute("value", $configuredService->plugin_id . "-" . $configuredService->service_id);
        $xmlDelBtn->addAttribute("class", "BlaatLoginConfigButton");
      }
      return BlaatSchaap::xml2html($xmlroot);
    }

    //------------------------------------------------------------------------------
//------------------------------------------------------------------------------

    function getConfigOptions(&$tabs) {
      // GENERIC FIELDS // TODO move to BlaatLogin
      $GenericTab = new BlaatConfigTab("generic", __("Generic configuration", "blaat_oauth"));
      $tabs[] = $GenericTab;

      $GenericTab->addOption(new BlaatConfigOption("display_name", __("Display name", "blaat_auth"), "text", true));

      $GenericTab->addOption(new BlaatConfigOption("enabled", __("Enabled", "blaat_auth"), "checkbox", false, true));

      /* Not yet implemented, hiding the option
        $GenericTab->addOption(new BlaatConfigOption("auto_register",
        __("Auto Register","blaat_auth"),
        "checkbox",false,true));
       */
    }

//------------------------------------------------------------------------------
    function generateLoginPage() {
      global $BSLOGIN_PLUGINS;
      $xmlroot = new SimpleXMLElement("<div />");

      if (isset($_SESSION['bsauth_display_message'])) {
        //echo "<div class=bsauth_message>".$_SESSION['bsauth_display_message']."</div>";
        $xmlMessage = $xmlroot->addChild("div", $_SESSION['bsauth_display_message']);
        $xmlMessage->addAttribute("class", "bsauth_message");
        unset($_SESSION['bsauth_display_message']);
      }
      $user = wp_get_current_user();

      if (get_option("bs_debug")) {
        /*
          echo "DEBUG SESSION<pre>"; print_r($_SESSION); echo "</pre>";
          echo "DEBUG POST<pre>"; print_r($_POST); echo "</pre>";
          echo "DEBUG URL:<pre>" . blaat_get_current_url() . "</pre>";
         */
        $xmlroot->addChild("pre", "SESSION:\n" . var_export($_SESSION, true));
        $xmlroot->addChild("pre", "POST   :\n" . var_export($_POST, true));
      }

      $logged = is_user_logged_in();
      $logging = isset($_SESSION['bsauth_login']) || isset($_POST['bsauth_login']);
      $linking = isset($_SESSION['bsauth_link']) || isset($_POST['bsauth_link']);
      $regging = isset($_SESSION['bsauth_register']) || isset($_POST['bsauth_register']);

      if ($regging) {
        $regging_local = (isset($_POST['bsauth_register']) && $_POST['bsauth_register'] == "local") ||
                (isset($_SESSION['bsauth_register']) && $_SESSION['bsauth_register'] == "local");
      } else
        $regging_local = false;

      $unlinking = isset($_POST['bsauth_unlink']);


      $loginOptions = get_option("blaatlogin_login_enabled");
      $registerOptions = get_option("blaatlogin_register_enabled");
      $linkOptions = get_option("blaatlogin_link_enabled");

      // begin not loggedin, logging, linking,regging
      if (!($logged || $logging || $linking || $regging)) {



        if (!($loginOptions == "Disabled") || ($loginOptions == "RemoteOnly")) {
          $xmlLinkLogin = $xmlroot->addChild("div");
          $xmlLinkLogin->addAttribute("id", "bsauth_local");
          //echo "<div id='bsauth_local'>";
          $xmlLinkLogin->addChild("p", __("Log in with a local account", "BlaatLogin"));
          //echo "<p>" .  __("Log in with a local account","blaat_auth") . "</p>" ; 
          //wp_login_form();
          /*
           * generating login form outselves, the SimpleXML way
           * TODO: possibly in future version... write an XML/HTML class that has
           * the simplicity of SimpleXML but the flexibibility of DOMDocument
           * that way we can "import" snippets of HTML, such as the login form
           * but can still add elements with a single class rather then the
           * DOM way, where we first create an element and then add it.
           */
          $xmlLocalLinkForm = $xmlLinkLogin->addChild("form");
          $xmlLocalLinkForm->addAttribute("method", "post");
          $xmlLocalLinkForm->addAttribute("action", "/wp-login.php");
          $xmlLocalLinkRedir = $xmlLocalLinkForm->addChild("input");
          $xmlLocalLinkRedir->addAttribute("name", "redirect_to");
          $xmlLocalLinkRedir->addAttribute("type", "hidden");
          $xmlLocalLinkRedir->addAttribute("value", blaat_get_current_url()); //TODO migrate to class

          $xmlLocalLinkFormTable = $xmlLocalLinkForm->addChild("table");

          $xmlLocalLinkFormTableTr = $xmlLocalLinkFormTable->addChild("tr");
          $xmlLocalLinkFormTableTr->addChild("th", __('Username'));
          $xmlLocalLinkUser = $xmlLocalLinkFormTableTr->addChild("td")->addChild("input");
          $xmlLocalLinkUser->addAttribute("name", "log");

          $xmlLocalLinkFormTableTr = $xmlLocalLinkFormTable->addChild("tr");
          $xmlLocalLinkFormTableTr->addChild("th", __('Password'));
          $xmlLocalLinkPass = $xmlLocalLinkFormTableTr->addChild("td")->addChild("input");
          $xmlLocalLinkPass->addAttribute("name", "pwd");
          $xmlLocalLinkPass->addAttribute("type", "password");




          $xmlLocalLinkFormTableTr = $xmlLocalLinkFormTable->addChild("tr");
          $xmlLocalLinkFormTableTr->addChild("th");
          $xmlLocalLinkSub = $xmlLocalLinkFormTableTr->addChild("td")->addChild("input");
          $xmlLocalLinkSub->addAttribute("name", "wp-submit");
          $xmlLocalLinkSub->addAttribute("type", "submit");
          $xmlLocalLinkSub->addAttribute("value", __("Log in"));

          /*
            ?>
            <form method='post' action='<?php echo blaat_get_current_url(); ?>'>

            <button type='submit' value='local' name='bsauth_register'><?php
            _e("Register"); ?></button>
            </form>
            <?php
            echo "</div>";
           */
          // TODO ADD REGISTER BUTTON 
        }

        if (!($loginOptions == "Disabled") || ($loginOptions == "LocalOnly")) {

          $xmlRemoteLogin = $xmlroot->addChild("div");
          $xmlRemoteLogin->addAttribute("id", "bsauth_buttons");

          //echo "<div id='bsauth_buttons'>";
          //echo "<p>" . __("Log in with","blaat_auth") . "</p>";
          $xmlRemoteLogin->addChild("p", __("Log in with", "BlaatLogin"));

          //echo "<form action='".blaat_get_current_url()."' method='post'>";
          $xmlRemoteLoginForm = $xmlRemoteLogin->addChild("form");
          $xmlRemoteLoginForm->addAttribute("method", "post");


          $services = array();
          foreach ($BSLOGIN_PLUGINS as $plugin) {
            $services_new = array_merge($services, $plugin->getServices());
            $services = $services_new;
          }


          //echo "<pre>DEBUG:\n"; print_r($services); echo "</pre>";
          //usort($services, "bsauth_buttons_sort");
          usort($services, "BlaatLogin::sortServices");
          //echo "<pre>DEBUG:\n"; print_r($services); echo "</pre>";

          foreach ($services as $service) {
            //echo "<pre>DEBUG:\n"; print_r($service); echo "</pre>";
            //!!echo bsauth_generate_button($button,"login");
            self::generateButton($service, $xmlRemoteLoginForm, "login");
          }
          $customStyle = get_option("bsauth_custom_button");
          if ($customStyle)
            $xmlRemoteLoginForm->addChild("style", $customStyle);
          //echo "</form>";
          //echo "</div>";
          //echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
        }
        //!! MIGRATION IN PROGRESS, REMOVE LATER
        BlaatSchaap::xml2html($xmlroot);
      }
      // end not loggedin, logging, linking,regging      
      // begin logged in (show linking)
      if ($logged && ($linkOptions == "Enabled")) {

        $servicesLinked = array();
        $servicesUnlinked = array();

        foreach ($BSLOGIN_PLUGINS as $bs_service) {
          $services = $bs_service->getServicesLinked($user->ID);

          $buttonsLinked_new = array_merge($servicesLinked, $services['linked']);
          $buttonsUnlinked_new = array_merge($servicesUnlinked, $services['unlinked']);
          $servicesLinked = $buttonsLinked_new;
          $servicesUnlinked = $buttonsUnlinked_new;
        }

        usort($servicesLinked, "BlaatLogin::sortServices");
        usort($servicesUnlinked, "BlaatLogin::sortServices");

        $xmlForm = $xmlroot->addChild("form");
        $xmlForm->addAttribute("action", BlaatSchaap::getCurrentURL());
        $xmlForm->addAttribute("method", "post");
        $xmlLink = $xmlForm->addChild("div");
        $xmlLink->addAttribute("class", 'link authservices');
        $xmlLinkTitle = $xmlLink->addChild("div", __("Link your account to", "BlaatLogin"));

        $xmlUnlink = $xmlForm->addChild("div");
        $xmlUnlink->addAttribute("class", 'unlink authservices');
        $xmlUnlinkTitle = $xmlUnlink->addChild("div", __("Unlink your account from", "BlaatLogin"));

        foreach ($servicesLinked as $linked) {
          self::generateButton($linked, $xmlUnlink, "unlink");
        }

        foreach ($servicesUnlinked as $unlinked) {
          self::generateButton($unlinked, $xmlLink, "link");
        }
        //!! MIGRATION IN PROGRESS, REMOVE LATER
        BlaatSchaap::xml2html($xmlroot);
      }
      // end logged in (show linking)
      // TODO ?? show something when ($logging && $linkOptions!="Enabled")




      if ($regging && ($registerOptions == "RemoteOnly" ||
              $registerOptions == "Both" ||
              ($registerOptions == "HonourGlobal" &&
              get_option('users_can_register'))) &&
              !$linking && !$regging_local) {
        if (isset($_SESSION['new_user']))
          $new_user = $_SESSION['new_user'];

        $xmlForm = $xmlroot->addChild("form");
        $xmlForm->addAttribute("action", BlaatSchaap::getCurrentURL());
        $xmlForm->addAttribute("method", "post");
        $xmlRegister = $xmlForm->addChild("div");
        $xmlRegister->addAttribute("class", 'link authservices');
        $xmlRegisterTitle = $xmlRegister->addChild("div", __("Please provide a username and e-mail address to complete your signup", "BlaatLogin"));
        $xmlTable = $xmlForm->addChild("table");

        $xmlTableTr = $xmlTable->addChild("tr");
        $xmlTableTr->addChild("th", __('Username'));
        $xmlRemoteRegisterUser = $xmlTableTr->addChild("td")->addChild("input");
        $xmlRemoteRegisterUser->addAttribute("name", "username");
        if (isset($new_user['user_login']))
          $xmlRemoteRegisterUser->addAttribute("value", $new_user['user_login']);

        // TODO Options Enable/Disable fields, add more fields
        $xmlTableTr = $xmlTable->addChild("tr");
        $xmlTableTr->addChild("th", __('Email'));
        $xmlRemoteRegisterEmail = $xmlTableTr->addChild("td")->addChild("input");
        $xmlRemoteRegisterEmail->addAttribute("name", "email");
        $xmlRemoteRegisterEmail->addAttribute("type", "email");
        if (isset($new_user['user_email']))
          $xmlRemoteRegisterEmail->addAttribute("value", $new_user['user_email']);

        $xmlTableTr = $xmlTable->addChild("tr");
        //$xmlTableTr->addChild("th");

        $xmlRemoteRegisterCancel = $xmlTableTr->addChild("td")->addChild("button", __("Cancel"));
        $xmlRemoteRegisterCancel->addAttribute("type", "submit");
        $xmlRemoteRegisterCancel->addAttribute("value", "1");
        $xmlRemoteRegisterCancel->addAttribute("name", "cancel");
        $xmlRemoteRegisterSubmit = $xmlTableTr->addChild("td")->addChild("button", __("Register"));
        $xmlRemoteRegisterSubmit->addAttribute("value", "1");
        $xmlRemoteRegisterSubmit->addAttribute("name", "register");
        $xmlRemoteRegisterSubmit->addAttribute("type", "submit");

        if ($linkOptions == "Enabled") {
          $xmlTableTr = $xmlTable->addChild("tr");
          $xmlTableTr->addChild("th");
          $xmlRemoteRegisterLink = $xmlTableTr->addChild("td")->addChild("button", __("Link to existing account", "BlaatLogin"));
          $xmlRemoteRegisterLink->addAttribute("value", $_SESSION['bsauth_register']);
          $xmlRemoteRegisterLink->addAttribute("name", "bsauth_link");
          $xmlRemoteRegisterLink->addAttribute("type", "submit");
        }

        //!! MIGRATION IN PROGRESS, REMOVE LATER
        BlaatSchaap::xml2html($xmlroot);
        /*
          ?><form action='<?php echo blaat_get_current_url() ?>'method='post'>
          <table>
          <tr><td><?php _e("Username"); ?></td><td><input name='username' value='<?php if (isset($new_user['user_login'])) echo htmlspecialchars($new_user['user_login']); ?>'</td></tr>
          <?php if (get_option("bs_auth_signup_user_email") != "Disabled") { ?>
          <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email' value='<?php if (isset($new_user['user_email'])) echo htmlspecialchars($new_user['user_email']); ?>' ></td></tr>
          <?php } ?>
          <tr><td><button name='cancel' type=submit><?php _e("Cancel"); ?></button></td><td><button name='register' value='1' type=submit><?php _e("Register"); ?></button></td></tr>
          <tr><td></td><td><button name='bsauth_link' value='<?php echo htmlspecialchars($_SESSION['bsauth_register']); ?>' type='submit'><?php _e("Link to existing account", "blaat_auth"); ?></button></td></td></tr>
          </table>
          </form>
          <?php
          //printf( __("If you already have an account, please click <a href='%s'>here</a> to link it.","blaat_auth") , site_url("/".get_option("link_page")));
         * 
         */
      }


      if ($regging && $linking && ($linkOptions == "Enabled") && !$regging_local) {
        $service = $_SESSION['bsauth_display'];
        $xmlLinkLogin = $xmlroot->addChild("div");
        $xmlLinkLogin->addAttribute("id", "bsauth_local");
        //echo "<div id='bsauth_local'>";
        $xmlLinkLogin->addChild("p", __("Log in with a local account", "BlaatLogin"));

        $xmlLocalLinkForm = $xmlLinkLogin->addChild("form");
        $xmlLocalLinkForm->addAttribute("method", "post");
        $xmlLocalLinkForm->addAttribute("action", "/wp-login.php");
        $xmlLocalLinkRedir = $xmlLocalLinkForm->addChild("input");
        $xmlLocalLinkRedir->addAttribute("name", "redirect_to");
        $xmlLocalLinkRedir->addAttribute("type", "hidden");
        $xmlLocalLinkRedir->addAttribute("value", BlaatSchaap::getCurrentURL()); //TODO migrate to class

        $xmlLocalLinkFormTable = $xmlLocalLinkForm->addChild("table");

        $xmlLocalLinkFormTableTr = $xmlLocalLinkFormTable->addChild("tr");
        $xmlLocalLinkFormTableTr->addChild("th", __('Username'));
        $xmlLocalLinkUser = $xmlLocalLinkFormTableTr->addChild("td")->addChild("input");
        $xmlLocalLinkUser->addAttribute("name", "log");

        $xmlLocalLinkFormTableTr = $xmlLocalLinkFormTable->addChild("tr");
        $xmlLocalLinkFormTableTr->addChild("th", __('Password'));
        $xmlLocalLinkPass = $xmlLocalLinkFormTableTr->addChild("td")->addChild("input");
        $xmlLocalLinkPass->addAttribute("name", "pwd");
        $xmlLocalLinkPass->addAttribute("type", "password");




        $xmlLocalLinkFormTableTr = $xmlLocalLinkFormTable->addChild("tr");
        $xmlLocalLinkFormTableTr->addChild("th");
        $xmlLocalLinkSub = $xmlLocalLinkFormTableTr->addChild("td")->addChild("input");
        $xmlLocalLinkSub->addAttribute("name", "wp-submit");
        $xmlLocalLinkSub->addAttribute("type", "submit");
        $xmlLocalLinkSub->addAttribute("value", __("Link Account", "BlaatLogin"));

        //!! MIGRATION IN PROGRESS, REMOVE LATER
        BlaatSchaap::xml2html($xmlroot);
        /*
          echo "<div id='bsauth_local'>";
          printf("<p>" . __("Please provide a local account to link to %s", "blaat_auth") . "</p>", $service);
          wp_login_form();
          echo "</div>";
         * 
         */
      }


      // begin regging 
      if ($regging_local) {

        if (!(get_option("bs_auth_hide_local"))) {
          echo "<div id='bsauth_local'>";
          echo "<p>" . __("Enter a username, password and e-mail address to sign up", "blaat_auth") . "</p>";
          ?>
          <form ection='<?php blaat_get_current_url(); ?>' method=post>
            <table>
              <tr><td><?php _e("Username"); ?></td><td><input name='username'></td></tr>
              <tr><td><?php _e("Password"); ?></td><td><input type='password' name='password'></td></tr>
              <tr><td><?php _e("E-mail Address"); ?></td><td><input name='email'></td></tr>
              <tr><td><button name='cancel' type=submit><?php _e("Cancel"); ?></button></td><td><button name='register'  type='submit'><?php _e("Register"); ?></button></td></tr>
            </table>
          </form>
          <?php
          echo "</div>";
        }

        echo "<style>" . htmlspecialchars(get_option("bsauth_custom_button")) . "</style>";
      }

      // end regging
    }

    //------------------------------------------------------------------------------  
    function generateButton($configuredService, &$xmlroot, $action = NULL) {


      $xmlbutton = $xmlroot->addChild("button");
      $xmlbutton->addAttribute("class", 'bs-auth-btn');
      if ($action) {
        $xmlbutton->addAttribute("name", "bsauth_$action");
        $xmlbutton->addAttribute("value", $configuredService->plugin_id . "-" . $configuredService->service_id);
        $xmlbutton->addAttribute("type", "submit");
      }


      $xmllogo = $xmlbutton->addChild("span", " "); //HTML5/XHTML incompatibility, no <span /> allowed?
      // might not be needed with the new generation code
      $xmllogo->addAttribute("class", "bs-auth-btn-logo");
      $xmllogo->addAttribute("style", "background-image:url(\"" . $configuredService->icon . "\");");

      $xmltext = $xmlbutton->addChild("span", $configuredService->display_name);
      $xmltext->addAttribute("class", 'bs-auth-btn-text');
    }

    //------------------------------------------------------------------------------
    function sortServices($a, $b) {
      if ($a->order == $b->order)
        return 0;
      return ($a->order < $b->order) ? -1 : 1;
    }

    //------------------------------------------------------------------------------
  }

}
?>
