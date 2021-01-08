<?php
require_once('class-mgs-admin.php');

if( !class_exists('MGS_Theme_Upgrade_Admin') ){
	class MGS_Theme_Upgrade_Admin extends MGS_Admin_Class{
		public function __construct($config){
			$this->slug = 'mgs_theme_upgrade_page';
			$this->plg_url = MGS_THEME_UPG_PLUGIN_DIR_URL;
			$this->plg_git = MGS_THEME_UPG_GIT;
			$this->plg_ver = MGS_THEME_UPG_VERSION;
			$this->plg_name = MGS_THEME_UPG_NAME;
			$this->admin_option = [
				'page_title'	=> 'MGS Theme Upgrade',
				'menu_title'	=> 'MGS Theme Upgrade',
				'capability'	=> 'manage_options',
				'menu_slug'		=> $this->slug,
			];
			$this->settings = $config;
			
			add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
			
			//add_action('admin_bar_menu', [$this, 'add_toolbar_items'], 100);
			//add_action('wp_dashboard_setup', [$this, 'mgs_tu_dashboard_widget']);
        }
		
		public function on_plugins_loaded(){
			self::$compatibility['gutenberg'] = true;
			self::$compatibility['elementor'] = $this->is_elementor();
			self::$compatibility['avada'] = ( class_exists('FusionBuilder') ) ? true : false;
            self::$compatibility['wpml'] = ( function_exists('icl_object_id') ) ? true : false;
			
			add_action('admin_bar_menu', [$this, 'add_toolbar_items'], 100);
			add_action('wp_dashboard_setup', [$this, 'mgs_tu_dashboard_widget']);
			$this->load();
			//var_dump(self::$compatibility);
		}
		
		public function mgs_tu_dashboard_widget(){
			global $wp_meta_boxes;
			wp_add_dashboard_widget('mgs-tu-widget', 'MGS Theme Upgrade', [$this, 'mgs_tu_dashboard_widget_render']);
		}
		
		public function mgs_tu_dashboard_widget_render(){
			$git = $this->get_git();
		?>
				<div class="header">
					<div class="brand">
						<span class="logo"></span>
						<span class="text">MGS Theme Upgrade</span>
						<span class="ver">V<?php echo $this->plg_ver?></span>
					</div>
					<div class="git">
						<p class="last-ver">Última versión: V<?php echo $git->tag_name?></p>
						<p class="git-link"><a href="https://github.com/biffly/mgs-theme-upgrade" title="Visitar repositorio" target="_blank">Visitar repositorio</a></p>
					</div>
				</div>
				<div class="content">
					<?php echo $this->build_sections_widget()?>
				</div>
		<?php
		}
		
		public function add_toolbar_items($admin_bar){
			$admin_bar->add_menu(
				[
					'id'    => 'mgs-TU',
					'title'	=> 'MGS Theme Upgrade',
					'href'  => admin_url('options-general.php?page='.$this->slug),
					'meta'  => [
						'title'	=> 'MGS Theme Upgrade'
					],
				]
			);
			
		}
		
		public function test_folder_css($return='flag'){
            $flag = false;
            if( is_dir(get_stylesheet_directory().'/mgs-tu') ){
                $flag = true;
            }
            
            if( $return=='ico' ){
                if( $flag ){
                    return 'folder open outline';
                    
                }else{
                    return 'folder open outline';
                }
            }elseif( $return=='class' ){
                if( $flag ){
                    return 'positive';
                }else{
                    return 'negative';
                }
            }elseif( $return=='text' ){
                if( $flag ){
                    return __('Carpeta encontrada.', 'mgs-tu-upgrade');
                }else{
                    return __('Carpeta no encontrada.', 'mgs-tu-upgrade');
                }
            }else{
                return $flag;
            }
        }
        
        public function test_file_css($return='flag'){
            $flag = false;
            if( file_exists(get_stylesheet_directory().'/mgs-tu/main.css') ){
                $flag = true;
            }
            
            if( $return=='ico' ){
                if( $flag ){
                    return 'file code outline';
                }else{
                    return 'file code outline';
                }
            }elseif( $return=='class' ){
                if( $flag ){
                    return 'positive';
                }else{
                    return 'negative';
                }
            }elseif( $return=='text' ){
                if( $flag ){
                    return __('Archivo encontrado.', 'mgs-tu-upgrade');
                }else{
                    return __('Archivo no encontrado.', 'mgs-tu-upgrade');
                }
            }else{
                return $flag;
            }
        }
		
		private function build_sections_widget(){
			$out = '<ul>';
			foreach( $this->settings as $id_seccion=>$attrs ){
				if( $attrs['icon']=='' ) $attrs['icon'] = 'fas fa-info';
				$out .= '
					<li class="item">
						<div class="section">
							<span class="ico"><i class="'.$attrs['icon'].'"></i></span>
							<span class="text">'.$attrs['label'].'</span>
						</div>
						<div class="section-content section-content-'.$id_seccion.'">
							'.$this->build_sections_content_widget($attrs['fields']).'
						</div>
					</li>
				';
			}
			$out .= '</ul>';
			return $out;
		}
		
		private function build_sections_content_widget($attrs){
			$out = '<ul>';
			if( !$attrs ) return;
			foreach( $attrs as $id=>$ops ){
				if( $this->is_dependent($ops) ) continue;
				$name = $this->get_field_name($id, $ops);
            	$val = $this->get_field_value($id, $ops);
				$est = ( $val ) ? '<i class="fas fa-power-off"></i>' : '<i class="fas fa-power-off"></i>';
				$out .= '
					<li>
						<div class="description">'.$ops['desc'].'</div>
						<div class="est est-'.$val.'"><span>'.$est.'</span></div>
					</li>
				';
			}
			$out .= '</ul>';
			return $out;
		}
		
		public function is_elementor(){
			// Check if Elementor installed and activated
			if( !did_action('elementor/loaded') ){
				return false;
			}else{
				// Check for required Elementor version
				if( !version_compare(ELEMENTOR_VERSION, MGS_MINIMUM_ELEMENTOR_VERSION, '>=') ){
					add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
					return false;
				}else{
					return true;
				}
			}
		}
		
		public function admin_notice_missing_elementor_plugin(){
			if( isset($_GET['activate']) ) unset($_GET['activate']);
			
			$message = sprintf(
				/* translators: 1: Plugin name 2: Elementor */
				esc_html__('"%1$s" requiere "%2$s" este instalado y activado.', 'mgs-theme-upgrade'),
				'<strong>'.esc_html__('MGS Theme Upgrade', 'mgs-theme-upgrade').'</strong>',
				'<strong>'.esc_html__('Elementor', 'mgs-theme-upgrade').'</strong>'
			);
			printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
		}
		
		public function admin_notice_minimum_elementor_version(){
			if( isset($_GET['activate']) ) unset($_GET['activate']);
			$message = sprintf(
				/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
				esc_html__('"%1$s" requiere "%2$s" versión %3$s o mayor.', 'mgs-theme-upgrade'),
				'<strong>'.esc_html__('MGS Theme Upgrade', 'mgs-theme-upgrade').'</strong>',
				'<strong>'.esc_html__('Elementor', 'mgs-theme-upgrade').'</strong>',
			 	MGS_MINIMUM_ELEMENTOR_VERSION
			);
			printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
		}
	}
}