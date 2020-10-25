<?php
//version 1.1
if( !class_exists('MGS_Admin_Class') ){
	class MGS_Admin_Class{
		public $slug;
		public $admin_option;
		public $plg_url;
		public $plg_git;
		public $plg_ver;
		public $plg_name;
		public $settings;
		public static $compatibility;
		public $raw_settings;
		
		public function __construct(){
			self::$compatibility = [
                'elementor'     => ( did_action('elementor/loaded') )   ? true : false ,
                'avada'         => ( class_exists('FusionBuilder') )    ? true : false ,
                'wpml'          => ( function_exists('icl_object_id') ) ? true : false ,
            ];
        }
		
		public function load(){
			add_action('admin_menu', [$this, 'admin_menu']);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
			add_action('admin_init', function(){    
                $this->register_settings();
            }, 1);
		}
		
		public function page(){
            ?>
            <div class="wrap mgs-admin-warp">
                <div class="ui fluid container">
					<svg class="svg-hidden">
  						<clipPath id="my-clip-path" clipPathUnits="objectBoundingBox"><path d="M0,-0.001 l0,0.733 c0,0,0.005,0.261,0.04,0.265 s0.591,0,0.591,0 s0.035,-0.049,0.035,-0.25 s0.031,-0.247,0.036,-0.247 s0.264,-0.002,0.264,-0.002 s0.033,-0.05,0.033,-0.265 s0,-0.234,0,-0.234 H0"></path></clipPath>
					</svg>
					<div class="mgs-admin-header">
						<div class="top">
							<div class="left">
								<div class="warper">
									<div class="mgs-admin-logo">
										<div class="logo"></div>
										<div>
											<h1><?php echo $this->admin_option['page_title']?></h1>
											<div class="ver"><?php echo __('Versión:', 'mgs-admin').' '.$this->plg_ver?></div>
										</div>
									</div>
									
								</div>
							</div>
                        	<div class="right">
								<a href="https://github.com/<?php echo $this->plg_git?>" target="_blank" class="ui button mini"><i class="github icon"></i> GitHub</a>
								<div class="git-info">
									<?php
									$git = $this->get_git();
									echo '<p class="version">'.__('Ultima versión', 'mgs-admin').' '.$git->tag_name.'</p>';
									echo '<p class="fecha">'.__('Fecha', 'mgs-admin').' '.date('d/m/Y', strtotime($git->published_at)).'</p>';
									if( version_compare($this->plg_ver, $git->tag_name)<0 ){
										echo '<p class="update"><a href="wp-admin/plugins.php" class="ui grey button small"><i class="icon sync"></i> '.__('Actualizar', 'mgs-admin').'</a></p>';
									}
									echo '<p class="last-check">'.__('Ultima verificación', 'mgs-admin').': '.date('d/m/Y H:i:s e', get_option('mgs-admin-last-time-git_'.$this->slug)).' <a href="?page='.$this->slug.'&acc=force_git_check" class="mini ui"><i class="icon sync"></i></a></p>';
									?>
								</div>
    	                    </div>
							<div class="clearfix"></div>
						</div>
					</div>
					<div class="clearfix"></div>
                    
					
					<form method="post" action="options.php" class="mgs-form-options">
                        <?php settings_fields($this->plg_name.'_options');?>
                        
                        <div id="tabs" class="mgs-admin-tabs">
                            <?PHP
                            $this->build_tabs();
                            $this->build_contents();
                            ?>
                        </div>
                        <script>
                            jQuery(document).ready(function(){
                                //tabs
								jQuery('#tabs').tabs();
								
								//more help
								jQuery('.mgs-more-help-tooltip').popup();
                                
                                dependent_check();
                                
                                jQuery('.mgs-admin-dependent-tigger').on('change', function(){
                                    dependent_check();
                                });

                                function dependent_check(){
                                    jQuery('.mgs-admin-row-dependent').each(function(){
                                        if( jQuery(this).data('dependent')!='' ){
                                            var dependent = jQuery(this).data('dependent');
                                            jQuery('#'+dependent).addClass('mgs-admin-dependent-tigger');
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
            foreach( $this->settings as $id_seccion=>$attrs ){
				$style = '';
				if( $attrs['icon']=='' ) $attrs['icon'] = 'fas fa-info';
				if( $attrs['color']!='' ) $style = ' style="color:'.$attrs['color'].';border-color:'.$attrs['color'].'"';
                $out .= '<li><a href="#'.$id_seccion.'" '.$style.'><i class="'.$attrs['icon'].'"></i> '.$attrs['label'].'</a></li>';
            }
            $out .= '<div class="clearfix"></div></ul>';
            echo $out;
        }
		
		private function build_contents(){
            foreach( $this->settings as $id_seccion=>$attrs ){
                $out .= '
                    <div id="'.$id_seccion.'">
                        <h2 class="title-seccion">'.$attrs['label'].'</h2>
						<div class="warp-table">
							<table class="form-table">
								<tbody>
									'.$this->build_fields($attrs['fields']).'
									'.$this->save_button().'
								</tbody>
							</table>
						</div>
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
                            '.$this->build_th($ops, $id).'
                            '.$this->open_td($ops, $id).'
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
					
					if( $ops['more-help'] ){
						$out .= '
							<div class="mgs-more-help-tooltip" data-html="'.$ops['more-help'].'" data-variation="wide" data-position="right center">
								<i class="question icon"></i>
							</div>
						';
					}
					
                    $out .= $this->close_td();
					$out .= '
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
			//var_dump($this->get_field_name($id, $ops));
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
                        <button class="ui primary blue button right floated"><i class="icon save outline"></i> '.__('Guardar cambios', 'mgs-admin').'</button>
                    </td>
                </tr>
            ';
            return $out;
        }
		
		private function dependent($ops, $class=''){
            return ( $ops['dependent']!='' ) ? 'data-dependent="'.$this->get_field_name($ops['dependent'], $ops).'" class="mgs-admin-row-dependent '.$class.'"' : '';
        }
        
        private function is_dependent($ops, $class=''){
            return ( $ops['dependent']!='' ) ? true : false;
        }
        
        private function build_th($ops, $id){
            if( $this->is_dependent($ops) ){
                return '<th scope="row"></th>';
            }else{
                return '<th scope="row">'.$this->label($ops, $id).'</th>';
                
            }
        }
        
        private function open_td($ops, $id){
            if( $this->is_dependent($ops) ){
                return '
                    <td class="header-dependant">'.$this->label($ops, $id).'</td>
                    <td>
                ';
            }else{
                return '<td colspan="2">';
            }
        }
        
        private function close_td(){
            return '</td>';
        }
        
        private function label($ops, $id){
            $name = $this->get_field_name($id, $ops);
            $out .= '
                <label for="'.$name.'">'.$ops['label'].$this->Label_WPML($ops).'</label>
                <p class="desc">'.$ops['desc'].'</p>
            ';
			return $out;
        }
		
		private function Label_WPML($ops){
            if( self::$compatibility['wpml'] && $ops['wpml'] ){
                return ' <span class="dashicons dashicons-translation" aria-hidden="true"></span>';
            }
        }
		
		public function admin_menu(){
			add_options_page(
				$this->admin_option['page_title'],
				$this->admin_option['menu_title'],
				$this->admin_option['capability'],
				$this->admin_option['menu_slug'],
				[$this, 'page']
			);
		}

		public function enqueue_scripts($hook){
			if( $hook=='settings_page_'.$this->slug ){
				wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-tabs');
				wp_enqueue_script('kit-fontawesome', 'https://kit.fontawesome.com/432b91a985.js');
                wp_enqueue_script('semantic-ui-js', $this->plg_url.'assets/js/semantic.min.js');
                wp_enqueue_style('semantic-ui-css', $this->plg_url.'assets/css/semantic.min.css');
                wp_enqueue_style('mgs-admin-css', $this->plg_url.'assets/css/admin.css');
			}			
		}
		
		public function get_git(){
            $time = time();
			$force = false;
            $_last = get_option('mgs-admin-last-time-git_'.$this->slug);
			
			if( isset($_GET['acc']) && $_GET['acc']=='force_git_check' ) $force = true;
			
            if( $_last && !$force ){
                if( ($time - $_last)>3600 ){
                    $git = wp_remote_get('https://api.github.com/repos/'.$this->plg_git.'/releases/latest');
                    $git = json_decode($git['body']);
                    update_option('mgs-admin-last-time-git_'.$this->slug, $time);
                    update_option('mgs-admin-last-git_'.$this->slug, $git);
                }else{
                    $git = get_option('mgs-admin-last-git_'.$this->slug);
                }
            }else{
                $git = wp_remote_get('https://api.github.com/repos/'.$this->plg_git.'/releases/latest');
				$git = json_decode($git['body']);
                update_option('mgs-admin-last-time-git_'.$this->slug, $time);
                update_option('mgs-admin-last-git_'.$this->slug, $git);
            }
            return $git;
        }
		
		public function register_settings(){
			//echo '<pre>'.print_r($this->settings, true).'</pre>';
            foreach( $this->settings as $seccion ){
                foreach( $seccion['fields'] as $id=>$attrs ){
                    if( self::$compatibility['wpml'] && $attrs['wpml'] ){
                        $languages = apply_filters('wpml_active_languages', NULL);
                        foreach( $languages as $lang_id=>$v ){
							$option_name = $this->plg_name . '_' . $id . '_' . $lang_id;
                            add_option($option_name, $attrs['def']);
                            register_setting($this->plg_name.'_options', $option_name, $this->plg_name.'_options_callback');
                        }
                    }else{
						$option_name = $this->plg_name . '_' . $id;
						add_option($option_name, $attrs['def']);
						register_setting($this->plg_name.'_options', $option_name, $this->plg_name.'_options_callback');
                    }
                }
            }
        }
		
		public function get_field_name($id, $ops=NULL){
            if( self::$compatibility['wpml'] && $ops['wpml'] ){
				$name = $this->plg_name . '_' . $id . '_' . ICL_LANGUAGE_CODE;
            }else{
                $name = $this->plg_name . '_' . $id;
            }
            return $name;
        }
        
        public function get_field_value($id, $ops=NULL){
            $name = $this->get_field_name($id, $ops);
            $val = get_option($name);
            if( $val=='' ) $val = $ops['def'];
            return $val;
        }
	}
	new MGS_Admin_Class();
}