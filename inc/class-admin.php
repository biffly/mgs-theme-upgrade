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
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('admin_init', function(){
                //delete_option('mgs-tu-default-images-sizes');
                if( !get_option('mgs-tu-default-images-sizes') ) update_option('mgs-tu-default-images-sizes', $this->get_all_image_sizes());
                
                $this->register_settings();
                
            }, 1);
            
            
        }
        
        public function get_settings(){
            $imgs_sizes_arr = [];
            foreach( get_option('mgs-tu-default-images-sizes') as $k=>$v ){
                $imgs_sizes_arr[$k][0] = $k.' ('.$v['width'].'x'.$v['height'].')';
                $imgs_sizes_arr[$k][1] = $v['disabled'];
            }
            
            $mgs_tu_images_disabled = false;
            $mgs_tu_images_disabled_why = __('Se detecto el pluging <code>Smush</code> para desactivar la creación de imagenes hagalo desde sus opciones.', 'mgs-theme-upgrade');
            if( class_exists('WP_Smush') ){
                $mgs_tu_images_disabled = true;
            }
            
            $this->settings = [
                'css'               => [
                    'label'     => 'CSS',
                    'fields'    => [
                        'mgs-tu-css'                    => [
                            'wpml'              => false,
                            'type'              => 'checkbox',
                            'label'             => __('Activar opciones de CSS personalizada', 'mgs-theme-upgrade'),
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
                    'fields'    => [
                        'mgs-tu-correos'                => [
                            'wpml'              => false,
                            'type'              => 'checkbox',
                            'label'             => __('Activar opciones de correo', 'mgs-theme-upgrade'),
                            'desc'              => __('Agrega opciones especiales a los correos por defecto de wordpress.'),
                            'def'               => '',
                        ],
                        'mgs-tu-correos-sender-name'    => [
                            'wpml'              => true,
                            'type'              => 'text',
                            'label'             => __('Nombre', 'mgs-theme-upgrade'),
                            'desc'              => __('Nombre asignado a la dirección desde donde se envian los correos', 'mgs-theme-upgrade'),
                            'def'               => get_bloginfo('name'),
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
                'imgs'              => [
                    'label'     => __('Imagenes', 'mgs-theme-upgrade'),
                    'fields'    => [
                        'mgs-tu-images-disabled'        => [
                            'wpml'              => false,
                            'type'              => 'checkbox',
                            'label'             => __('Desactivar tamaños', 'mgs-theme-upgrade'),
                            'desc'              => __('Desactiva la creación de algunas imagenes.'),
                            'def'               => '',
                            'disabled'          => $mgs_tu_images_disabled,
                            'disabled_why'      => $mgs_tu_images_disabled_why
                            
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
                        ]
                    ]
                ],
            ];
            return $this->settings;
        }
        
        public function page(){
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">MGS Theme Upgrade</h1>
                <form method="post" action="options.php">
                    <?php settings_fields('mgs_theme_upgrade_options');?>
                
                    <div id="tabs" class="mgs-tu-tabs">
                        <?PHP
                        $this->build_tabs();
                        $this->build_contents();
                        ?>
                    </div>
                    <?php submit_button();?>
                    <script>
                        jQuery(document).ready(function(){
                            jQuery('#tabs').tabs();
                            dependent_check();
                            
                            
                            jQuery('.mgs-tu-dependent-tigger').on('change', function(){
                                dependent_check();
                            });
                            
                            function dependent_check(){
                                jQuery('.mgs-tu-row-dependent').each(function(){
                                    if( jQuery(this).data('dependent')!='' ){
                                        var dependent = jQuery(this).data('dependent');
                                        jQuery('#'+dependent).addClass('mgs-tu-dependent-tigger');
                                        if( !jQuery('#'+dependent).is(':checked') ){
                                            jQuery(this).fadeOut('fast');
                                        }else{
                                            jQuery(this).fadeIn();
                                        }
                                    }
                                });
                            }
                        });
                    </script>
                </form>
            </div>
            <?php
        }
        
        private function build_tabs(){
            $out = '<ul>';
            foreach( $this->get_settings() as $id_seccion=>$attrs ){
                $out .= '<li><a href="#'.$id_seccion.'">'.$attrs['label'].'</a></li>';
            }
            $out .= '</ul><div class="clear"></div>';
            echo $out;
        }
        
        private function build_contents(){
            foreach( $this->get_settings() as $id_seccion=>$attrs ){
                $out .= '
                    <div id="'.$id_seccion.'">
                        <h2>'.$attrs['label'].'</h2>
                        <table class="form-table">
                            <tbody>
                                '.$this->build_fields($attrs['fields']).'
                            </tbody>
                        </table>
                    </div>
                ';
            }
            echo $out;
        }
        
        private function build_fields($attrs){
            $out = '';
            if( $attrs ){
                foreach( $attrs as $id=>$ops ){
                    switch( $ops['type'] ){
                        case 'checkboxes':
                            $out .= $this->_checkboxes($id, $ops);
                            break;
                        case 'checkbox':
                            $out .= $this->_checkbox($id, $ops);
                            break;
                        case 'text':
                            $out .= $this->_text($id, $ops);
                            break;
                        case 'test':
                            $out .= $this->_test($id, $ops);
                            break;
                        default:
                            $out .= '';
                            break;
                    }
                }
            }
            return $out;
        }
        
        private function _checkboxes($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            
            $out = '
                <tr data-dependent="'.$ops['dependent'].'" class="mgs-tu-row-dependent">
                    <th scope="row">
                        <label>'.$ops['label'].'</label>
                    </th>
                    <td>
                        <fieldset class="'.$ops['class'].'">
            ';
            if( $ops['desc'] ) $out .= '<p class="description">'.$ops['desc'].'</p>';
            foreach( $ops['values'] as $valor=>$etiqueta ){
                $c = '';
                if( is_array($val) ){
                    if( in_array($valor, $val) ) $c = 'checked="checked"';
                }else{
                    if( $val==$valor ) $c = 'checked="checked"';
                }
                $disabled = ( $etiqueta[1]==1 ) ? 'disabled="disabled"' : '';
                $out .= '
                            <label><input type="checkbox" name="'.$name.'[]" value="'.$valor.'" '.$c.' '.$disabled.'> '.$etiqueta[0].'</label><br>';
            }
            $out .= '
                        </fieldset>
            ';
            $out .= '
                    </td>
                </tr>
            ';
            return $out;
        }
        
        private function _checkbox($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            if( $ops['disabled'] ){
                $disabled = 'disabled';
                $disabled_why = '<p class="alert">'.$ops['disabled_why'].'</p>';
            }else{
                $disabled = '';
                $disabled_why = '';
            }
            
            $out = '
                <tr data-dependent="'.$ops['dependent'].'" class="mgs-tu-row-dependent">
                    <th scope="row">
                        <label for="'.$name.'">'.$ops['label'].'</label>
                    </th>
                    <td>
                        <label><input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.checked($val, true, false).' '.$disabled.'> '.$ops['desc'].$disabled_why.'</label>
                    </td>
                </tr>
            ';
            return $out;
        }
        
        private function _text($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            
            $out = '
                <tr data-dependent="'.$ops['dependent'].'" class="mgs-tu-row-dependent">
                    <th scope="row">
                        <label for="'.$name.'">'.$ops['label'].$this->Label_WPML($ops).'</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" id="'.$name.'" name="'.$name.'" value="'.$val.'" />
            ';
            if( $ops['desc'] ) $out .= '<p class="description">'.$ops['desc'].'</p>';
            $out .= '
                    </td>
                </tr>
            ';
            return $out;
        }
        
        private function _test($id, $ops){
            $out = '
                <tr data-dependent="'.$ops['dependent'].'" class="mgs-tu-row-dependent mgs-tu-row-test '.call_user_func([$this, $ops['func']], 'class').'">
                    <th scope="row">
                        <label for="'.$name.'">'.$ops['label'].'</label>
                    </th>
                    <td>
                        '.$ops['desc'].'<p class="mgs-tu-test-aviso">'.call_user_func([$this, $ops['func']], 'ico').'</p>
                    </td>
                </tr>
            ';
            return $out;
        }
        
        public function admin_menu(){
            add_options_page('MGS Theme Upgrade', 'MGS Theme Upgrade', 'manage_options', $this->slug, [$this, 'page']);
        }
        
        private function get_field_name($id, $ops){
            if( MGS_Theme_Upgrade::isWPML() && $ops['wpml'] ){
                $name = $id.'_'.ICL_LANGUAGE_CODE;
            }else{
                $name = $id;
            }
            return $name;
        }
        
        private function get_field_value($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = get_option($name);
            if( $val=='' ) $val = $ops['def'];
            return $val;
        }
        
        private function Label_WPML($ops){
            if( MGS_Theme_Upgrade::isWPML() && $ops['wpml'] ){
                return ' <span class="dashicons dashicons-translation" aria-hidden="true"></span>';
            }
        }
        
        public function register_settings(){
            foreach( $this->get_settings() as $seccion ){
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
        
        public function enqueue_scripts($hook){
            if( $hook=='settings_page_mgs_theme_upgrade_page' ){
                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-tabs');
                
                wp_enqueue_style('mgs-jquery-ui', MGS_THEME_UPG_PLUGIN_DIR_URL.'inc/assets/css/admin.css');
                //wp_enqueue_style('jquery-ui-tabs', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');

                /*
                $cm_settings['codeEditor'] = wp_enqueue_code_editor(
                    [
                        'type'          => 'text/html',
                        'codemirror'    => [
                            'autoRefresh'   => true
                        ]
                    ]
                );
                wp_localize_script('jquery', 'cm_settings', $cm_settings);

                wp_enqueue_script('wp-theme-plugin-editor');
                wp_enqueue_style('wp-codemirror');
                */
            }
        }
        
        private function get_all_image_sizes(){
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
            
            /*unset($image_sizes['thumb']);
            unset($image_sizes['thumbnail']);
            unset($image_sizes['medium']);
            unset($image_sizes['medium_large']);
            unset($image_sizes['large']);*/
            $image_sizes['thumb']['disabled'] = 1;
            $image_sizes['thumbnail']['disabled'] = 1;
            $image_sizes['medium']['disabled'] = 1;
            $image_sizes['medium_large']['disabled'] = 1;
            $image_sizes['large']['disabled'] = 1;
            return $image_sizes;
        }
        
        public function test_folder_css($return='flag'){
            $flag = false;
            if( is_dir(get_stylesheet_directory().'/mgs-tu') ){
                $flag = true;
            }
            
            if( $return=='ico' ){
                if( $flag ){
                    return '<svg version="1.1" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path fill="#00c800" d="M116,32H68L52,16H12C5.373,16,0,21.373,0,28v72c0,6.627,5.373,12,12,12h104c6.627,0,12-5.373,12-12V44 C128,37.373,122.627,32,116,32z"/></svg> '.__('Carpeta encontrada.', 'mgs-tu-upgrade');
                }else{
                    return '<svg version="1.1" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path fill="#960000" d="M116,32H68L52,16H12C5.373,16,0,21.373,0,28v72c0,6.627,5.373,12,12,12h104c6.627,0,12-5.373,12-12V44 C128,37.373,122.627,32,116,32z"/></svg> '.__('Carpeta no encontrada.', 'mgs-tu-upgrade');
                }
            }elseif( $return=='class' ){
                if( $flag ){
                    return 'valid';
                }else{
                    return 'not-valid';
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
                    return '<svg version="1.1" id="Capa_1" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path fill="#00c800" d="M112,30.485V32H80V0h1.515c1.591,0,3.118,0.632,4.243,1.757l24.484,24.485 C111.367,27.368,112,28.894,112,30.485z M78,40c-3.3,0-6-2.7-6-6V0H22c-3.314,0-6,2.686-6,6v116c0,3.314,2.686,6,6,6h84 c3.314,0,6-2.686,6-6V40H78z M46.801,100.127c-0.51,0.543-1.364,0.57-1.908,0.061l0,0L28.677,84.984	c-0.544-0.51-0.571-1.363-0.062-1.908c0.02-0.021,0.041-0.041,0.062-0.061l16.216-15.203c0.544-0.51,1.398-0.482,1.908,0.061l0,0	l4.895,5.222c0.51,0.544,0.482,1.398-0.062,1.908c-0.01,0.01-0.021,0.019-0.032,0.028L41.413,84l10.19,8.969	c0.56,0.492,0.614,1.346,0.122,1.905c-0.009,0.011-0.019,0.021-0.028,0.031L46.801,100.127z M59.625,112.746l-6.863-1.992	c-0.716-0.209-1.128-0.957-0.92-1.674l15.359-52.906c0.209-0.716,0.957-1.128,1.674-.92l6.863,1.992	c0.715,0.208,1.127,0.957,0.92,1.673v0l-15.36,52.907C61.09,112.541,60.342,112.953,59.625,112.746	C59.626,112.746,59.625,112.746,59.625,112.746z M99.823,84.984l-16.216,15.203c-.545,0.51-1.398,0.482-1.908-0.061l0,0	l-4.896-5.223c-0.511-0.543-0.482-1.397,0.062-1.907c0.01-0.01,0.021-0.02,0.031-0.028L87.088,84l-10.191-8.969	c-0.56-0.492-0.613-1.346-0.121-1.905c0.01-0.011,0.019-0.021,0.028-0.031l4.896-5.222c0.51-0.543,1.363-0.571,1.908-0.061l0,0	l16.217,15.203c0.543,0.51,0.57,1.363,0.061,1.908C99.865,84.944,99.844,84.965,99.823,84.984L99.823,84.984z"/></svg> '.__('Archivo encontrado.', 'mgs-tu-upgrade');
                }else{
                    return '<svg version="1.1" id="Capa_1" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path fill="#960000" d="M112,30.485V32H80V0h1.515c1.591,0,3.118,0.632,4.243,1.757l24.484,24.485 C111.367,27.368,112,28.894,112,30.485z M78,40c-3.3,0-6-2.7-6-6V0H22c-3.314,0-6,2.686-6,6v116c0,3.314,2.686,6,6,6h84 c3.314,0,6-2.686,6-6V40H78z M46.801,100.127c-0.51,0.543-1.364,0.57-1.908,0.061l0,0L28.677,84.984	c-0.544-0.51-0.571-1.363-0.062-1.908c0.02-0.021,0.041-0.041,0.062-0.061l16.216-15.203c0.544-0.51,1.398-0.482,1.908,0.061l0,0	l4.895,5.222c0.51,0.544,0.482,1.398-0.062,1.908c-0.01,0.01-0.021,0.019-0.032,0.028L41.413,84l10.19,8.969	c0.56,0.492,0.614,1.346,0.122,1.905c-0.009,0.011-0.019,0.021-0.028,0.031L46.801,100.127z M59.625,112.746l-6.863-1.992	c-0.716-0.209-1.128-0.957-0.92-1.674l15.359-52.906c0.209-0.716,0.957-1.128,1.674-.92l6.863,1.992	c0.715,0.208,1.127,0.957,0.92,1.673v0l-15.36,52.907C61.09,112.541,60.342,112.953,59.625,112.746	C59.626,112.746,59.625,112.746,59.625,112.746z M99.823,84.984l-16.216,15.203c-.545,0.51-1.398,0.482-1.908-0.061l0,0	l-4.896-5.223c-0.511-0.543-0.482-1.397,0.062-1.907c0.01-0.01,0.021-0.02,0.031-0.028L87.088,84l-10.191-8.969	c-0.56-0.492-0.613-1.346-0.121-1.905c0.01-0.011,0.019-0.021,0.028-0.031l4.896-5.222c0.51-0.543,1.363-0.571,1.908-0.061l0,0	l16.217,15.203c0.543,0.51,0.57,1.363,0.061,1.908C99.865,84.944,99.844,84.965,99.823,84.984L99.823,84.984z"/></svg> '.__('Archivo no encontrado.', 'mgs-tu-upgrade');
                }
            }elseif( $return=='class' ){
                if( $flag ){
                    return 'valid';
                }else{
                    return 'not-valid';
                }
            }else{
                return $flag;
            }
            
        }
    }
    new MGS_Theme_Upgrade_Admin();
}