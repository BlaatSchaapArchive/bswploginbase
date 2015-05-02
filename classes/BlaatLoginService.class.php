<?php

class BlaatLoginService{
  

  public $plugin_id;
  public $service_id;
  public $display_name;
  public $order;
  public $icon;
  public $enabled;
  public $login_options_id;

  function BlaatLoginService($plugin, $id, $display_name, $order, $icon, $enabled, $login_options_id) {
    $this->plugin_id=$plugin;
    $this->service_id=$id;
    $this->display_name=$display_name;
    $this->order=$order;
    $this->icon=$icon;
	$this->enabled=$enabled;
	$this->login_options_id=$login_options_id;
  }
}

?>
