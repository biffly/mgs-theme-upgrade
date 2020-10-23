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
			$this->load();
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
	}
}
