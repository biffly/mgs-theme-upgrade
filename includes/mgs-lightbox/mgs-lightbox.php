<?php
if( !defined('ABSPATH') ){ exit; }
error_reporting(E_ALL & ~E_NOTICE);

//ADDON dir
define('MGS_LIGHTBOX_ADDON_DIR', MGS_THEME_UPG_PLUGIN_DIR.'includes/mgs-lightbox/');
define('MGS_LIGHTBOX_ADDON_DIR_URL', MGS_THEME_UPG_PLUGIN_DIR_URL.'includes/mgs-lightbox/');
//ACF dirs
define('MGS_LIGHTBOX_ADDON_ACF_PATH', MGS_LIGHTBOX_ADDON_DIR.'includes/acf/');
define('MGS_LIGHTBOX_ADDON_ACF_URL', MGS_LIGHTBOX_ADDON_DIR_URL.'includes/acf/');

add_action('fusion_builder_before_init', 'mgs_lightbox_addon_fusion_builder_init');
add_action('elementor/widgets/widgets_registered', 'mgs_lightbox_addon_init_widgets_elementor');

if( !class_exists('MGS_LightBox_AddOn') ){
	class MGS_LightBox_AddOn extends MGS_Theme_Upgrade{
		public static $ACF;
		public static $lightbox_enabled;
		public static $mce_js_url;
		private $parent;
		
		function __construct($parent){
			//carga configuracion y opciones
			$this->parent = $parent;
			self::$ACF = ( $this->parent->get_field_value('addon-lightbox-moreinfo-enabled') ) ? true : false;
			self::$lightbox_enabled = ( $this->parent->get_field_value('addon-lightbox') ) ? true : false;
			
			if( self::$ACF ) $this->Enabled_ACF();
			
			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
			add_shortcode('mgs_lightbox_addon', [$this, 'mgs_lightbox_addon_build']);
			add_shortcode('mgs_gallery_lightbox_addon', [$this, 'mgs_gallery_lightbox_addon_build']);
			
			
			//ADD ADMIN OPTIONS
			if( is_admin() ){
				self::$mce_js_url = MGS_LIGHTBOX_ADDON_DIR_URL.'assets/js/mgs-tinymce.js';
				if( self::$lightbox_enabled ){
					add_filter('mce_external_plugins', [$this, 'mce_shortcode_button_init_mce_external_plugins']);
					add_filter('mce_buttons', [$this, 'mce_shortcode_button_init_mce_buttons']);
					add_editor_style(MGS_LIGHTBOX_ADDON_DIR_URL.'assets/css/editor-style.css');

				}
			}
		}
		
		public function enqueue_scripts(){
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-fancybox-js', MGS_LIGHTBOX_ADDON_DIR_URL.'assets/js/jquery.fancybox.min.js');
			wp_enqueue_style('jquery-fancybox-css', MGS_LIGHTBOX_ADDON_DIR_URL.'assets/css/jquery.fancybox.min.css');
			wp_enqueue_style('mgs-lightbox-css', MGS_LIGHTBOX_ADDON_DIR_URL.'assets/css/main.css');
		}
		
		public function mgs_gallery_lightbox_addon_build($attr){
			$out = '';
			if( !isset($attr['img_id']) && !isset($attr['avada_img']) ) return false;
			
			$imgs_array = explode(',', $attr['img_id']);
			$attr['size'] = ( $attr['size'] ) ? $attr['size'] : 'medium';
			
			$uniqid = uniqid();
			$sc_id = 'mgs-gallery-lightbox-addon-sc-'.$uniqid;
			$gallery_id = 'mgs-gallery-lightbox-'.$uniqid;
			$out = '
				<div class="mgs-gallery-lightbox">
					<!--<pre>'.print_r($attr, true).'</pre>-->
					<div id="'.$sc_id.'" class="mgs-gallery-lightbox-warpper columns-'.$attr['cols'].' '.$attr['class'].' theme-'.$attr['theme'].'">
			';
			foreach( $imgs_array as $_id_img ){
				$title = get_the_title($_id_img);
				$all_img_info = $this->get_attachment_info($_id_img);
				$img = wp_get_attachment_image(
					$_id_img, 
					$attr['size'], 
					false, 
					[
						'class'	=>'mgs-lightbox-img mgs-lightbox-img-'.$attr['size'],
						'alt'	=> $title,
					]
				);

				$img_full_url = wp_get_attachment_url($_id_img);
				$img_full = wp_get_attachment_image(
					$_id_img, 
					'', 
					false, 
					[
						'class'	=>'mgs-lightbox-img-full ',
						'alt'	=> $title,
					]
				);
				if( $attr['layout']=='image' ){
					$out .= '
						<a data-fancybox="'.$gallery_id.'" href="'.$img_full_url.'" title="'.$title.'" class="mgs-gallery-lightbox-item">'.$img.'</a>
					';
				}elseif( $attr['layout']=='image_text' ){
					$uniqid_single = uniqid();
					$out .= $this->build_link($gallery_id, $uniqid_single, $title, $img, $attr);
					$out .= '
						<div class="mgs-lightbox-warpper theme-'.$attr['theme'].'" id="mgs-lightbox-'.$uniqid_single.'" style="display: none;">
							<div class="mgs-lightbox-grid">
								<div class="mgs-lightbox-img">
									<div class="mgs-lightbox-img-warpper">'.$img_full.'</div>
								</div>
								<div class="mgs-lightbox-content">
									<div class="mgs-lightbox-content-warper">
										'.$this->build_content($attr, $title, $all_img_info).'
									</div>
								</div>
							</div>
						</div>
					';
				}elseif( $attr['layout']=='text' ){
					$uniqid_single = uniqid();
					$out .= $this->build_link($gallery_id, $uniqid_single, $title, $img, $attr);
					$out .= '
						<div class="mgs-lightbox-warpper" id="mgs-lightbox-'.$uniqid_single.'" style="display: none;">
							<div class="mgs-lightbox-content">
								<div class="mgs-lightbox-content-warper">
									'.$this->build_content($attr, $title, $all_img_info).'
								</div>
							</div>
						</div>
					';
			}
				
			}
			$out .= '
					</div>
				</div>
			';
			
			return $out;
		}
		
		private function build_link($gallery_id, $uniqid_single, $title, $img, $attr){
			$out = '<a data-fancybox="'.$gallery_id.'" data-src="#mgs-lightbox-'.$uniqid_single.'" href="javascript:;" title="'.$title.'"  class="mgs-gallery-lightbox-item">';
			$out .= $img;
			if( $attr['title_list'] ){
				$out .= '<p class="mgs-gallery-lightbox-caption">'.$title.'</p>';
			}
			$out .= '</a>';
			return $out;
		}
		
		private function build_content($attr, $title, $all_img_info){
			$out = '';
			if( $attr['title']=='true' ){
				$out .= '<h3 class="mgs-lightbox-content-title">'.$title.'</h3>';
			}
			if( $attr['desc']=='plano' ){
				$out.= '<span class="mgs-lightbox-content-desc plano">'.$all_img_info['desc'].'</span>';
			}if( $attr['desc']=='html' ){
				$out.= '<span class="mgs-lightbox-content-desc html">'.$all_img_info['desc_html'].'</span>';
			}
			return $out;
		}
		
		public function mgs_lightbox_addon_build($attr){
			$out = '';
			
			if( !isset($attr['img_id']) && isset($attr['avada_img']) ){
				$attr['img_id'] = $this->get_attachment_id($attr['avada_img']);
			}
			$attr['size'] = ( $attr['size'] ) ? $attr['size'] : 'medium';
			
			$uniqid = uniqid();
			$sc_id = 'mgs-lightbox-addon-sc-'.$uniqid;
			$out .= '<span id="'.$sc_id.'" class="mgs-lightbox-addon-sc '.$attr['class'].'">';
			//$out .= '<pre>'.print_r($attr, true).'</pre>';
			
			$title = get_the_title($attr['img_id']);
			$all_img_info = $this->get_attachment_info($attr['img_id']);
			$img = wp_get_attachment_image(
				$attr['img_id'], 
				$attr['size'], 
				false, 
				[
					'class'	=>'mgs-lightbox-img',
					'alt'	=> $title,
				]
			);
			
			$img_full_url = wp_get_attachment_url($attr['img_id']);
			$img_full = wp_get_attachment_image(
				$attr['img_id'], 
				'', 
				false, 
				[
					'class'	=>'mgs-lightbox-img-full ',
					'alt'	=> $title,
				]
			);
			
			
			if( $attr['layout']=='image' ){
				$out .= '
					<a data-fancybox href="'.$img_full_url.'" title="'.$title.'">'.$img.'</a>
				';
			}elseif( $attr['layout']=='image_text' ){
				$out .= '
					<a data-fancybox data-src="#mgs-lightbox-'.$uniqid.'" href="javascript:;" title="'.$title.'">'.$img.'</a>
					<div class="mgs-lightbox-warpper" id="mgs-lightbox-'.$uniqid.'" style="display: none;">
						<div class="mgs-lightbox-grid">
							<div class="mgs-lightbox-img">
								<div class="mgs-lightbox-img-warpper">'.$img_full.'</div>
							</div>
							<div class="mgs-lightbox-content">
								<div class="mgs-lightbox-content-warper">
				';
				if( $attr['title']=='true' ){
					$out .= '		<h3 class="mgs-lightbox-content-title">'.$title.'</h3>';
				}
				if( $attr['desc']=='plano' ){
					$out.= '		<span class="mgs-lightbox-content-desc plano">'.$all_img_info['desc'].'</span>';
				}if( $attr['desc']=='html' ){
					$out.= '		<span class="mgs-lightbox-content-desc html">'.$all_img_info['desc_html'].'</span>';
				}
				$out .= '
									
								</div>
							</div>
						</div>
					</div>
				';
			}elseif( $attr['layout']=='text' ){
				$out .= '
					<a data-fancybox data-src="#mgs-lightbox-'.$uniqid.'" href="javascript:;" title="'.$title.'">'.$img.'</a>
					<div class="mgs-lightbox-warpper" id="mgs-lightbox-'.$uniqid.'" style="display: none;">
						<div class="mgs-lightbox-content">
							<div class="mgs-lightbox-content-warper">
				';
				if( $attr['title']=='true' ){
					$out .= '	<h3 class="mgs-lightbox-content-title">'.$title.'</h3>';
				}
				if( $attr['desc']=='plano' ){
					$out.= '	<span class="mgs-lightbox-content-desc plano">'.$all_img_info['desc'].'</span>';
				}if( $attr['desc']=='html' ){
					$out.= '	<span class="mgs-lightbox-content-desc html">'.$all_img_info['desc_html'].'</span>';
				}
				$out .= '
							</div>
						</div>
					</div>
				';
			}
			
			
			
			$out .= '</span>';
			return $out;
		}
		
		private function get_attachment_info($attachment_id){
			$attachment = get_post($attachment_id);
			return [
    			'alt'			=> ( get_post_meta($attachment->ID, '_wp_attachment_image_alt', true) ) ? ( get_post_meta($attachment->ID, '_wp_attachment_image_alt', true) ) : $attachment->post_title,
				'caption'		=> $attachment->post_excerpt,
				'desc'			=> $attachment->post_content,
				'title'			=> $attachment->post_title,
				'desc_html'		=> get_field('field_5f6e180c9ffca', $attachment_id)
			];
		}
		
		private function Enabled_ACF(){
			include_once(MGS_LIGHTBOX_ADDON_ACF_PATH.'acf.php');
			add_filter('acf/settings/url', function($url){
				return MGS_LIGHTBOX_ADDON_ACF_URL;
			});
			add_filter('acf/settings/show_admin', function($show_admin){
				return false;
			});
			add_action('acf/init', function(){
				//crea campo
				acf_add_local_field_group([
					'key' => 'mgs_group_5f6e17e7e3c8d',
					'title' => 'MGS Media Options',
					'fields' => [
						[
							'key' => 'field_5f6e180c9ffca',
							'label' => 'Descripción',
							'name' => 'descripcion',
							'type' => 'wysiwyg',
							'instructions' => '',
							'required' => 0,
							'conditional_logic' => 0,
							'wrapper' => [
								'width' => '',
								'class' => '',
								'id' => '',
							],
							'default_value' => '',
							'tabs' => 'all',
							'toolbar' => 'full',
							'media_upload' => 0,
							'delay' => 0,
						],
					],
					'location' => [
						[
							[
								'param' => 'attachment',
								'operator' => '==',
								'value' => 'image',
							],
						],
					],
					'menu_order' => 0,
					'position' => 'acf_after_title',
					'style' => 'default',
					'label_placement' => 'top',
					'instruction_placement' => 'label',
					'hide_on_screen' => '',
					'active' => true,
					'description' => ''
				]);
			});
		}
		
		public function mce_shortcode_button_init_mce_external_plugins($plugin_array){
			$screen = get_current_screen();
			if( !current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing')=='true' && $screen->parent_file=='edit.php' && $screen->post_type=='post' ) return;
			$plugin_array['mgs_lightbox_mce_button'] = self::$mce_js_url;
    		return $plugin_array;
		}
		public function mce_shortcode_button_init_mce_buttons($buttons){
			$screen = get_current_screen();
			if( !current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true' && $screen->parent_file=='edit.php' && $screen->post_type=='post' ) return;
			$buttons[] = "mgs_lightbox_mce_button";
    		return $buttons;
		}
	}
}


function mgs_lightbox_addon_init_widgets_elementor(){
	$elementor_elements = [
		'MGS_Ligtbox_Elementor'   => [
			'file'      => 'elementor-lightbox.php',
			'name'      => 'Lightbox',
			'ico'       => 'fa fa-bars',
			'ver'       => '1.0.0'
		],
		'MGS_Gallery_Ligtbox_Elementor'   => [
			'file'      => 'elementor-gallery-lightbox.php',
			'name'      => 'Lightbox Gallery',
			'ico'       => 'fa fa-bars',
			'ver'       => '1.0.0'
		],
	];
	require_once(MGS_LIGHTBOX_ADDON_DIR.'elementor/elementor.php');
	foreach( $elementor_elements as $k=>$v ){
		if( get_option($k, 1)==0 ){
		}else{
			if( file_exists(MGS_LIGHTBOX_ADDON_DIR.'elementor/'.$v['file']) ){
				update_option($k, 1, false);
				require_once(MGS_LIGHTBOX_ADDON_DIR.'elementor/'.$v['file']);    
				\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new $k );
			}else{
				update_option($k, 0, false);
			}
		}
	}
}

function mgs_lightbox_addon_fusion_builder_init(){
			global $fusion_settings, $pagenow;
			$builder_status = function_exists('is_fusion_editor') && is_fusion_editor();
			
			fusion_builder_map(
				[
					'name'			=> 'MGS LightBox AddOn',
					'shortcode'		=> 'mgs_lightbox_addon',
					'icon'			=> '',
					'params'		=> [
						[
							'type'        => 'upload',
							'heading'     => 'Imagen',
							'param_name'  => 'avada_img',
							'value'       => '',
						],
						[
							'type'          => 'radio_button_set',
							'heading'       => 'Diseño',
							'description'	=> 'Seleccione que desea mostrar en el lightbox',
							'param_name'    => 'layout',
							'default'       => 'image',
							'value'         => [
								'image'			=> 'Solo la imagen',
								'image_text'	=> 'Imagen y texto',
								'text'			=> 'Solo texto',
							],
						],
						[
							'type'          => 'radio_button_set',
							'heading'       => 'Titulo',
							'param_name'    => 'title',
							'default'       => 'true',
							'value'         => [
								'true'		=> 'Mostrar',
								'false'		=> 'ocultar',
							],
							'dependency'  => [
								[
									'element'  => 'layout',
									'value'    => 'image',
									'operator' => '!=',
								],
							],
						],
						[
							'type'          => 'radio_button_set',
							'heading'       => 'Descripción',
							'param_name'    => 'desc',
							'default'       => 'html',
							'value'         => [
								'html'		=> 'Texto HTML',
								'plano'		=> 'Texto plano',
							],
							'dependency'  => [
								[
									'element'  => 'layout',
									'value'    => 'image',
									'operator' => '!=',
								],
							],
						],
						[
							'type'        => 'textfield',
							'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
							'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
							'param_name'  => 'class',
							'value'       => '',
						],
					]
				]
			);
		}