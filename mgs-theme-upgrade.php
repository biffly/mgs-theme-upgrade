<?php
/*
Plugin Name: MGS Theme Upgrade
Plugin URI: https://github.com/biffly/mgs-theme-upgrade/
Description: Permite agregar funcionalidades nuevas a su tema y controlar algunas que la mayoria de los themas premiun no dejan.
Version: 0.2
Author: Marcelo Scenna
Author URI: http://www.marceloscenna.com.ar
Text Domain: mgs-theme-upgrade
*/

if( !defined('ABSPATH') ){ exit; }
//error_reporting(E_ALL & ~E_NOTICE);

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/biffly/mgs-theme-upgrade',
	__FILE__,
	'MGS-Theme-Upgrade/mgs-theme-upgrade.php'
);

if( !defined('MGS_THEME_UPG_VERSION') )             define('MGS_THEME_UPG_VERSION', '0.2');
if( !defined('MGS_THEME_UPG_BASENAME') )			define('MGS_THEME_UPG_BASENAME', plugin_basename(__FILE__));
if( !defined('MGS_THEME_UPG_PLUGIN_DIR') ) 			define('MGS_THEME_UPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
if( !defined('MGS_THEME_UPG_PLUGIN_DIR_URL') )		define('MGS_THEME_UPG_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));

include('inc/class-main.php');
if( is_admin() ){
    include('inc/class-admin.php');
}

register_activation_hook(__FILE__, 'mgs_tu_activation');
register_deactivation_hook(__FILE__, 'mgs_tu_activation');




function mgs_tu_activation(){
    delete_option('mgs-tu-default-images-sizes');
    delete_option('mgs-tu-images-disabled-sizes');
    delete_option('mgs-tu-images-disabled');
}