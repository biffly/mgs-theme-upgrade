<?php
if( !class_exists('MGS_Theme_Upgrade_Admin') ){
	class MGS_Theme_Upgrade_Admin{
		private static $instance;
        private $settings;
        private $slug;
        
        public static function get_instance(){
			if( null === self::$instance ){
				self::$instance = new MGS_Theme_Upgrade_Admin();
			}
			return self::$instance;
		}
		
		public function __construct(){
            $this->slug = 'mgs_theme_upgrade_page';
            
            add_action('admin_menu', [$this, 'admin_menu']);
            add_action('admin_init', [$this, 'register_settings']);
        }
        
        public function get_settings(){
            $this->settings = [
                'imgs'              => [
                    'label'     => __('Imagenes', 'mgs-theme-upgrade'),
                    'fields'    => [
                        'sizes'         => [
                            'wpml'          => false,
                            'type'          => 'checkbox',
                            'label'         => __('Desactivar', 'mgs-theme-upgrade'),
                            'desc'          => __('Seleccione cuales tamaños de imagenes desea desactivar el procesamiento.', 'mgs-theme-upgrade'),
                            'def'           => '',
                            'values'        => [
                                '1'     => 'Uno',
                                '2'     => 'Dos',
                                '3'     => 'Tres',
                            ]
                        ]
                    ]
                ]
            ];
        }
        
        public function page(){
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">MGS Theme Upgrade</h1>
                <form method="post" action="options.php">
                    <?php settings_fields('mgs_theme_upgrade_options');?>
                
                    <div id="tabs">
                        <?PHP $this->build_tabs()?>
                    </div>
                </form>
            </div>
            <?php
        }
        
        private function build_tabs(){
            $out = '<ul>';
            foreach( $this->settings as $id_seccion=>$attrs ){
                $out .= '<li><a href="#'.$id_seccion.'">'.$attrs['label'].'</a></li>';
            }
            $out .= '</ul>';
            echo $out;
        }
        
        public function admin_menu(){
            add_options_page('MGS Theme Upgrade', 'MGS Theme Upgrade', 'manage_options', $this->slug, [$this, 'page']);
        }
        
        public function register_settings(){
            foreach( $this->settings as $seccion ){
                foreach( $seccion['fields'] as $id=>$attrs ){
                    if( MGS_Theme_Upgrade::isWPML() && $attrs['wpml'] ){
                        $languages = apply_filters('wpml_active_languages', NULL);
                        foreach( $languages as $lang_id=>$v ){
                            $id .= '_'.$lang_id;
                            add_option($id, $attrs['def']);
                            register_setting('mgs_theme_upgrade_options', $id, 'mgs_theme_upgrade_options_callback');
                        }
                    }else{
                        add_option($id, $attrs['def']);
                        register_setting('mgs_theme_upgrade_options', $id, 'mgs_theme_upgrade_options_callback');
                    }
                }
            }
        }
        
    }
    new MGS_Theme_Upgrade_Admin();
}