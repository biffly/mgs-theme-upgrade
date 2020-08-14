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
        
        public function enqueue_scripts(){}
        
        
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