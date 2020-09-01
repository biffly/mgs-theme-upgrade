<?php
if( !class_exists('MGS_Theme_Upgrade_Admin') ){
	class MGS_Theme_Upgrade_Admin{
		private static $instance;
        private $settings;
        private $slug;
        public static $mgs_tu_images_disabled;
        public static $mgs_tu_images_svg_disabled;
        
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
            
            
            
            $this->settings = [
                'funcs'             => [
                    'label'     => __('Funcionalidades', 'mgs-theme-upgrade'),
                    'fields'    => [
                        'mgs-tu-readmore'               => [
                            'wpml'              => false,
                            'type'              => 'onoff',
                            'label'             => __('Leer más', 'mgs-theme-upgrade'),
                            'desc'              => __('Funcionalidad que permite crear un texto con la opcion de <i>Leer más</i> al estilo Facebook.'),
                            'def'               => '',                            
                        ],
                        'mgs-tu-readmore-text'          => [
                            'wpml'              => true,
                            'type'              => 'text',
                            'label'             => __('Texto', 'mgs-theme-upgrade'),
                            'desc'              => __('Etiqueta de texto que se agregara despues del primer parrafo.'),
                            'def'               => __('Leer más', 'mgs-theme-upgrade'),
                            'dependent'         => 'mgs-tu-readmore'
                        ],
                        'mgs-tu-readmore-text-speed'          => [
                            'wpml'              => false,
                            'type'              => 'text',
                            'label'             => __('Velocidad', 'mgs-theme-upgrade'),
                            'desc'              => __('Velocidad con la que aparece el texto.'),
                            'def'               => 500,
                            'dependent'         => 'mgs-tu-readmore'
                        ]
                    ]
                ],
                
                'imgs'              => [
                    'label'     => __('Imagenes', 'mgs-theme-upgrade'),
                    'fields'    => [
                        'mgs-tu-images-disabled'        => [
                            'wpml'              => false,
                            'type'              => 'onoff',
                            'label'             => __('Desactivar tamaños', 'mgs-theme-upgrade'),
                            'desc'              => __('Desactiva la creación de algunas imagenes.'),
                            'def'               => '',
                            //'disabled'          => MGS_TU_IMAGES_DESABLED,
                            //'disabled_why'      => __('Se detecto otro plugin que ya realiza esta tarea para desactivar la creación de imagenes hagalo desde sus opciones.', 'mgs-theme-upgrade')
                            
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
                        ],
                        'mgs-tu-images-svg'        => [
                            'wpml'              => false,
                            'type'              => 'onoff',
                            'label'             => 'SVG',
                            'desc'              => __('Permite la carga de imagenes en formato SVG.'),
                            'def'               => '',
                            'disabled'          => MGS_TU_IMAGES_SGV_DISABLED,
                            'disabled_why'      => __('Se detecto otro pluging que permirmite la carga de imagenes SVG, desactivelo para habilitar esta opción.', 'mgs-theme-upgrade')
                            
                        ],
                    ]
                ],
                
                'css'               => [
                    'label'     => 'CSS',
                    'fields'    => [
                        'mgs-tu-css'                    => [
                            'wpml'              => false,
                            'type'              => 'onoff',
                            'label'             => __('CSS personalizada', 'mgs-theme-upgrade'),
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
                            'type'              => 'onoff',
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
                            'labeled'           => '',
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
            ];
            return $this->settings;
        }
        
        public function page(){
            ?>
            <div class="wrap mgs-tu-warp">
                <div class="ui fluid container">
                    <div class="mgs-tu-header">
                        <h1>MGS Theme Upgrade</h1>
                        <div class="sub-head">
                            <a href="https://github.com/biffly/mgs-theme-upgrade" target="_blank" class="ui grey button small"><i class="github icon"></i> GitHub</a>
                        </div>
                    </div>
                    
                    <div class="mgs-tu-state">
                        <div class="mgs-tu-logo">
                            <div class="logo"></div>
                            <div class="ver"><?php echo __('Versión:', 'mgs-theme-upgrade').' '.MGS_THEME_UPG_VERSION?></div>
                        </div>
                        <div></div>
                        <div class="git-info">
                            <?php
                            $git = $this->get_git();
                            echo '<p class="version">'.__('Ultima versión', 'mgs-theme-upgrade').' '.$git->tag_name.'</p>';
                            echo '<p class="fecha">'.__('Fecha', 'mgs-theme-upgrade').' '.date('d/m/Y', strtotime($git->published_at)).'</p>';
                            if( version_compare(MGS_THEME_UPG_VERSION, $git->tag_name)<0 ){
                                echo '<p class="update"><a href="wp-admin/plugins.php" class="ui grey button small"><i class="icon sync"></i> '.__('Actualizar', 'mgs-theme-upgrade').'</a></p>';
                            }
                            echo '<p class="last-check">'.__('Ultima verificación', 'mgs-tu-upgrade').': '.date('d/m/Y H:i:s e', get_option('mgs-tu-last-time-git')).'</p>';
                            ?>
                        </div>
                    </div>

                    <form method="post" action="options.php">
                        <?php settings_fields('mgs_theme_upgrade_options');?>
                        
                        <div id="tabs" class="mgs-tu-tabs">
                            <?PHP
                            $this->build_tabs();
                            $this->build_contents();
                            ?>
                        </div>
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
            </div>
            <?php
        }
        
        private function build_tabs(){
            $out = '<ul>';
            foreach( $this->get_settings() as $id_seccion=>$attrs ){
                $out .= '<li><a href="#'.$id_seccion.'">'.$attrs['label'].'</a></li>';
            }
            $out .= '</ul>';
            echo $out;
        }
        
        private function build_contents(){
            foreach( $this->get_settings() as $id_seccion=>$attrs ){
                $out .= '
                    <div id="'.$id_seccion.'">
                        <h2 class="title-seccion">'.$attrs['label'].'</h2>
                        <table class="form-table">
                            <tbody>
                                '.$this->build_fields($attrs['fields']).'
                                '.$this->save_button().'
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
                    $out .= '
                        <tr '.$this->dependent($ops).'>
                            '.$this->build_th($ops).'
                            '.$this->open_td($ops).'
                    ';
                    
                    switch( $ops['type'] ){
                        case 'checkboxes':
                            $out .= $this->_checkboxes($id, $ops);
                            break;
                        case 'checkbox':
                            $out .= $this->_checkbox($id, $ops);
                            break;
                        case 'onoff':
                            $out .= $this->_onoff($id, $ops);
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
                    $out .= '
                            '.$this->close_td().'
                        </tr>
                    ';
                }
            }
            return $out;
        }
        
        private function _checkboxes($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            
            $out = '
                <fieldset class="'.$ops['class'].'">
            ';
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
                <label><input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.checked($val, true, false).' '.$disabled.'> '.$ops['desc'].$disabled_why.'</label>
            ';
            return $out;
        }
        
        private function _onoff($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            if( $ops['disabled'] ){
                $disabled = 'disabled';
                $disabled_why = $ops['disabled_why'];
            }else{
                $disabled = '';
                $disabled_why = '';
            }
            
            $out = '
                <div class="ui toggle checkbox">
                    <input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.checked($val, true, false).' '.$disabled.'>
                    <label> '.__('Activar / Desactivar', 'mgs-theme-upgrade').$disabled_why.'</label>
                </div>
            ';
            if( $ops['disabled'] ){
                $out .= '
                <div class="disabled-aviso">
                    <div class="ui icon message negative">
                        <i class="ban icon"></i>
                        <div class="content">
                            <div class="header">'.__('Opción no disponible', 'mgs-theme-upgrade').'</div>
                            <p>'.$disabled_why.'</p>
                        </div>
                    </div>
                </div>
                ';
            }
            return $out;
        }
        
        private function _text($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            $labeled = ( $ops['labeled']!='' ) ? 'labeled' : '';
            
            $out = '
                <div class="ui fluid '.$labeled.' input">
            ';
            $out .= ( $ops['labeled']!='' ) ? '<div class="ui label"></div>' : '';
            $out .= '
                    <input type="text" id="'.$name.'" name="'.$name.'" value="'.$val.'">
                </div>
            ';
            return $out;
        }
        
        private function _test($id, $ops){
            $out = '
                '.$ops['desc'].'
                <div class="ui icon tiny message '.call_user_func([$this, $ops['func']], 'class').'">
                    <i class="'.call_user_func([$this, $ops['func']], 'ico').' icon"></i>
                    <div class="content">
                        <p>'.call_user_func([$this, $ops['func']], 'text').'</p>
                    </div>
                </div>
            ';
            return $out;
        }
        
        private function save_button(){
            $out = '
                <tr class="save-seccion">
                    <th scope="row"></th>
                    <td colspan="2">
                        <button class="ui primary blue button right floated"><i class="icon save outline"></i> '.__('Guardar cambios', 'mgs-theme-upgrade').'</button>
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
                wp_enqueue_script('semantic-ui-js', MGS_THEME_UPG_PLUGIN_DIR_URL.'inc/assets/js/semantic.min.js');
                wp_enqueue_style('semantic-ui-css', MGS_THEME_UPG_PLUGIN_DIR_URL.'inc/assets/css/semantic.min.css');
                wp_enqueue_style('mgs-tu-css', MGS_THEME_UPG_PLUGIN_DIR_URL.'inc/assets/css/admin.css');
                
                
                
                //wp_enqueue_script('jquery-ui-tabs');
                //wp_enqueue_style('mgs-jquery-ui', MGS_THEME_UPG_PLUGIN_DIR_URL.'inc/assets/css/admin.css');
                
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
        
        public function get_git(){
            $time = time();
            $_last = get_option('mgs-tu-last-time-git');
            if( $_last ){
                if( ($time - $_last)>3600 ){
                    $git = wp_remote_get("https://api.github.com/repos/biffly/mgs-theme-upgrade/releases/latest");
                    $git = json_decode($git['body']);
                    update_option('mgs-tu-last-time-git', $time);
                    update_option('mgs-tu-last-git', $git);
                }else{
                    $git = get_option('mgs-tu-last-git');
                }
            }else{
                $git = wp_remote_get("https://api.github.com/repos/biffly/mgs-theme-upgrade/releases/latest");
                $git = json_decode($git['body']);
                update_option('mgs-tu-last-time-git', $time);
                update_option('mgs-tu-last-git', $git);
            }
            return $git;
        }
        
        private function dependent($ops, $class=''){
            return ( $ops['dependent']!='' ) ? 'data-dependent="'.$ops['dependent'].'" class="mgs-tu-row-dependent '.$class.'"' : '';
        }
        
        private function is_dependent($ops, $class=''){
            return ( $ops['dependent']!='' ) ? true : false;
        }
        
        private function build_th($ops){
            if( $this->is_dependent($ops) ){
                return '<th scope="row"></th>';
            }else{
                return '<th scope="row">'.$this->label($ops).'</th>';
                
            }
        }
        
        private function open_td($ops){
            if( $this->is_dependent($ops) ){
                return '
                    <td class="header-dependant">'.$this->label($ops).'</td>
                    <td>
                ';
            }else{
                return '<td colspan="2">';
            }
        }
        
        private function close_td(){
            return '</td>';
        }
        
        private function label($ops){
            $name = $this->get_field_name($id, $ops);
            return '
                <label for="'.$name.'">'.$ops['label'].$this->Label_WPML($ops).'</label>
                <p class="desc">'.$ops['desc'].'</p>
            ';
        }
        
    }
    new MGS_Theme_Upgrade_Admin();
}