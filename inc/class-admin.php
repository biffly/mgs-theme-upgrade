<?php
if( !class_exists('MGS_Theme_Upgrade_Admin') ){
	class MGS_Theme_Upgrade_Admin{
		private static $instance;
        private $settings;
        
        public static function get_instance(){
			if( null === self::$instance ){
				self::$instance = new MGS_Theme_Upgrade_Admin();
			}
			return self::$instance;
		}
		
		public function __construct(){
        }
        
        public function get_settings(){
            $this->settings = [
                'imgs'              => [
                    'label'     => __('Imagenes', 'mgs-theme-upgrade'),
                    'fields'    => [
                        'sizes'         => [
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
    }
    new MGS_Theme_Upgrade_Admin();
}