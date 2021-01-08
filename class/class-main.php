<?php
if( !class_exists('MGS_Theme_Upgrade') ){
	class MGS_Theme_Upgrade{
		private static $instance;
		public static $compatibility;
		public $settings;
		public $plg_name;

		public static function get_instance(){
			if( null === self::$instance ){
				self::$instance = new MGS_Theme_Upgrade();
			}
			return self::$instance;
		}
		
		public function __construct($config){
			$this->plg_name = MGS_THEME_UPG_NAME;
			$this->build_options($config);
			$this->add_actions();
			$this->add_filters();
			add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
		}
		
		public function add_actions(){
			//remueve imagenes sizes
			if( $this->get_field_value('mgs-tu-images-disabled') && $this->get_field_value('mgs-tu-images-disabled-sizes') ){
				add_action('init', function(){
					foreach( $this->get_field_value('mgs-tu-images-disabled-sizes') as $us ){
                        remove_image_size($us);
                    }
				}, 99999);
			}
			
			//cargo css personalizado
			if( $this->get_field_value('mgs-tu-css') && !is_admin() && file_exists(get_stylesheet_directory().'/mgs-tu/main.css') ){
				add_action('wp_enqueue_scripts', function(){
					wp_enqueue_style('mgs-tu-style', get_stylesheet_directory_uri().'/mgs-tu/main.css');
				}, 99999);
			}
			
			//READ MORE
			if( $this->get_field_value('mgs-tu-readmore') && !is_admin() ){
				add_action('wp_enqueue_scripts', function(){
					wp_enqueue_script('jquery');
                    wp_register_script('mgs-tu-js', MGS_THEME_UPG_PLUGIN_DIR_URL.'/assets/js/mgs-tu.js', ['jquery']);
					$translation_array = [
                        'mgs_tu_readmore_text'  => $this->get_field_value('mgs-tu-readmore-text'),
                        'mgs_tu_readmore_text_speed'  => $this->get_field_value('mgs-tu-readmore-text-speed'),
                    ];
                    wp_localize_script('mgs-tu-js', 'mgs_tu_js_vars', $translation_array);
                    wp_enqueue_script('mgs-tu-js');
					wp_enqueue_style('mgs-tu-style-readmore', MGS_THEME_UPG_PLUGIN_DIR_URL.'/assets/css/readmore.css');
				});
			}
			
		            
            //TODO agregar cabecera y footer a mail.
            /*add_action('phpmailer_init', function($phpmailer){
                $phpmailer->AltBody = $phpmailer->Body;
                $phpmailer->Body = '<h1>HEADER</h1>'.$phpmailer->Body.'<br><br><hr><h1>FOOTER</h1>';
            });*/
        }
		
		public function add_filters(){
            //ADD class to body
            add_filter('body_class', function($classes){
                if( !is_admin() ){
                    $classes[] = 'mgs-tu-upgrade';
                }
                return $classes;
            });
            
            //opciones de correo
            if( $this->get_field_value('mgs-tu-correos') ){
                if( $this->get_field_value('mgs-tu-correos-sender-name') ) add_filter('wp_mail_from_name', function($original_email_address){
                    return $this->get_field_value('mgs-tu-correos-sender-name');
                });
                if( $this->get_field_value('mgs-tu-correos-sender-dir') ) add_filter('wp_mail_from', function($original_email_from){
                    return $this->get_field_value('mgs-tu-correos-sender-dir');
                });
                
                add_filter('wp_mail_content_type', function(){
                    return 'text/html';
                });
            }
        }
				
		public function chech_compatibility($setting){
			foreach( self::$compatibility as $c=>$b ){
				if( in_array($c, $setting) && $b==true ){
					return true;
				}
			}
			return false;
		}
        
		public function get_field_name($id){
            if( self::$compatibility['wpml'] && $this->settings[$id]['wpml'] ){
				$name = $this->plg_name . '_' . $id . '_' . ICL_LANGUAGE_CODE;
            }else{
                $name = $this->plg_name . '_' . $id;
            }
            return $name;
        }
		
		public function get_field_value($id=NULL){
			if( $id===NULL ) return false;
            $name = $this->get_field_name($id);
            $val = get_option($name);
            if( $val=='' ) $val = $this->settings[$id]['def'];
            return $val;
        }
		
		private function build_options($config){
			$this->settings = [];
			foreach( $config as $sec=>$val ){
				foreach( $val['fields'] as $field_name=>$field){
					$this->settings[$field_name] = $field;
				}
			}
		}
		
		public function on_plugins_loaded(){
			self::$compatibility['gutenberg'] = true;
			self::$compatibility['elementor'] = $this->is_elementor();
			self::$compatibility['avada'] = ( class_exists('FusionBuilder') ) ? true : false;
            self::$compatibility['wpml'] = ( function_exists('icl_object_id') ) ? true : false;
			//var_dump(self::$compatibility);
		}
		
		public function is_elementor(){
			// Check if Elementor installed and activated
			if( !did_action('elementor/loaded') ){
				return false;
			}

			// Check for required Elementor version
			if( !version_compare(ELEMENTOR_VERSION, MGS_MINIMUM_ELEMENTOR_VERSION, '>=') ){
				return false;
			}
			
			return true;
		}
		
	}
}