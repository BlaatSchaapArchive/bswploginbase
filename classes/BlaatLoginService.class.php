<?php

class BlaatLoginService{
  

  public $plugin;
  public $id;
  public $display_name;
  public $order;
  public $icon;

  function BlaatLoginService($plugin, $id, $display_name, $order, $icon) {
    $this->plugin=$plugin;
    $this->id=$id;
    $this->display_name=$display_name;
    $this->order=$order;
    $this->icon=$icon;
  }
}

?>
