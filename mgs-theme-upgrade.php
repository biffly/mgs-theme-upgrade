<?php
/*
Plugin Name: MGS Theme Upgrade
Plugin URI: http://www.marceloscenna.com.ar
Description: Permite agregar funcionalidades nuevas a su tema y controlar algunas que la mayoria de los themas premiun no dejan.
Version: 1.0.0
Author: Marcelo Scenna
Author URI: http://www.marceloscenna.com.ar
Text Domain: mgs-theme-upgrade
*/

if( !defined('ABSPATH') ){ exit; }
error_reporting(E_ALL & ~E_NOTICE);

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/biffly/mgs-theme-upgrade',
	__FILE__,
	'MGS-Theme-Upgrade/mgs-theme-upgrade.php'
);