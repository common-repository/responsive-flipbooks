<?php
/**
 * Plugin Name: Responsive Flipbooks
 * Description: Plugin provides easy managing process of responsive HTML5 flipbooks in wordpress.
 * Version: 1.0
 * Author: Peak Technologies Ltd.
 * Author URI: http://peaktechnologies.ru
 */
require_once 'inc/loader.php';
$pluginLoader = new \PeakResponsiveFlipbooks\ResponsiveFlipbooksLoader;

/**
 * Register additional WYSIWYG button that inserts flipbook shortcode.
 */
$responsive_flipbooks_tinymce_addons = new \PeakResponsiveFlipbooks\Classes\ResponsiveFlipbooksTinymceAddons;


