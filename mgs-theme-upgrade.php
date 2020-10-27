<?php
/*
Plugin Name: MGS Theme Upgrade
Plugin URI: https://github.com/biffly/mgs-theme-upgrade/
Description: Permite agregar funcionalidades nuevas a su tema y controlar algunas que la mayoria de los themas premiun no dejan.
Version: 0.6
Author: Marcelo Scenna
Author URI: http://www.marceloscenna.com.ar
Text Domain: mgs-theme-upgrade
*/

if( !defined('ABSPATH') ){ exit; }
error_reporting(E_ALL & ~E_NOTICE);

require 'includes/plugin-update-checker-4.10/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/biffly/mgs-theme-upgrade',
	__FILE__,
	'MGS-Theme-Upgrade/mgs-theme-upgrade.php'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

if( !defined('MGS_THEME_UPG_VERSION') )             define('MGS_THEME_UPG_VERSION', '0.6');
if( !defined('MGS_THEME_UPG_BASENAME') )			define('MGS_THEME_UPG_BASENAME', plugin_basename(__FILE__));
if( !defined('MGS_THEME_UPG_PLUGIN_DIR') ) 			define('MGS_THEME_UPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
if( !defined('MGS_THEME_UPG_PLUGIN_DIR_URL') )		define('MGS_THEME_UPG_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
if( !defined('MGS_THEME_UPG_GIT') )             	define('MGS_THEME_UPG_GIT', 'biffly/mgs-theme-upgrade');
if( !defined('MGS_THEME_UPG_NAME') )             	define('MGS_THEME_UPG_NAME', 'MGS-Theme-Upgrade');


if( !get_option(MGS_THEME_UPG_NAME.'-mgs-tu-default-images-sizes') ) update_option(MGS_THEME_UPG_NAME.'-mgs-tu-default-images-sizes', mgs_tu_get_all_image_sizes());
$imgs_sizes_arr = [];
foreach( get_option(MGS_THEME_UPG_NAME.'-mgs-tu-default-images-sizes') as $k=>$v ){
	$imgs_sizes_arr[$k][0] = $k.' ('.$v['width'].'x'.$v['height'].')';
	$imgs_sizes_arr[$k][1] = $v['disabled'];
}

$config = [
	'funcs'             => [
		'label'     => __('Funcionalidades', 'mgs-theme-upgrade'),
		'fields'    => [
			'mgs-tu-readmore'               => [
				'wpml'              => false,
				'type'              => 'onoff',
				'label'             => __('Leer más', 'mgs-theme-upgrade'),
				'desc'              => __('Funcionalidad que permite crear un texto con la opcion de <i>Leer más</i> al estilo Facebook.', 'mgs-theme-upgrade'),
				'def'               => '',
				'more-help'			=> '',
			],
			'mgs-tu-readmore-text'          => [
				'wpml'              => true,
				'type'              => 'text',
				'label'             => __('Texto', 'mgs-theme-upgrade'),
				'desc'              => __('Etiqueta de texto que se agregara despues del primer parrafo.', 'mgs-theme-upgrade'),
				'def'               => __('Leer más', 'mgs-theme-upgrade'),
				'dependent'         => 'mgs-tu-readmore'
			],
			'mgs-tu-readmore-text-speed'          => [
				'wpml'              => false,
				'type'              => 'text',
				'label'             => __('Velocidad', 'mgs-theme-upgrade'),
				'desc'              => __('Velocidad con la que aparece el texto.', 'mgs-theme-upgrade'),
				'def'               => 500,
				'dependent'         => 'mgs-tu-readmore'
			]
		]
	],
	
	'imgs'              => [
		'label'     => __('Imagenes', 'mgs-theme-upgrade'),
		'icon'		=> 'far fa-images',
		'fields'    => [
			'mgs-tu-images-disabled'        => [
				'wpml'              => false,
				'type'              => 'onoff',
				'label'             => __('Desactivar tamaños', 'mgs-theme-upgrade'),
				'desc'              => __('Desactiva la creación de algunas imagenes.', 'mgs-theme-upgrade'),
				'def'               => '',
				//'disabled'          => MGS_TU_IMAGES_DESABLED,
				//'disabled_why'      => __('Se detecto otro plugin que ya realiza esta tarea para desactivar la creación de imagenes hagalo desde sus opciones.', 'mgs-theme-upgrade')

			],
			'mgs-tu-images-disabled-sizes'  => [
				'wpml'          => false,
				'type'          => 'checkboxes',
				'label'         => __('Desactivar', 'mgs-theme-upgrade'),
				'desc'          => __('Seleccione cuales tamaños de imagenes desea desactivar el procesamiento.<br>Solo es posible con las imagenes del tema, no se puede desactivar la creacion de thumbs por defecto de Wordpress', 'mgs-theme-upgrade'),
				'def'           => '',
				'values'        => $imgs_sizes_arr,
				'class'         => 'mgs-tu-chk_small',
				'dependent'     => 'mgs-tu-images-disabled'
			],
		]
	],
	
	'css'               => [
		'label'     => 'CSS',
		'icon'		=> 'fab fa-css3',
		'fields'    => [
			'mgs-tu-css'                    => [
				'wpml'              => false,
				'type'              => 'onoff',
				'label'             => __('CSS personalizada', 'mgs-theme-upgrade'),
				'desc'              => __('Carga un CSS personalizado que no se vera afectado por las actualizaciones de su tema.'),
				'def'               => '',
			],
			'mgs-tu-css-test-folder'        => [
				'wpml'              => false,
				'type'              => 'test',
				'label'             => __('Carpeta', 'mgs-theme-upgrade'),
				'desc'              => __('Cree una carpeta <code>mgs-tu</code> dentro de la carpeta de su tema.', 'mgs-theme-upgrade'),
				'dependent'         => 'mgs-tu-css',
				'func'              => 'test_folder_css'
			],
			'mgs-tu-css-test-file'          => [
				'wpml'              => false,
				'type'              => 'test',
				'label'             => __('Archivo', 'mgs-theme-upgrade'),
				'desc'              => __('Cree un archivo <code>main.css</code> denro de <code>mgs-tu</code> en la carpeta de su tema.', 'mgs-theme-upgrade'),
				'dependent'         => 'mgs-tu-css',
				'func'              => 'test_file_css'
			]
		]
	],
	
	'correos'           => [
		'label'     => __('Correos', 'mgs-theme-upgrade'),
		'icon'		=> 'fas fa-at',
		'fields'    => [
			'mgs-tu-correos'                => [
				'wpml'              => false,
				'type'              => 'onoff',
				'label'             => __('Activar opciones de correo', 'mgs-theme-upgrade'),
				'desc'              => __('Agrega opciones especiales a los correos por defecto de wordpress.', 'mgs-theme-upgrade'),
				'def'               => '',
			],
			'mgs-tu-correos-sender-name'    => [
				'wpml'              => true,
				'type'              => 'text',
				'label'             => __('Nombre', 'mgs-theme-upgrade'),
				'desc'              => __('Nombre asignado a la dirección desde donde se envian los correos', 'mgs-theme-upgrade'),
				'def'               => get_bloginfo('name'),
				'labeled'           => '',
				'dependent'         => 'mgs-tu-correos'
			],
			'mgs-tu-correos-sender-dir'     => [
				'wpml'              => false,
				'type'              => 'text',
				'label'             => __('Dirección', 'mgs-theme-upgrade'),
				'desc'              => __('Dirección de correo desde donde se enviaran los mails de wordpress.', 'mgs-theme-upgrade'),
				'def'               => '',
				'dependent'         => 'mgs-tu-correos'
			]
		]
	],
	
	'addons'			=> [
		'label'     => __('AddOns', 'mgs-theme-upgrade'),
		'icon'		=> 'fas fa-puzzle-piece',
		'color'		=> '#FF9100',
		'fields'	=> [
			//LIGHTBOX
			'addon-lightbox'						=> [
				'wpml'              => false,
				'type'              => 'onoff',
				'label'             => __('MGS Lightbox', 'mgs-theme-upgrade'),
				'desc'              => __('Opción que permite mostrar en un lightbox la meta información de una imagen.', 'mgs-theme-upgrade'),
				'def'               => '',
			],
			'addon-lightbox-moreinfo-enabled'	=> [
				'wpml'              => false,
				'type'              => 'onoff',
				'label'             => __('Más info', 'mgs-theme-upgrade'),
				'desc'              => __('Funcionalidad que permite agregar un campo HTML a los META de una imagen.', 'mgs-theme-upgrade'),
				'def'               => '',
				'more-help'			=> __('Esta opción permite agregar una descripción en <strong>HTML</strong> a las imagenes cuando son subidas o desde la sección de <i>Multimedia</i>.', 'mgs-theme-upgrade'),
				'dependent'			=> 'addon-lightbox'
			],
			
			//MGS BLOG SHORTCODE
			'mgs-blog-shortcode'				=> [
				'wpml'				=> false,
				'type'              => 'onoff',
				'label'             => __('MGS Blog <i>Beta</i>', 'mgs-theme-upgrade'),
				'desc'              => __('Crear listados de entradas o Custom Posts Types personalizadas.', 'mgs-theme-upgrade'),
				'def'               => '',
				'more-help'			=> __('Beta: todavia sin implementar.', 'mgs-theme-upgrade'),
			],
		]
	]
];

require_once('class/class-main.php');
$mgs = new MGS_Theme_Upgrade($config);

if( $mgs->get_field_value('addon-lightbox') ){
	require_once('includes/mgs-lightbox/mgs-lightbox.php');
	new MGS_LightBox_AddOn($mgs);
}


if( is_admin() ){
    require_once('class/class-mgs-admin.php');
	require_once('class/class-admin.php');
	new MGS_Theme_Upgrade_Admin($config);
}

register_activation_hook(__FILE__, 'mgs_tu_activation');
register_deactivation_hook(__FILE__, 'mgs_tu_activation');




function mgs_tu_activation(){
    delete_option('mgs-tu-default-images-sizes');
    delete_option('mgs-tu-images-disabled-sizes');
    delete_option('mgs-tu-images-disabled');
	delete_option('mgs-tu-readmore');
}

function mgs_tu_get_all_image_sizes(){
	global $_wp_additional_image_sizes;
	$default_image_sizes = get_intermediate_image_sizes();
	//$image_sizes = [];

	foreach( $default_image_sizes as $size ){
		$image_sizes[$size]['width'] = intval(get_option("{$size}_size_w"));
		$image_sizes[$size]['height'] = intval(get_option("{$size}_size_h"));
		$image_sizes[$size]['crop'] = get_option("{$size}_crop") ? get_option("{$size}_crop") : false;
	}
	if( isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes) ){
		$image_sizes = array_merge($image_sizes, $_wp_additional_image_sizes);
	}

	$image_sizes['thumb']['disabled'] = 1;
	$image_sizes['thumbnail']['disabled'] = 1;
	$image_sizes['medium']['disabled'] = 1;
	$image_sizes['medium_large']['disabled'] = 1;
	$image_sizes['large']['disabled'] = 1;
	return $image_sizes;
}
