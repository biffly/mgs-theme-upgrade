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
			add_action('wp_ajax_mgs_admin_save_settings', [$this, 'mgs_admin_save_settings_callback']);
		}

		public function mgs_admin_save_settings_callback(){
			parse_str($_POST['data'], $posts);
			if( is_user_logged_in() && wp_verify_nonce($posts['_wpnonce'], 'mgs-admin-nonce') ){
				foreach( $this->$raw_settings as $key=>$value ){
					if( isset($posts[$key]) ){
						update_option($key, $posts[$key]);
					}else{
						update_option($key, $value);
					}
				}
				sleep(2);
				echo 'ok';
			}else{
				sleep(2);
				echo 'error';
			}
			die();
		}
		
		public function page(){
			$git = $this->get_git();
            ?>
        	<div class="wrap mgs-admin-warp">
				<div class="mgs-top-bar">
					<ul class="menu-left">
						<li class="brand">
							<span class="logo"></span>
							<span class="text">MGS Theme Upgrade</span>
						</li>
						<?php echo $this->build_menu()?>
					</ul>
					<ul class="menu-right">
						<li class="version">
							<span>V <?php echo $this->plg_ver?></span>
						</li>
						<?php if( version_compare($this->plg_ver, $git->tag_name)<0 ){?>
						<li class="git">
							<span>V <?php echo $git->tag_name?></span>
							<a href="#"><i class="fas fa-sync"></i></a>
						</li>
						<?php }?>
						<li class="theme">
							<div class="switch-theme">
								<input type="checkbox" id="switch-theme" name="switch-theme"/><label for="switch-theme">Toggle</label>
							</div>
						</li>
					</ul>
				</div>
				<form method="post" action="#" class="mgs-form-options" id="mgs-form-options">
					<div class="mgs-admin-main">
					<div class="back-save"></div>
                        <?php //settings_fields($this->plg_name.'_options');?>
						<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('mgs-admin-nonce')?>">
						<div class="mgs-admin-tabs">
							<?php echo $this->build_tabs()?>
						</div>
					</div>
					<div class=" mgs-admin-main mgs-admin-save">
						<div id="alert-warper">
							<div class="aviso-loading">
								<div class="ui icon message green">
									<i class="notched circle loading icon"></i>
									<div class="content">
										<div class="header">Guardando....</div>
									</div>
								</div>
							</div>
							<div class="aviso-error">
								<div class="ui icon message red">
								<i class="exclamation triangle icon"></i>
									<div class="content">
										<div class="header">Error al guardar</div>
									</div>
								</div>
							</div>
							<div class="aviso-ok">
								<div class="ui icon message green">
									<i class="check icon"></i>
									<div class="content">
										<div class="header">Opciones guardadas con exito</div>
									</div>
								</div>
							</div>
						</div>
						<div class="action"><button type="submit" class="submit-options"><?php echo __('Guardar cambios', 'mgs-admin')?></button></div>
					</div>
				</form>
			</div>
			<script>
				var switch_theme = document.querySelector('input[name=switch-theme]');
				var theme = getCookie('mgs-admin-theme');

				let trans = () => {
					document.documentElement.classList.add('transition');
					window.setTimeout(() => {
						document.documentElement.classList.remove('transition')
					}, 500)
				}


				if( theme=='' ){
					theme = 'light';
				}
				if( theme=='dark'){
					switch_theme.checked = true;
				}
				trans();
				document.documentElement.setAttribute('data-theme', theme);

				switch_theme.addEventListener('change', function() {
					if( this.checked ){
						trans();
						document.documentElement.setAttribute('data-theme', 'dark');
						setCookie('mgs-admin-theme','dark',365);
					}else{
						trans();
						document.documentElement.setAttribute('data-theme', 'light')
						setCookie('mgs-admin-theme','light',365);
					}
				})

				
				jQuery(document).ready(function(){
					jQuery('.menu-left .item').tab({
						history		: true,
						historyType	: 'hash',
						show		: { effect: "blind", duration: 800 },
						hide		: 'fade'
					});
					
					dependent_check();
                                
					jQuery('.mgs-admin-dependent-tigger').on('change', function(){
						dependent_check();
					});

					jQuery('#mgs-form-options').on('submit', function(e){
						e.preventDefault();
						jQuery('.submit-options').prop('disabled', 'disabled');
						jQuery('#alert-warper .aviso-loading').fadeIn();
						jQuery('.back-save').fadeIn(200);
						jQuery.ajax({
							type		: "post",
							url			: mgs_ajax.ajaxurl,
							data		: {
								action		: 'mgs_admin_save_settings',
								data 		: jQuery(this).serialize()
							}
						}).done(function(data){
							if( data=='ok' ){
								jQuery('#alert-warper .aviso-loading').fadeOut(1);
								jQuery('#alert-warper .aviso-ok').fadeIn();
								jQuery('.back-save').fadeOut(200);
								jQuery('.submit-options').prop('disabled', '');
								setTimeout(function(){jQuery('#alert-warper .aviso-ok').fadeOut();}, 5000);
							}else{
								jQuery('#alert-warper .aviso-loading').fadeOut(1);
								jQuery('#alert-warper .aviso-error').fadeIn();
								jQuery('.back-save').fadeOut(200);
								jQuery('.submit-options').prop('disabled', '');
								setTimeout(function(){jQuery('#alert-warper .aviso-error').fadeOut();}, 5000);
							}
						}).fail(function(data){
							jQuery('#alert-warper .aviso-loading').fadeOut(1);
							jQuery('#alert-warper .aviso-error').fadeIn();
							jQuery('.back-save').fadeOut(200);
							jQuery('.submit-options').prop('disabled', '');
							setTimeout(function(){jQuery('#alert-warper .aviso-error').fadeOut();}, 5000);
						}); 
					});
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
				function setCookie(name,value,days) {
					var expires = "";
					if (days) {
						var date = new Date();
						date.setTime(date.getTime() + (days*24*60*60*1000));
						expires = "; expires=" + date.toUTCString();
					}
					document.cookie = name + "=" + (value || "")  + expires + "; path=/";
				}
				function getCookie(name) {
					var nameEQ = name + "=";
					var ca = document.cookie.split(';');
					for(var i=0;i < ca.length;i++) {
						var c = ca[i];
						while (c.charAt(0)==' ') c = c.substring(1,c.length);
						if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
					}
					return null;
				}
			</script>
			<?php
		}
        
        private function build_menu(){
			$out = '';
			foreach( $this->settings as $id_seccion=>$attrs ){
				$style = '';
				if( $attrs['icon']=='' ) $attrs['icon'] = 'fas fa-info';
				$aviso = '';
				$pri = array_key_first($this->settings[$id_seccion]['fields']);
				if( !$this->test_settings($id_seccion) && $this->get_field_value($pri) ){
					$aviso = '<span class="aviso"><i class="aviso-config fas fa-exclamation"></i></span>';
				}
				$out .= '
					<li class="item" data-tab="'.$id_seccion.'" id="tab-item-'.$id_seccion.'">
						<span class="ico"><i class="'.$attrs['icon'].'"></i></span>
						<span class="text">'.$attrs['label'].'</span>
						'.$aviso.'
					</li>
				';
			}
			return $out;
		}
		
		private function build_tabs(){
			$out = '';
			foreach( $this->settings as $id_seccion=>$attrs ){
				$out .= '
					<div class="ui tab" data-tab="'.$id_seccion.'" id="tab-content-'.$id_seccion.'">
						<h2 class="title-seccion">'.$attrs['label'].'</h2>
						<div class="list-settings">'.$this->build_setting_seccion($attrs['fields']).'</div>
					</div>
				';
			}
			return $out;
		}
		
		private function build_setting_seccion($attrs){
			$out = '';
			if( !$attrs ) return;
			foreach( $attrs as $id=>$ops ){
				$out .= $this->open_setting_section($ops);
				$out .= $this->label($ops, $id);
				if( $this->is_dependent($ops) ){
					$out .= '<div class="value">'.$this->build_fields($ops, $id).'</div>';
				}else{
					$out .= '<div class="toogle">'.$this->build_fields($ops, $id).'</div>';
				}
				$out .= $this->close_setting_section($ops);
			}
			return $out;
		}
		
		private function build_fields($ops, $id){
			$out = '';
			switch( $ops['type'] ){
				case 'checkboxes':
					$out = $this->_checkboxes($id, $ops);
					break;
				case 'checkbox':
					//$out = $this->_checkbox($id, $ops);
					break;
				case 'onoff':
					$out = $this->_onoff($id, $ops);
					break;
				case 'text':
					$out = $this->_text($id, $ops);
					break;
				case 'test':
					$out = $this->_test($id, $ops);
					break;
				default:
					$out = '';
					break;
			}
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
				<div class="mgs-switch x2">
					<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.checked($val, true, false).' '.$disabled.'/><label for="'.$name.'">'.__('Activar / Desactivar', 'mgs-theme-upgrade').'</label>
				</div>
            ';
			return $out;
		}
		
		private function _checkboxes($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            
            $out = '
                <div class="mgs-checkbox-warper '.$ops['class'].'">
            ';
			$i = 1;
            foreach( $ops['values'] as $valor=>$etiqueta ){
                $c = '';
                if( is_array($val) ){
                    if( in_array($valor, $val) ) $c = 'checked="checked"';
                }else{
                    if( $val==$valor ) $c = 'checked="checked"';
                }
                $disabled = ( $etiqueta[1]==1 ) ? 'disabled="disabled"' : '';
                $out .= '<input type="checkbox" id="'.$name.'-'.$i.'" name="'.$name.'[]" value="'.$valor.'" '.$c.' '.$disabled.' /><label for="'.$name.'-'.$i.'">'.$etiqueta[0].'</label>';
				$i++;
            }
            $out .= '
                </div>
            ';
            return $out;
        }
		
		private function _text($id, $ops){
            $name = $this->get_field_name($id, $ops);
            $val = $this->get_field_value($id, $ops);
            $labeled = ( $ops['labeled']!='' ) ? 'labeled' : '';
			$out = '
				<div class="mgs-input-warper">
					<input type="text" class="mgs-textbox" id="'.$name.'" name="'.$name.'" value="'.$val.'">
				</div>
            ';
            return $out;
        }
		
		private function _test($id, $ops){
            $out = '
                <div class="ui icon tiny message '.call_user_func([$this, $ops['func']], 'class').'">
                    <i class="'.call_user_func([$this, $ops['func']], 'ico').' icon"></i>
                    <div class="content">
                        <p>'.call_user_func([$this, $ops['func']], 'text').'</p>
                    </div>
                </div>
            ';
            return $out;
        }
		
		private function label($ops, $id){
            $name = $this->get_field_name($id, $ops);
            $out .= '
				<div class="title">
					<h3>'.$ops['label'].$this->Label_WPML($ops).'</h3>
					<p class="description">'.$ops['desc'].'</p>
				</div>
            ';
			return $out;
        }
		
		private function Label_WPML($ops){
            if( self::$compatibility['wpml'] && $ops['wpml'] ){
                return ' <span class="dashicons dashicons-translation" aria-hidden="true"></span>';
            }
        }
		
		private function open_setting_section($ops, $class=''){
			$data = '';
			$after = '';
			$class .= ' setting';
			if( $ops['dependent']!='' ){
				$class .= ' dependent mgs-admin-row-dependent';
				$data = 'data-dependent="'.$this->get_field_name($ops['dependent'], $ops).'"';
				$after = '<div class="inner">';
			}
			return '<div class="'.$class.'" '.$data.'>'.$after;
        }
		
		private function close_setting_section($ops){
			$before = '';
			if( $ops['dependent']!='' ){
				$before = '</div><!-- inner -->';
			}
			return $before.'</div>';
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
				wp_enqueue_script('kit-fontawesome', 'https://kit.fontawesome.com/432b91a985.js');
				wp_enqueue_script('semantic-ui-js', $this->plg_url.'assets/js/semantic.min.js', ['jquery']);
				wp_enqueue_script('jquery-address-js', $this->plg_url.'assets/js/jquery.address.js', ['semantic-ui-js']);
				wp_enqueue_script('jquery-validate-js', $this->plg_url.'assets/js/jquery.validate.min.js', ['jquery']);

				wp_register_script('mgs-admin-js', $this->plg_url.'assets/js/admin.js', ['jquery']);
   				wp_localize_script('mgs-admin-js', 'mgs_ajax',['ajaxurl'=>admin_url('admin-ajax.php')]);        
   				wp_enqueue_script('mgs-admin-js');


                wp_enqueue_style('semantic-ui-css', $this->plg_url.'assets/css/semantic.min.css');
                wp_enqueue_style('mgs-admin-css', $this->plg_url.'assets/css/admin2.css');
				
			}			
		}
		
		private function is_dependent($ops){
			return ( $ops['dependent']!='' ) ? true : false;
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
			$this->$raw_settings = [];
            foreach( $this->settings as $seccion ){
                foreach( $seccion['fields'] as $id=>$attrs ){
                    if( self::$compatibility['wpml'] && $attrs['wpml'] ){
                        $languages = apply_filters('wpml_active_languages', NULL);
                        foreach( $languages as $lang_id=>$v ){
							$option_name = $this->plg_name . '_' . $id . '_' . $lang_id;
							add_option($option_name, $attrs['def']);
							$this->$raw_settings[$option_name] = $attrs['def'];
                            register_setting($this->plg_name.'_options', $option_name, $this->plg_name.'_options_callback');
                        }
                    }else{
						$option_name = $this->plg_name . '_' . $id;
						add_option($option_name, $attrs['def']);
						$this->$raw_settings[$option_name] = $attrs['def'];
						register_setting($this->plg_name.'_options', $option_name, $this->plg_name.'_options_callback');
                    }
                }
            }
        }
		
		public function test_settings($id_seccion){
			$r = true;
            foreach( $this->settings[$id_seccion]['fields'] as $field ){
                if( $field['func'] ){
					if( !call_user_func([$this, $field['func']], '') ){
						$r = false;
					}
				}
            }
			return $r;
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