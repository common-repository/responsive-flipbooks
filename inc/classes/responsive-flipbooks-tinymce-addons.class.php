<?php

namespace PeakResponsiveFlipbooks\Classes;

if(!defined('ABSPATH')) {
  die("Can't load this file directly.");
}

class ResponsiveFlipbooksTinymceAddons {
  public function __construct() {
    add_action('admin_init', array($this, 'flipbook_admin_init'));
  }

  public function flipbook_admin_init() {
    if(current_user_can('edit_posts') && current_user_can('edit_pages')) {
      add_filter('mce_buttons', array($this, 'flipbook_filter_mce_button'));
      add_filter('mce_external_plugins', array($this, 'flipbook_filter_mce_plugin'));
    }
  }

  public function flipbook_filter_mce_button($buttons) {
    array_push($buttons, '|', 'flipbook_button', '|', 'add_flipbook');

    return $buttons;
  }

  public function flipbook_filter_mce_plugin($plugins) {
    $plugins['flipbook_button'] = plugin_dir_url(__FILE__).'js/flipbook_button_plugin.js';
    $plugins['add_flipbook'] = plugin_dir_url(__FILE__).'js/flipbook_button_plugin.js';

    return $plugins;
  }
}
