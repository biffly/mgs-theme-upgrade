<?php
//version 1.2
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
		public $base_config;
		private $hook_styles;
		
		public $mgs;
		
		public function __construct(){
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
				foreach( $this->raw_settings as $key=>$value ){
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
		
		public function page($content=''){
			$git = $this->get_git();
            ?>
        	<div class="wrap mgs-admin-warp">
				<?php $this->build_head()?>
				<div id="mgs-notice-bar"><h2></h2></div>
				<?php
				if( $content!='' ){
					echo $content;
				}else{
					$this->form_options();
				}
				?>
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
			</div>
			<?php
		}

		private function form_options(){
			?>
				<form method="post" action="#" class="mgs-form-options" id="mgs-form-options">
					<div class="mgs-admin-main content-style border-top-left-radius border-top-right-radius border-bottom-left-radius border-bottom-right-radius margin-bottom">
						<div class="back-save"></div>
                        <?php //settings_fields($this->plg_name.'_options');?>
						<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('mgs-admin-nonce')?>">
						<div class="mgs-admin-tabs">
							<?php echo $this->build_tabs()?>
						</div>
					</div>
					<div class=" mgs-admin-main mgs-admin-save content-style border-top-left-radius border-top-right-radius border-bottom-left-radius border-bottom-right-radius margin-bottom">
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
						<div class="action"><button type="submit" class="submit-options mgs-admin-btn"><?php echo __('Guardar cambios', 'mgs-admin')?></button></div>
					</div>
					<div class="mgs-admin-main cards">
						<div class="mgs-card paypal">
							<h2>Si este Plugin te resulto útil, puedes invitarme un cafe!</h2>
							<div class="coffee"></div>
							<a href="https://www.paypal.com/donate/?hosted_button_id=JAPKZNZEYZFN2" target="_blank" class="card-cmd buy-me-a-coffee" title="Cómprame un café">Cómprame un café</a>
						</div>
					</div>
				</form>
				<script>
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
									location.reload();
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
				</script>
			<?php
		}

		private function build_head(){
			?>
			<header class="mgs-admin-header">
					<div class="inner-head content-style border-top-left-radius border-top-right-radius border-bottom margin-top" style="padding-bottom:0;">
						<div class="brand">
							<span class="logo"></span>
							<span class="text">MGS Theme Upgrade</span>
						</div>
						<div class="addons-top">
							<ul>
							<?php echo $this->build_top_menu();?>
							</ul>
						</div>
						<div class="theme">
							<div class="switch-theme">
								<input type="checkbox" id="switch-theme" name="switch-theme"/><label for="switch-theme">Toggle</label>
							</div>
						</div>
					</div>
				</header>
				<header class="mgs-admin-header">
					<div class="inner-head content-style border-bottom-left-radius border-bottom-right-radius margin-bottom">
						<div class="version">
							<span class="actual">
								<span>V <?php echo $this->plg_ver?></span>
							</span>
							<span class="update">
								<a href="#" class="mgs-admin-btn">Actualizar</a>
							</span>
						</div>
						<div class="menu">
							<ul class="menu-left">
								<?php echo $this->build_menu()?>
							</ul>
						</div>
					</div>
				</header>
			<?php
		}
		
		private function build_top_menu(){
			$screen = get_current_screen();
			$out = '';
			foreach( $this->settings['addons']['fields'] as $id_seccion=>$attrs ){
				if( $attrs['show_on_top'] && $this->get_field_value($id_seccion) ){
					$active = '';
					if( in_array($screen->base, $this->$hook_styles) && $screen->base!='toplevel_page_'.$this->admin_option['menu_slug'] ){
						$active = 'active';
					}
					$out .= '
						<li class="'.$active.'">
							<a href="#" title="addon">'.$attrs['label'].'</a>
					';
					if( $attrs['subs-menus'] ){
						$out .= '<ul>';
						foreach( $attrs['subs-menus'] as $sub_menu ){
							$out .= '
								<li>
									<a href="admin.php?page='.$sub_menu['slug'].'">
							';
							if( $sub_menu['ico']) $out .= $sub_menu['ico'];
							$out .= '
										<div class="text">
											<div class="label">'.$sub_menu['label'].'</div>
							';
							if( $sub_menu['desc'] ) $out .= '<div class="desc">'.$sub_menu['desc'].'</div>';
							$out .= '
										</div>
									</a>
								</li>
							';
						}
						$out .= '</ul>';
					}
					$out .= '</li>';
				}
			}

			return $out;
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
						<a class="a" href="admin.php?page='.$this->admin_option['menu_slug'].'#/'.$id_seccion.'">
							<span class="ico"><i class="'.$attrs['icon'].'"></i></span>
							<span class="text">'.$attrs['label'].'</span>
							'.$aviso.'
						</a>
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
						<div class="title-seccion">'.$attrs['label'].'</div>
						<div class="list-settings">'.$this->build_setting_seccion($attrs['fields'], $id_seccion).'</div>
					</div>
				';
			}
			return $out;
		}
		
		private function build_setting_seccion($attrs, $id_section){
			$out = '';
			if( !$attrs ) return;
			foreach( $attrs as $id=>$ops ){
				//$out .= '<pre>'.print_r($attrs, true).'</pre>';
				$out .= $this->open_setting_section($ops, $id_section, $id);
				$out .= $this->label($ops, $id_section, $id);
				if( $this->is_dependent($ops) ){
					$out .= '<div class="value">'.$this->build_fields($ops, $id).'</div>';
				}else{
					
					/*if( $this->chech_compatibility($id) ){
						
					}*/
					$out .= '<div class="toogle">'.$this->build_fields($ops, $id).'</div>';
				}
				$out .= $this->close_setting_section($ops, $id_section, $id);
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
		
		public function chech_compatibility($setting){
			// Verifico que el array de compatibilidad este establecido.
			// Si esta establecido continuo con la verificacion.
			// Si no existe o no esta establecido es porque el elemento 
			// es compatible o no hace falta verificar
			if( !is_array($setting) ) return true;
			
			foreach( self::$compatibility as $c=>$b ){
				if( in_array($c, $setting) && $b==true ){
					return true;
				}
			}
			return false;
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
		
		private function label($ops, $id_section, $id){
            $name = $this->get_field_name($id, $ops);
            $out .= '
				<div class="title">
					<h3>'.$ops['label'].$this->Label_WPML($ops).'</h3>
					<p class="description">'.$ops['desc'].'</p>
				</div>
            ';
			return $out;
        }
		
		public function Label_WPML($ops){
            if( self::$compatibility['wpml'] && $ops['wpml'] ){
                return ' <span class="dashicons dashicons-translation" aria-hidden="true"></span>';
            }
        }
		
		private function open_setting_section($ops, $id_section, $id, $class=''){
			$data = '';
			$after = '';
			$class .= ' setting setting-'.$id;
			if( $ops['dependent']!='' ){
				$class .= ' dependent mgs-admin-row-dependent';
				$data = 'data-dependent="'.$this->get_field_name($ops['dependent'], $ops).'"';
				$after = '<div class="inner">';
			}elseif( !$this->chech_compatibility($this->settings[$id_section]['fields'][$id]['compatibility']) ){
				$class .= ' not-compatibility';
			}
			return '<div class="'.$class.'" '.$data.'>'.$after;
        }
		
		private function close_setting_section($ops, $id_section, $id){
			$before = '';
			if( $ops['dependent']!='' ){
				$before = '</div><!-- inner -->';
			}else{
				if( is_array($this->settings[$id_section]['fields'][$id]['compatibility']) ){
					$before = '<div class="compatibility">';
					//$before .= '<pre>'.print_r($this->settings[$id_section]['fields'][$id]['compatibility'], true).'</pre>';
					//$before .= '<pre>'.print_r(self::$compatibility, true).'</pre>';
					foreach( $this->settings[$id_section]['fields'][$id]['compatibility'] as $theme ){
						$before .= '<a class="'.$theme.' '.$theme.'-'.self::$compatibility[$theme].'" title="'.$theme.'">'.$theme.'</a>';
					}
					$before .= '</div>';
				}
			}
			return $before.'</div>';
		}
		
		public function admin_menu(){
			add_menu_page(
				$this->admin_option['page_title'],
				$this->admin_option['menu_title'],
				$this->admin_option['capability'],
				$this->admin_option['menu_slug'],
				[$this, 'page']
			);
			$this->$hook_styles[] = 'toplevel_page_'.$this->admin_option['menu_slug'];
			$added_subs = false;
			foreach( $this->settings['addons']['fields'] as $id_seccion=>$attrs ){
				if( $attrs['show_on_top'] && $this->get_field_value($id_seccion) && $attrs['subs-menus'] ){
					foreach( $attrs['subs-menus'] as $sub_menu ){
						$this->$hook_styles[] = 'mgs-theme-upgrade_page_'.$sub_menu['slug'];
						if( $sub_menu['menu_side'] ){
							add_submenu_page(
								$this->admin_option['menu_slug'],
								$sub_menu['label'],
								$sub_menu['label'],
								$this->admin_option['capability'],
								$sub_menu['slug'],
								$sub_menu['callback']
							);
							$added_subs = false;
						}
					}
				}
			}

			
		}

		public function enqueue_scripts($hook){
			if( in_array($hook, $this->$hook_styles) ){
				wp_enqueue_script('jquery');
				
				wp_enqueue_script('semantic-ui-js', $this->plg_url.'assets/js/semantic.min.js', ['jquery']);
				wp_enqueue_script('jquery-address-js', $this->plg_url.'assets/js/jquery.address.js', ['semantic-ui-js']);
				wp_enqueue_script('jquery-validate-js', $this->plg_url.'assets/js/jquery.validate.min.js', ['jquery']);

				


                wp_enqueue_style('semantic-ui-css', $this->plg_url.'assets/css/semantic.min.css');
                wp_enqueue_style('mgs-admin-css', $this->plg_url.'assets/css/admin2.css');
			}

			wp_register_script('mgs-admin-js', $this->plg_url.'assets/js/admin.js', ['jquery']);
			wp_localize_script('mgs-admin-js', 'mgs_ajax',['ajaxurl'=>admin_url('admin-ajax.php')]);        
			wp_enqueue_script('mgs-admin-js');

			wp_enqueue_script('kit-fontawesome', 'https://kit.fontawesome.com/432b91a985.js');
			wp_enqueue_style('mgs-all-admin-css', $this->plg_url.'assets/css/admin.css');
		}
		
		public function is_dependent($ops){
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
			$this->raw_settings = [];
            foreach( $this->settings as $seccion ){
                foreach( $seccion['fields'] as $id=>$attrs ){
                    if( self::$compatibility['wpml'] && $attrs['wpml'] ){
                        $languages = apply_filters('wpml_active_languages', NULL);
                        foreach( $languages as $lang_id=>$v ){
							$option_name = $this->plg_name . '_' . $id . '_' . $lang_id;
							add_option($option_name, $attrs['def']);
							$this->raw_settings[$option_name] = $attrs['def'];
                            register_setting($this->plg_name.'_options', $option_name, $this->plg_name.'_options_callback');
                        }
                    }else{
						$option_name = $this->plg_name . '_' . $id;
						add_option($option_name, $attrs['def']);
						$this->raw_settings[$option_name] = $attrs['def'];
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