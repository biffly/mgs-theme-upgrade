<?php
if( !defined('ABSPATH') ){ exit; }
//error_reporting(E_ALL & ~E_NOTICE);

//https://code.elementor.com/php-hooks/#elementorelementsection_namesection_idbefore_section_end

//ADDON dir
define('MGS_STOPGUESTS_DIR', MGS_THEME_UPG_PLUGIN_DIR.'includes/mgs-stopguests/');
define('MGS_STOPGUESTS_DIR_URL', MGS_THEME_UPG_PLUGIN_DIR_URL.'includes/mgs-stopguests/');

use Elementor\Controls_Manager;
use Elementor\Plugin;


if( !class_exists('MGS_StopGuests') ){
	class MGS_StopGuests extends MGS_Theme_Upgrade{
		public static $stopguests_enabled;
		private $parent;
		const VERSION = '0.0.1';
		private $elementSettings = [];
		public static $roles;
		
		
		function __construct($parent){
			global $wp_roles;
			if( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();
			self::$roles['todos'] = 'Cualquiera';
			foreach( $wp_roles->roles as $k=>$v ){
				self::$roles[$k] = $v['name'];
			}
			
	
		
		
			//carga configuracion y opciones
			$this->parent = $parent;
			self::$stopguests_enabled = ( $this->parent->get_field_value('mgs-stop-guests') ) ? true : false;
			
			if( is_admin() ){
				//ELEMENTOR
				//estilos para el editor
				add_action('elementor/editor/after_enqueue_styles', [$this, 'mgs_sotop_guests_register_styles_editor']);
				//agrega opcion
				add_action('elementor/element/section/section_advanced/after_section_end', [$this, 'mgs_stopguests_add_elementor_control'], 10, 2);
				
				//AVADA
				add_action('fusion_builder_shortcodes_init', [$this, 'fusion_init_stop_guests']);

			}
			//ELEMENTOR
			add_action('elementor/frontend/section/before_render', [$this, 'mgs_stopguests_verifica_antes'], 10, 1);
			add_action('elementor/frontend/section/after_render', [$this, 'mgs_stopguests_verifica_despues'], 10, 1);
			//AVADA
			add_filter('do_shortcode_tag', [$this, 'test_container'], 10, 3);
		}
		
		
		public function fusion_init_stop_guests(){
			require_once MGS_STOPGUESTS_DIR.'mgs-stopguests-FB.php';
		}
		
		public function test_container($output, $tag, $attr){
			/*
			//ACCION OCULTAR
			if( $tag==='fusion_builder_container' && $attr['mgs_stopguests_enabled']=='true' && $attr['mgs_stopguests_action']=='hide' ){
				//OCULTA A USERS LOGUED
				if( $attr['mgs_stopguests_if']==='if-logued' && is_user_logged_in() ){
					$output = '<!-- MGS StopGuests -->';
				//OCULTA A USUARIOS NON LOGUED
				}elseif( $attr['mgs_stopguests_if']==='not_logued' && !is_user_logged_in() ){
					$output = '<!-- MGS StopGuests -->';
				}
			}
			*/			
			if( $tag==='fusion_builder_container' && $attr['mgs_stopguests_enabled']=='true' && $attr['mgs_stopguests_action']=='hide' ){
				if( $attr['mgs_stopguests_if']=='if-logued' && is_user_logged_in() ){
					$output = '<!-- MGS StopGuests -->';
				}elseif( $attr['mgs_stopguests_if']=='not_logued' && !is_user_logged_in() ){
					$output = '<!-- MGS StopGuests -->';
				}
			}elseif( $tag==='fusion_builder_container' && $attr['mgs_stopguests_enabled']=='true' && $attr['mgs_stopguests_action']=='show' ){
				//$output .= '<pre>'.print_r($attr, true).'</pre>';
				if( $attr['mgs_stopguests_if']=='if-logued' && is_user_logged_in() ){
					if( $attr['mgs_stopguests_if_role']=='todos' ){
						//muestro
					}else{
						$user = $user ? new WP_User( $user ) : wp_get_current_user();
						if( in_array($attr['mgs_stopguests_if_role'], (array)$user->roles) ){
							//muestro
						}else{
							$output = '<!-- MGS StopGuests -->';
						}
					}
				}elseif( $attr['mgs_stopguests_if']=='not_logued' && !is_user_logged_in() ){
					//muestro
				}else{
					$output = '<!-- MGS StopGuests -->';
				}
			}
			return $output;
		}
		
		public function checkCondition($settings){
			if( $this->getMode()==='edit' ){
            	return false;
        	}

			$settings = $settings['mgs_stopguests'];
			$hide = false;
			if( $settings['enabled'] && $settings['action']==='hide' ){
				if( $settings['if']==='if-logued' && is_user_logged_in() ){
					$hide = true;
				}elseif( $settings['if']==='not_logued' && !is_user_logged_in() ){
					$hide = true;
				}else{
					$hide = false;
				}
			}elseif( $settings['enabled'] && $settings['action']==='show' ){
				
				if( $settings['if']==='if-logued' && is_user_logged_in() ){
					if( $settings['if_role']=='todos' ){
						$hide = false;
					}else{
						$user = $user ? new WP_User( $user ) : wp_get_current_user();
						if( in_array($settings['if_role'], (array)$user->roles) ){
							$hide = false;	
						}else{
							$hide = true;
						}
					}
				}elseif( $settings['if']==='not_logued' && !is_user_logged_in() ){
					$hide = false;
				}else{
					$hide = true;
				}
			}
			return $hide;
			
		}
		
		
		public function mgs_sotop_guests_register_styles_editor(){
			wp_enqueue_style('mgs_stop_guests_ele_editor_css', MGS_STOPGUESTS_DIR_URL.'assets/css/admin.css', ['elementor-editor'], self::VERSION);
		}
			
		public function mgs_stopguests_verifica_antes($section){
			
			if( $this->getMode()==='edit' ){
            	return;
        	}
			$settings = $this->getElementSettings($section);
			$hide = $this->checkCondition($settings);
			$this->renderDebug($settings);
			
			if( !$hide ){
            	return;
        	}
			
			$section->mgs_stopguestsIsHidden = true;
        	$section->mgs_stopguestsSettings = $settings;
        	ob_start();
		}
		
		public function mgs_stopguests_verifica_despues($section){
			if( empty($section) || empty($section->mgs_stopguestsIsHidden) ){
            	return;
        	}
			ob_end_clean();
			$type = $section->get_type();
        	echo "<!-- hidden $type -->";
		}
		
		public function mgs_stopguests_add_elementor_control($element, $args){
			$element->start_controls_section(
				'mgs_stopguests_section',
				[
					'tab'				=> Controls_Manager::TAB_ADVANCED,
					'label'				=> 'MGS StopGuests',
				]
			);
			$element->add_control(
				'mgs_stopguests_enabled',
				[
					'label'				=> 'Activar?',
					'type'				=> \Elementor\Controls_Manager::SWITCHER,
					'label_on'			=> 'Si',
					'label_off'			=> 'No',
					'return_value'		=> 'true',
					'default'			=> 'false',
				]
			);
			$element->add_control(
				'mgs_stopguests_if',
				[
					'label'				=> 'Condición',
					'type'				=> \Elementor\Controls_Manager::SELECT,
					'default'			=> '',
					'options'			=> [
						'if-logued'  		=> 'Usuario logueado',
						'not_logued'		=> 'Usuario sin loguear',
					],
					'condition' => [
						'mgs_stopguests_enabled'	=> 'true',
					]
				]
			);
			$element->add_control(
				'mgs_stopguests_if_role',
				[
					'label'				=> 'Role',
					'type'				=> \Elementor\Controls_Manager::SELECT,
					'default'			=> '',
					'options'			=> self::$roles,
					'condition' => [
						'mgs_stopguests_enabled'	=> 'true',
						'mgs_stopguests_if'			=> 'if-logued'
					]
				]
			);
			$element->add_control(
				'mgs_stopguests_action',
				[
					'label'				=> 'Acción',
					'type'				=> \Elementor\Controls_Manager::SELECT,
					'default'			=> '',
					'options'			=> [
						'hide'  		=> 'Ocultar',
						'show'			=> 'Mostrar',
					],
					'condition' => [
						'mgs_stopguests_enabled'	=> 'true',
					]
				]
			);
			
			
			
			
			
			$element->add_control(
				'mgs_stopguests_debug',
				[
					'label'				=> 'Debug',
					'type'				=> \Elementor\Controls_Manager::SWITCHER,
					'label_on'			=> 'Si',
					'label_off'			=> 'No',
					'return_value'		=> 'true',
					'default'			=> 'false',
					'condition' => [
						'mgs_stopguests_enabled'	=> 'true',
					]
				]
			);
			$element->add_control(
				'mgs_stopguests_section_footer',
				[
					'label'				=> 'MGS StopGuests Footer',
					'type'				=> \Elementor\Controls_Manager::RAW_HTML,
					'raw'				=> '<div class="logo"></div><div class="ver">MGS StopGuests '.self::VERSION.'</div><div class="ver">MGS ThemeUpgrade '.MGS_THEME_UPG_VERSION.'</div>',
					'content_classes'	=> 'mgs-stopguests-seccion-footer',
					'show_label'		=> false
				]
			);
			$element->end_controls_section();
		}
		
		private function getMode(){
			if( !empty(Plugin::$instance->editor) && Plugin::$instance->editor->is_edit_mode() ){
				return 'edit';
			}

			if( !empty(Plugin::$instance->preview) && Plugin::$instance->preview->is_preview_mode() ){
				return 'preview';
			}

			return 'website';
		}
		
		private function getElementSettings($element){
        	$id = $element->get_id();
			$clonedElement = clone $element;
			
			$this->elementSettings[$id]['mgs_stopguests']['enabled'] = $element->get_settings_for_display('mgs_stopguests_enabled');
			$this->elementSettings[$id]['mgs_stopguests']['if'] = $element->get_settings('mgs_stopguests_if');
			$this->elementSettings[$id]['mgs_stopguests']['if_role'] = $element->get_settings('mgs_stopguests_if_role');
			$this->elementSettings[$id]['mgs_stopguests']['action'] = $element->get_settings('mgs_stopguests_action');
			$this->elementSettings[$id]['mgs_stopguests']['debug'] = $element->get_settings('mgs_stopguests_debug');
			
			return $this->elementSettings[$id];
		}
		
		public function renderDebug($s){
			if( $s['debug'] ){
				echo '<pre>'.print_r($s, true).'</pre>';
			}
		}
	}
}
