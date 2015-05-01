<?php

class BlaatLoginService{
  

  public $plugin_id;
  public $service_id;
  public $display_name;
  public $order;
  public $icon;
  public $enabled;

  function BlaatLoginService($plugin, $id, $display_name, $order, $icon, $enabled) {
    $this->plugin_id=$plugin;
    $this->service_id=$id;
    $this->display_name=$display_name;
    $this->order=$order;
    $this->icon=$icon;
	$this->enabled=$enabled;
  }
}

?>
