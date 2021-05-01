<?php
if( !defined('ABSPATH') ){ exit; }

include_once('mgs-forms-public.php');

if( !class_exists('MGS_Forms') ){
	class MGS_Forms extends MGS_Theme_Upgrade{
		public static $mgs_forms_enabled;
		public static $plg_url;
		public static $post_type;
		public static $table_name_mgs_forms;
		public static $charset_collate;
		public static $sql_tabla;
		public static $admin_slug;
		public static $addon_id;
		
		private $parent;
		
		const VERSION = '0.0.1';
		
		
		function __construct($parent){
			global $wpdb;
			self::$plg_url = MGS_THEME_UPG_PLUGIN_DIR_URL.'includes/mgs-forms/';
			self::$post_type = 'mgs-addon-forms';
			self::$table_name_mgs_forms = strtolower($wpdb->prefix.'mgs_forms_submits');
			self::$charset_collate = $wpdb->get_charset_collate(); 
			self::$sql_tabla = "CREATE TABLE ".self::$table_name_mgs_forms." (id int(11) NOT NULL AUTO_INCREMENT, post_id int(11) NULL, fecha date NOT NULL, nonce varchar(255) NULL, fields text NOT NULL, agent text, refferer text, veri_date date DEFAULT NULL, veri_agent text, UNIQUE KEY id (id) ) ".self::$charset_collate.";";
			self::$admin_slug = 'mgs_theme_upgrade_page';
			self::$addon_id = 'mgs-forms';

			$this->parent = $parent;
			self::$mgs_forms_enabled = ( $this->parent->get_field_value('mgs-forms') ) ? true : false;
			if( !self::$mgs_forms_enabled ) exit('No se encuentra activado este addon.');


			add_action('init', [$this, 'create_ctp']);
			add_action('init', [$this, 'test_instalation']);
			
			if( is_admin() ){
				add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
				add_action('save_post', [$this, 'save_meta_form_fields']);
				add_action('new_to_publish', [$this, 'save_meta_form_fields']);
				add_action('admin_action_mgs-forms-acc-trash', [$this, 'trash_form']);
				add_action('admin_action_mgs-forms-acc-untrash', [$this, 'untrash_form']);
				add_action('admin_action_mgs-forms-acc-delete', [$this, 'delete_form']);
				add_action('admin_action_mgs-forms-acc-new', [$this, 'new_form']);

				include('mgs-forms-admin-class-table.php');
				//echo '<pre>'.print_r($this->parent->settings['mgs-forms'], true).'</pre>';
			}else{
				new MGS_Forms_Public();
			}
		}

		

		static function admin_forms(){
			global $mgs_admin;
			wp_enqueue_style('mgs-forms-admin-css');
			$forms_table = new MGS_Forms_Builder_Table();
			ob_start();
			?>
				<div class="mgs-forms-warper-admin">
					<div class="mgs-admin-main content-style border-top-left-radius border-top-right-radius border-bottom-left-radius border-bottom-right-radius margin-bottom big-padding">
						<h1><?php _e('Nuevo formulario', 'mgs-theme-upgrade')?></h1>
						<div class="mgs-forms-new-form">
							<div>
								<p><?php _e('Agregue un nombre para su formulario. Se le redirigirá a la página de edición del formulario.', 'mgs-theme-upgrade')?></p>
							</div>
							<div>
								<form>
								<?php wp_nonce_field('mgs_new_form'); ?>
									<input type="hidden" name="action" value="mgs-forms-acc-new">
									<div>
										<input type="text" placeholder="<?php _e('Nombre de su nuevo formulario', 'mgs-theme-upgrade')?>" required id="mgs-form-set-name" name="name" />
									</div>
									<div>
										<input type="submit" value="<?php _e('Crear formulario', 'mgs-theme-upgrade')?>" class="button button-large button-full-width" />
									</div>
								</form>
							</div>
						</div>
						
					</div>
				</div>
				<div class="mgs-forms-warper-admin">
					<div class="mgs-admin-main content-style border-top-left-radius border-top-right-radius border-bottom-left-radius border-bottom-right-radius margin-bottom no-bg no-padding-side">
						<?php
						$forms_table->get_status_links();
						?>
						<div class="clear"></div>
						<form id="mgs-forms-data" method="get">
							<?php
							$forms_table->prepare_items();
							$forms_table->display();
							?>
						</form>
						<script>
							jQuery(document).ready(function(){
								jQuery('.mgs-forms-button-copy-shortcode').on('click', function(event){
									event.preventDefault();
									var target = jQuery(this).data('target');
									copyToClipboard(document.getElementById(target));
									console.log('Shortcode copiado');
								});
							});
						</script>
					</div>
				</div>
			<?php
			$out = ob_get_contents();
        	ob_get_clean();
			$mgs_admin->page($out);
		}

		public function test_instalation(){
			global $wpdb;
			if( $wpdb->get_var("SHOW TABLES LIKE '".self::$table_name_mgs_forms."'")!=self::$table_name_mgs_forms ){
				require_once(ABSPATH.'wp-admin/includes/upgrade.php');
				dbDelta(self::$sql_tabla);
				if( $wpdb->get_var("SHOW TABLES LIKE '".self::$table_name_mgs_forms."'")!=self::$table_name_mgs_forms ){
					add_action('admin_notices', [$this, 'instalation_fail']);
					exit();
				}
			}
		}

		public function instalation_fail(){
			echo '
			<div class="error notice mgs-tables-error">
				<h3 style="color:#dc3232">MGS-Forms (Beta)</h3>
				<p>MGS-Forms no pudo crear una tabla nueva en su base de datos.</p>
			</div>
			';
		}

		public function enqueue_scripts($hook){
			global $post; 
			wp_register_style('mgs-forms-admin-css', self::$plg_url.'assets/css/admin.css');
    		if( $hook=='post-new.php' || $hook=='post.php' ){
        		if( $post->post_type==self::$post_type ){ 
					wp_register_script('form-builder-js', self::$plg_url.'assets/js/form-builder.min.js', ['jquery']);
					wp_register_script('form-builder-control-starRating', self::$plg_url.'assets/js/form-builder/js/control_plugins/starRating.js', ['jquery', 'form-builder-js']);
					wp_register_script('form-builder-control-politicas', self::$plg_url.'assets/js/form-builder/js/control_plugins/politicas.js', ['jquery', 'form-builder-js']);
					wp_register_script('mgs-forms-admin-js', self::$plg_url.'assets/js/admin.js', ['jquery', 'form-builder-js']);
					wp_enqueue_style('mgs-forms-admin-css');
				}
			}
		}
		
		public function add_edit_meta_box(){
			add_meta_box(
				'mgs_forms_shortcode_meta_box',
				__('Shortcode', 'mgs-theme-upgrade'),
				[$this, 'render_shortcode_meta_box'],
				'mgs-addon-forms',
				'normal',
				'high'
			);
			add_meta_box(
				'mgs_forms_edit_meta_box',
				__('Formulario', 'mgs-theme-upgrade'),
				[$this, 'render_edit_meta_box'],
				'mgs-addon-forms',
				'advanced',
				'high'
			);
			add_meta_box(
				'mgs_forms_options_form_meta_box',
				__('Opciones', 'mgs-theme-upgrade'),
				[$this, 'render_options_form_meta_box'],
				'mgs-addon-forms',
				'advanced',
				'high'
			);
		}
		
		public function save_meta_form_fields($post_id){
			// verify nonce
			if( !isset($_POST['mgs-forms-nonce']) || !wp_verify_nonce($_POST['mgs-forms-nonce'], basename(__FILE__)) ) return 'nonce not verified';

			// check autosave
			if( wp_is_post_autosave($post_id) ) return 'autosave';

			//check post revision
			if( wp_is_post_revision($post_id) ) return 'revision';

			// check permissions
			if( $_POST['post_type']==self::$post_type ){
				if( !current_user_can('edit_page', $post_id) ){
					return 'cannot edit page';
				}elseif( !current_user_can('edit_post', $post_id) ){
					return 'cannot edit post';
				}
				$tmp = json_decode(stripslashes($_POST['json-form']));

				$out = 'POST : <pre>'.print_r(stripslashes($_POST['json-form']), true).'</pre>';
				$out .= 'JSON DECODE : <pre>'.print_r($tmp, true).'</pre>';

				//wp_die($out);
				update_post_meta($post_id, 'mgs-forms-fields', $tmp);
			}
		}
		
		public function render_shortcode_meta_box(){
			ob_start();
			?>
			<div class="mgs_meta_box mgs_meta_box_copy_shortcode">
				<div class="help">Puede utilizar este código para insertar el formulario donde lo desee.</div>
				<div class="mgs-forms-copy-shortcode">
					<input type="text" readonly id="mgs-code-warper-<?php echo get_the_ID()?>" value="[mgs-forms id=<?php echo get_the_ID()?>]">
					<a class="mgs-forms-button-copy-shortcode" data-target="mgs-code-warper-<?php echo get_the_ID()?>"><i class="far fa-clone"></i></a>
				</div>
			</div>
			<?php
			$out = ob_get_contents();
			ob_get_clean();
			echo $out;
		}

		public function render_edit_meta_box(){
			$string = $form_data = get_post_meta(get_the_ID(), 'mgs-forms-fields');
			$string = $form_data[0];
			//$string = preg_replace('/\s+/', '', $form_data[0]);
			$string = json_encode($string, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			wp_localize_script('mgs-forms-admin-js', 'mgs_form_js', [
					'ajaxurl'			=> admin_url('admin-ajax.php'),
					'lang_folder'		=> self::$plg_url.'assets/lang',
					'lang'				=> 'es-ES',
					'form_data'			=> $string
				]
			);
			wp_enqueue_script('jquery');
			wp_enqueue_script('form-builder-js', ['jquery']);
			wp_enqueue_script('form-builder-control-starRating');
			wp_enqueue_script('form-builder-control-politicas');
			wp_enqueue_script('mgs-forms-admin-js', ['jquery', 'form-builder-js']);
			?>
			<div id="mgs-form-build-wrap">
				<div class="loading">
					<div class="mask"></div>
					Cargando editor de formularios
				</div>
			</div>
			<textarea id="json-form" name="json-form" rows="8" cols="80" style="display:none; width: 100%"></textarea>
			<textarea id="json-form_saved" name="json-form-saved" rows="8" cols="80" style="display:none; width: 100%"><?=print_r($string, true)?></textarea>
			<?php
			wp_nonce_field(basename(__FILE__), 'mgs-forms-nonce');
		}

		public function render_options_form_meta_box(){
			ob_start();
			?>
			<div class="top-menu">
				<div class="send_mail item">
					<input type="checkbox" id="send_mail" value="send_mail">
					<label for="send_mail">
						<div class="ico"></div>
						<div class="text"><?php _e('Enviar por mail', 'mgs-theme-upgrade')?></div>
					</label>
				</div>
				<div class="send_bbdd item">
					<input type="checkbox" id="send_bbdd" value="send_bbdd">
					<label for="send_bbdd">
						<div class="ico"></div>
						<div class="text"><?php _e('Almacenar en base de datos', 'mgs-theme-upgrade')?></div>
					</label>
				</div>
				<div class="send_mail_bbdd item">
					<input type="checkbox" id="send_mail_bbdd" value="send_mail_bbdd">
					<label for="send_mail_bbdd">
						<div class="ico"></div>
						<div class="text"><?php _e('Almacenar en base de dato y enviar por mail', 'mgs-theme-upgrade')?></div>
					</label>
				</div>
			</div>
			<?php
			$out = ob_get_contents();
			ob_clean();
			echo $out;
		}
		
		public function create_ctp(){
			$labels = [
				'name'					=> __( 'Formularios', 'mgs-theme-upgrade' ),
				'singular_name'			=> __( 'Formulario', 'mgs-theme-upgrade' ),
				'menu_name'				=> __( 'MGS Forms', 'mgs-theme-upgrade' ),
				'all_items'				=> __( 'Todos los formularios', 'mgs-theme-upgrade' ),
				'add_new'				=> __( 'Agregar formulario', 'mgs-theme-upgrade' ),
				'add_new_item'			=> __( 'Agregar nuevo formulario', 'mgs-theme-upgrade' ),
				'edit_item'				=> __( 'Editar formulario', 'mgs-theme-upgrade' ),
				'new_item'				=> __( 'Nuevo formulario', 'mgs-theme-upgrade' ),
				'view_item'				=> __( 'Ver formulario', 'mgs-theme-upgrade' ),
				'view_items'			=> __( 'Ver formularios', 'mgs-theme-upgrade' ),
				'search_items'			=> __( 'Buscar formularios', 'mgs-theme-upgrade' ),
				'not_found'				=> __( 'Formulario no encontrado', 'mgs-theme-upgrade' ),
				'not_found_in_trash'	=> __( 'No hay formularios en la papelera', 'mgs-theme-upgrade' ),
			];

			$args = [
				'label' 				=> __( 'Formularios', 'mgs-theme-upgrade' ),
				'labels' 				=> $labels,
				'description' 			=> '',
				'public' 				=> false,
				'publicly_queryable' 	=> true,
				'show_ui' 				=> true,
				//'show_in_rest'			=> false,
				//'rest_base' 			=> '',
				//'rest_controller_class'	=> 'WP_REST_Posts_Controller',
				'has_archive' 			=> false,
				'show_in_menu' 			=> false,
				'show_in_nav_menus' 	=> false,
				//'delete_with_user' 		=> false,
				'exclude_from_search' 	=> true,
				'capability_type' 		=> 'post',
				'map_meta_cap' 			=> true,
				'hierarchical' 			=> false,
				//'rewrite' 				=> false,
				'query_var' 			=> true,
				'supports'				=> ['title'],
				'register_meta_box_cb'	=> [$this, 'add_edit_meta_box']
			];
			register_post_type('mgs-addon-forms', $args);
		}

		public function delete_form(){
			if( current_user_can('delete_published_pages') ){
				$element_ids = '';
				if( isset($_GET['post']) ){
					$element_ids = wp_unslash($_GET['post']);
				}
	
				if( $element_ids!=='' ){
					$element_ids = (array) $element_ids;
				}
	
				if( !empty($element_ids) ){
					foreach ( $element_ids as $id ) {
						wp_delete_post($id, true);
					}
				}
			}
			$referer = mgs_get_referer();
			if( $referer ){
				wp_safe_redirect($referer);
				exit;
			}
		}

		public function trash_form(){
			if( current_user_can('delete_published_pages') ){
				$element_ids = '';
				if( isset($_GET['post']) ){
					$element_ids = wp_unslash($_GET['post']);
				}
	
				if($element_ids!=='' ){
					$element_ids = (array) $element_ids;
				}
	
				if( !empty($element_ids) ){
					foreach( $element_ids as $id ){
						wp_trash_post($id);
					}
				}
			}
			$referer = mgs_get_referer();
			if( $referer ){
				wp_safe_redirect($referer);
				exit;
			}
		}
		
		public function untrash_form(){
			if( current_user_can('publish_pages') ){
				$element_ids = '';
				if( isset($_GET['post']) ){
					$element_ids = wp_unslash($_GET['post']);
				}
	
				if($element_ids!=='' ){
					$element_ids = (array) $element_ids;
				}
	
				if( !empty($element_ids) ){
					foreach( $element_ids as $id ){
						wp_untrash_post($id);
					}
				}
			}
	
			$referer = mgs_get_referer();
			if( $referer ){
				wp_safe_redirect($referer);
				exit;
			}
		}

		public function new_form(){
			check_admin_referer('mgs_new_form');
			if( !current_user_can('publish_pages') ){
				return;
			}

			$new_form = [
				'post_title'  => isset($_GET['name']) ? sanitize_text_field(wp_unslash($_GET['name'])) : '',
				'post_status' => 'publish',
				'post_type'   => self::$post_type,
			];

			$set_id = wp_insert_post($new_form);
			if( is_wp_error($set_id) ){
				$error_string = $set_id->get_error_message();
				wp_die(esc_html($error_string));
			}

			wp_safe_redirect(get_edit_post_link($set_id, false));
			die();
		}
	}
}
