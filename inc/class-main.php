<?php
if( !class_exists('MGS_Theme_Upgrade') ){
	class MGS_Theme_Upgrade{
		private static $instance;
        public static $compatibility;

		public static function get_instance(){
			if( null === self::$instance ){
				self::$instance = new MGS_Theme_Upgrade();
			}
			return self::$instance;
		}
		
		public function __construct(){
            $this->on_load();
            
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts_admin']);
			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
            
            $this->add_actions();
            $this->add_filters();
        }
        
        public function add_actions(){
            add_action('init', function(){
                //remueve imagenes sizes
                if( get_option('mgs-tu-images-disabled') ){
                    foreach( get_option('mgs-tu-images-disabled-sizes') as $us ){
                        remove_image_size($us);
                    }
                }
            }, 999);
            
            //envio de correos, modifico body
            add_action('phpmailer_init', function($phpmailer){
                $phpmailer->AltBody = $phpmailer->Body;
                $phpmailer->Body = '<h1>HEADER</h1>'.$phpmailer->Body.'<br><br><hr><h1>FOOTER</h1>';
            });
            
            //cargo css personalizado
            add_action('wp_enqueue_scripts', function(){
                if( get_option('mgs-tu-css') && !is_admin() && file_exists(get_stylesheet_directory().'/mgs-tu/main.css') ){
                    wp_enqueue_style('mgs-tu-style', get_stylesheet_directory_uri().'/mgs-tu/main.css');
                }
            });
        }
        
        public function add_filters(){
            //agrego al body el class del plg
            add_filter('body_class', function($classes){
                if( !is_admin() ){
                    $classes[] = 'mgs-tu-upgrade';
                }
                return $classes;
            });
            
            //opciones de correo
            if( get_option('mgs-tu-correos') ){
                if( get_option('mgs-tu-correos-sender-name') ) add_filter('wp_mail_from_name', function($original_email_address){
                    return get_option('mgs-tu-correos-sender-name');
                });
                if( get_option('mgs-tu-correos-sender-dir') ) add_filter('wp_mail_from', function($original_email_from){
                    return get_option('mgs-tu-correos-sender-dir');
                });
                
                add_filter('wp_mail_content_type', function(){
                    return 'text/html';
                });
            }
        }
        
        public function on_load(){
            self::$compatibility = [
                'elementor'     => ( did_action('elementor/loaded') )   ? true : false ,
                'avada'         => ( class_exists('FusionBuilder') )    ? true : false ,
                'wpml'          => ( function_exists('icl_object_id') ) ? true : false ,
            ];
        }
        
        public static function activation(){}
        
        public function enqueue_scripts_admin(){}
        
        public function enqueue_scripts(){
            
        }
        
        public static function isAvada(){
            if( self::$compatibility['avada'] ){
                return true;
            }else{
                return false;
            }
        }
        
        public static function isElementor(){
            if( self::$compatibility['elementor'] ){
                return true;
            }else{
                return false;
            }
        }
        
        public static function isWPML(){
            if( self::$compatibility['wpml'] ){
                return true;
            }else{
                return false;
            }
        }
    }
    new MGS_Theme_Upgrade();
}     