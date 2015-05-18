<?php
if (class_exists("BlaatSchaap")) {

  // possible extend BlaatSchaap -- in a later phase of development
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

      $GenericTab->addOption(new BlaatConfigOption("display_name", __("Display name", "BlaatLogin"), "text", true));

      $GenericTab->addOption(new BlaatConfigOption("enabled", __("Enabled", "BlaatLogin"), "checkbox", false, true));

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
      
      $reggingGlobalAllowed = $registerOptions == "Both" ||
          ($registerOptions == "HonourGlobal" && get_option('users_can_register'));
      $reggingRemoteOnly = ($registerOptions == "RemoteOnly"); 
      $reggingLocalOnly = ($registerOptions == "LocalOnly"); 

      
      
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
          $xmlLocalLinkSub->addAttribute("value", __("Log in"));



          if (($registerOptions == "LocalOnly" ||
                  $registerOptions == "Both" ||
                  ($registerOptions == "HonourGlobal" &&
                  get_option('users_can_register')))) {

            $xmlLocalRegisterForm = $xmlLinkLogin->addChild("form");
            $xmlLocalRegisterForm->addAttribute("method", "post");
            $xmlLocalRegisterForm->addAttribute("action", BlaatSchaap::getCurrentURL());
            $xmlLocalRegisterButton = $xmlLocalRegisterForm->addChild("button", __("Register"));
            $xmlLocalRegisterButton->addAttribute("value", "local");
            $xmlLocalRegisterButton->addAttribute("name", "bsauth_register");
            $xmlLocalRegisterButton->addAttribute("type", "submit");
          }
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


          usort($services, "BlaatLogin::sortServices");

          foreach ($services as $service) {
            self::generateButton($service, $xmlRemoteLoginForm, "login");
          }
          $customStyle = get_option("bsauth_custom_button");
          if ($customStyle)
            $xmlRemoteLoginForm->addChild("style", $customStyle);
        }
      } // end not loggedin, logging, linking,regging      
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
      }
      // end logged in (show linking)
      // TODO ?? show something when ($logging && $linkOptions!="Enabled")


      if ($regging && ( $reggingRemoteOnly|| $reggingGlobalAllowed )
               &&
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
      }


      if ($regging && $linking && ($linkOptions == "Enabled") && !$regging_local) {
        $service = $_SESSION['bsauth_display'];
        $xmlLinkLogin = $xmlroot->addChild("div");
        $xmlLinkLogin->addAttribute("id", "bsauth_local");
        $xmlLinkLogin->addChild("p", __("Log in with a local account", "BlaatLogin"));

        $xmlLocalLinkForm = $xmlLinkLogin->addChild("form");
        $xmlLocalLinkForm->addAttribute("method", "post");
        $xmlLocalLinkForm->addAttribute("action", "/wp-login.php");
        $xmlLocalLinkRedir = $xmlLocalLinkForm->addChild("input");
        $xmlLocalLinkRedir->addAttribute("name", "redirect_to");
        $xmlLocalLinkRedir->addAttribute("type", "hidden");
        $xmlLocalLinkRedir->addAttribute("value", BlaatSchaap::getCurrentURL()); 

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
      }


      // begin regging local
      if ($regging_local && ( $reggingLocalOnly|| $reggingGlobalAllowed ) ) {
        // TODO :: What are the differences between the local and remote
        // login forms??? Possibly merge!!! no $new_user, added password field
        $xmlForm = $xmlroot->addChild("form");
        $xmlForm->addAttribute("action", BlaatSchaap::getCurrentURL());
        $xmlForm->addAttribute("method", "post");
        $xmlRegister = $xmlForm->addChild("div");
        $xmlRegister->addAttribute("class", 'link authservices');
        $xmlRegisterTitle = $xmlRegister->addChild("div", __("Enter a username, password and e-mail address to sign up", "BlaatLogin"));
        $xmlTable = $xmlForm->addChild("table");

        $xmlTableTr = $xmlTable->addChild("tr");
        $xmlTableTr->addChild("th", __('Username'));
        $xmlLocalRegisterUser = $xmlTableTr->addChild("td")->addChild("input");
        $xmlLocalRegisterUser->addAttribute("name", "username");

        $xmlTableTr = $xmlTable->addChild("tr");
        $xmlTableTr->addChild("th", __('Password'));
        $xmlLocalRegisterEmail = $xmlTableTr->addChild("td")->addChild("input");
        $xmlLocalRegisterEmail->addAttribute("name", "password");
        $xmlLocalRegisterEmail->addAttribute("type", "password");

// TODO Options Enable/Disable fields, add more fields
        $xmlTableTr = $xmlTable->addChild("tr");
        $xmlTableTr->addChild("th", __('Email'));
        $xmlLocalRegisterEmail = $xmlTableTr->addChild("td")->addChild("input");
        $xmlLocalRegisterEmail->addAttribute("name", "email");
        $xmlLocalRegisterEmail->addAttribute("type", "email");


        $xmlTableTr = $xmlTable->addChild("tr");
        //$xmlTableTr->addChild("th");

        $xmlLocalRegisterCancel = $xmlTableTr->addChild("td")->addChild("button", __("Cancel"));
        $xmlLocalRegisterCancel->addAttribute("type", "submit");
        $xmlLocalRegisterCancel->addAttribute("value", "1");
        $xmlLocalRegisterCancel->addAttribute("name", "cancel");
        $xmlLocalRegisterSubmit = $xmlTableTr->addChild("td")->addChild("button", __("Register"));
        $xmlLocalRegisterSubmit->addAttribute("value", "1");
        $xmlLocalRegisterSubmit->addAttribute("name", "register");
        $xmlLocalRegisterSubmit->addAttribute("type", "submit");
      }
      // end regging

      //$reggingGlobalAllowed $reggingRemoteOnly $reggingLocalOnly
      
      if ($regging &! $reggingGlobalAllowed){
        if ($regging_local && $reggingRemoteOnly) {
          $message = __("Local user registrations have been disabled, only remote registrations are allowed.","BlaatLogin");
        } elseif (!$regging_local && $reggingLocalOnly) {
          $message = __("Remote user registrations have been disabled, only local registrations are allowed.","BlaatLogin");
        } else {
          $message = __("User registrations have been disabled.","BlaatLogin");
        }
        $xmlMessage = $xmlroot->addChild("div", $message);
        $xmlMessage->addAttribute("class", "bsauth_message");
      }
      
      if ($linking && ($linkOptions =! "Enabled")){
        $message = __("User linking have been disabled.","BlaatLogin");
        $xmlMessage = $xmlroot->addChild("div", $message);
        $xmlMessage->addAttribute("class", "bsauth_message");
      }
      
      if ($logging &!
        (!($loginOptions == "Disabled") ||
        ($loginOptions == "LocalOnly")) &&
        !$logged) {
        $message = __("Remote logging in has been disabled.","BlaatLogin");
        $xmlMessage = $xmlroot->addChild("div", $message);
        $xmlMessage->addAttribute("class", "bsauth_message");
      }
      
      $customCSS = get_option("bsauth_custom_button");
      if ($customCSS)
        $xmlroot->addChild("style", $customCSS);
      BlaatSchaap::xml2html($xmlroot);
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

    function processLogin() {


      global $BSLOGIN_PLUGINS;
      global $wpdb;
      //$user = wp_get_current_user();
      $loginOptions = get_option("blaatlogin_login_enabled");
      $registerOptions = get_option("blaatlogin_register_enabled");
      $linkOptions = get_option("blaatlogin_link_enabled");

      $logged = is_user_logged_in();
      $logging = isset($_SESSION['bsauth_login']) || isset($_POST['bsauth_login']);
      $linking = isset($_SESSION['bsauth_link']) || isset($_POST['bsauth_link']);
      $regging = isset($_SESSION['bsauth_register']) || isset($_POST['bsauth_register']);
      $unlinking = isset($_POST['bsauth_unlink']);

      if ($regging && isset($_POST['cancel'])) {
        unset($_SESSION['bsauth_register']);
        unset($_SESSION['bsauth_plugin']);
        unset($_SESSION['bsauth_login_id']);
        $regging = false;
      }

      if ($regging && $linking) {
        $_SESSION['bsauth_link'] = $_SESSION['bsauth_register'];
      }

      if ($regging && $logged) {
        unset($_SESSION['bsauth_register']);
      }

      // begin linking 
      if ($logged && $linking && ($linkOptions == "Enabled")) {
        if (isset($_SESSION['bsauth_link'])) {
          $link = explode("-", $_SESSION['bsauth_link']);
          unset($_SESSION['bsauth_link']);
        }
        if (isset($_POST['bsauth_link'])) {
          $link = explode("-", $_POST['bsauth_link']);
          $_SESSION['bsauth_link'] = $_POST['bsauth_link'];
        }

        $plugin_id = $link[0];
        $link_id = $link[1];
        $plugin = $BSLOGIN_PLUGINS[$plugin_id];

        $status = $plugin->Link($link_id);
        switch ($status) {
          case AuthStatus::LinkSuccess :
            $_SESSION['bsauth_display_message'] = sprintf(__("Your %s account has been linked", "BlaatLogin"), $_SESSION['display_name']);
            unset($_SESSION['bsauth_link']);
            unset($_SESSION['bsauth_register']);
            break;
          case AuthStatus::LinkInUse :
            $_SESSION['bsauth_display_message'] = sprintf(__("Your %s account has is already linked to another local account", "BlaatLogin"), $_SESSION['display_name']);
            unset($_SESSION['bsauth_link']);
            break;
          default :
            $_SESSION['bsauth_display_message'] = "Unkown status while attempting to link" . $status;
          //$_SESSION['debug_status'] = $status;
        }
      }
      // end linkin
      // begin unlinking 
      if ($logged && $unlinking && ($linkOptions == "Enabled")) {
        $unlink = explode("-", $_POST['bsauth_unlink']);

        $plugin_id = $unlink[0];
        $link_id = $unlink[1];
        $plugin = $BSLOGIN_PLUGINS[$plugin_id];
        if ($plugin->Unlink($link_id)) {
          $_SESSION['bsauth_display_message'] = sprintf(__("You are now unlinked from %s.", "BlaatLogin"), $_SESSION['display_name']);
        } else {
          // unlink error
        }
        unset($_SESSION['bsauth_unlink']);
      }
      // end unlinking
      // 
      // begin loggin in
      /*
       * 
       */
      if ($logging &&
              (!($loginOptions == "Disabled") ||
              ($loginOptions == "LocalOnly")) &&
              !$logged) {
        if (isset($_POST['bsauth_login'])) {
          $login = explode("-", $_POST['bsauth_login']);
          $_SESSION['bsauth_login'] = $_POST['bsauth_login'];
        } else {
          $login = explode("-", $_SESSION['bsauth_login']);
        }
        $plugin_id = $login[0];
        $login_id = $login[1];


        if (isset($plugin_id) && isset($login_id)) {
          $service = $BSLOGIN_PLUGINS[$plugin_id];
          if ($service != null) {
            $result = $service->Login($login_id);
            switch ($result) {
              case AuthStatus::Busy :
                break;
              case AuthStatus::LoginSuccess :
                //logged in
                unset($_SESSION['bsauth_login']);
                unset($_SESSION['bsauth_plugin']);
                unset($_SESSION['bsauth_login_id']);
                unset($_SESSION['bsauth_register_userinfo']);
                $userinfo = wp_get_current_user();
                if (strlen($userinfo->display_name)) {
                  $display_name = $userinfo->display_name;
                } else {
                  $display_name = $userinfo->display_login;
                }
                $_SESSION['bsauth_display_message'] = sprintf(__("Welcome back, %s.", "BlaatLogin"), $display_name);
                break;
              case AuthStatus::LoginMustRegister :
                // does this work now?
                $_SESSION['bsauth_register'] = $_SESSION['bsauth_login'];
                unset($_SESSION['bsauth_login']);
                //$_SESSION['bsauth_display_message'] = "TODO:: EXTERNAL SIGNUP"; 
                break;
              case AuthStatus::Error :
                $_SESSION['bsauth_display_message'] = "Unkown error";
                break;
              default :
                $_SESSION['bsauth_display_message'] = "Unkown status while attempting to log in";
              //$_SESSION['debug_status'] = $result;
            }
          } else {
            $_SESSION['bsauth_display_message'] = __("Invalid plugin", "BlaatLogin");
          }
        } else {
          $_SESSION['bsauth_display_message'] = __("Invalid request", "BlaatLogin");
        }
      }
      // end loggin in
      // 
      // begin regging
      if ($regging && !$logged) {
        if (!isset($_SESSION['bsauth_register'])) {
          $_SESSION['bsauth_register'] = $_POST['bsauth_register'];
        }
        $register = explode("-", $_SESSION['bsauth_register']);

        $plugin_id = $register[0];
        if ($plugin_id == "local") {
          $local = true;
        } else {
          $login_id = $register[1];
          $local = false;
        }




        if (!isset($new_user))
          $new_user = array();

        if (isset($_POST['username']))
          $new_user['user_login'] = $_POST['username'];
        if (isset($_POST['email']))
          $new_user['user_email'] = $_POST['email'];
        //if (isset($_POST['password'])) $new_user['user_pass']= wp_hash_password($_POST['password']);
        //*NEW* users need *UNHASHED* password, *EXISTING* users need *HASHED* password.
        //Speaking about consistency....
        if (isset($_POST['password']))
          $new_user['user_pass'] = $_POST['password'];

        if (isset($new_user))
          $_SESSION['bsauth_register_userinfo'] = $new_user;

        if (isset($new_user) && (isset($new_user['user_login']) &&
                ( isset($new_user['user_email']) || (get_option("bs_auth_signup_user_email") != "Required") )
                ) && ( isset($_POST['register']) || $_SESSION['bsauth_register_auto'] )) {
          if (!isset($new_user['user_pass']))
            $new_user['user_pass'] = wp_hash_password(wp_generate_password());
          $user_id = wp_insert_user($new_user);
          if (is_numeric($user_id)) {
            unset($_SESSION['bsauth_register']);
            $_SESSION['bsauth_registered'] = 1;
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            $canRegister = ($registerOptions == "Both" ||
                    ($registerOptions == "HonourGlobal" &&
                    get_option('users_can_register')));
            if ($local && ($registerOptions == "LocalOnly" || $canRegister)) {
              $_SESSION['bsauth_display_message'] = sprintf(__("Welcome to %s.", "BlaatLogin"), get_bloginfo('name'));
            } elseif ($registerOptions == "RemoteOnly" || $canRegister) {

              global $BSLOGIN_PLUGINS;
              $serviceToLink = $BSLOGIN_PLUGINS[$plugin_id];
              if ($serviceToLink) {
                if ($serviceToLink->Link($login_id)) {
                  $_SESSION['bsauth_display_message'] = sprintf(__("Welcome to %s.", "BlaatLogin"), get_bloginfo('name'));
                } else {
                  // This should never happen. Cannot sign up with already linked account.
                  // but it did?
                  $_SESSION['bsauth_display_message'] = __("An error occurred while registering your account.", "BlaatLogin");
                }
              } else {
                $_SESSION['bsauth_display_message'] = __("Plugin not registered.", "BlaatLogin");
              }
            } else {
              $_SESSION['bsauth_display_message'] = __("Registration has been disabled.", "BlaatLogin");
            }
          } else {
            $_SESSION['bsauth_display_message'] = __($user_id->get_error_message());
          }
        }
      }
      // end regging
    }

  }

}
?>
