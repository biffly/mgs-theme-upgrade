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
		
		private $parent;
		
		const VERSION = '0.0.1';
		
		
		function __construct($parent){
			global $wpdb;
			self::$plg_url = MGS_THEME_UPG_PLUGIN_DIR_URL.'includes/mgs-forms/';
			self::$post_type = 'mgs-addon-forms';
			self::$table_name_mgs_forms = strtolower($wpdb->prefix.'mgs_forms_submits');
			self::$charset_collate = $wpdb->get_charset_collate(); 
			self::$sql_tabla = "CREATE TABLE ".self::$table_name_mgs_forms." (id int(11) NOT NULL AUTO_INCREMENT, post_id int(11) NULL, fecha date NOT NULL, nonce varchar(255) NULL, fields text NOT NULL, agent text, refferer text, veri_date date DEFAULT NULL, veri_agent text, UNIQUE KEY id (id) ) ".self::$charset_collate.";";

			
			$this->parent = $parent;
			self::$mgs_forms_enabled = ( $this->parent->get_field_value('mgs-forms') ) ? true : false;
			if( !self::$mgs_forms_enabled ) exit('No se encuentra activado este addon.');


			add_action('init', [$this, 'create_ctp']);
			add_action('init', [$this, 'test_instalation']);
			
			if( is_admin() ){
				add_filter('manage_edit-'.self::$post_type.'_columns', [$this, '_columns_head_forms']);
				
				add_action('manage_'.self::$post_type.'_posts_custom_column', [$this, '_columns_content_forms'], 10, 2);
				add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
				add_action('save_post', [$this, 'save_meta_form_fields']);
				add_action('new_to_publish', [$this, 'save_meta_form_fields']);
			}else{
				new MGS_Forms_Public();
			}
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
    		if( $hook=='post-new.php' || $hook=='post.php' ){
        		if( $post->post_type==self::$post_type ){ 
					wp_register_script('form-builder-js', self::$plg_url.'assets/js/form-builder.min.js', ['jquery']);
					wp_register_script('mgs-forms-admin-js', self::$plg_url.'assets/js/admin.js', ['jquery', 'form-builder-js']);
					wp_enqueue_style('mgs-forms-admin-css', self::$plg_url.'assets/css/admin.css');
				}
			}
		}
		
		public function add_edit_meta_box(){
			add_meta_box(
				'mgs_forms_shortcode_meta_box',
				'Shortcode',
				[$this, 'render_shortcode_meta_box'],
				'mgs-addon-forms',
				'normal',
				'high'
			);
			add_meta_box(
				'mgs_forms_edit_meta_box',
				'Formulario',
				[$this, 'render_edit_meta_box'],
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
			echo '<p>puede utilizar este codigo para insertar el formulario donde lo desee. <code>[mgs-forms id="'.get_the_ID().'"]</code></p>';
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
			wp_enqueue_script('mgs-forms-admin-js', ['jquery', 'form-builder-js']);
			?>
			<div id="mgs-form-build-wrap">
				<div class="loading">Cargando editor de formularios</div>
			</div>
			<textarea id="json-form" name="json-form" rows="8" cols="80" style="display:none; width: 100%"></textarea>
			<textarea id="json-form_saved" name="json-form-saved" rows="8" cols="80" style="display:none; width: 100%"><?=print_r($string, true)?></textarea>
			<?php
			wp_nonce_field(basename(__FILE__), 'mgs-forms-nonce');
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
				'publicly_queryable' 	=> false,
				'show_ui' 				=> true,
				'show_in_rest'			=> false,
				'rest_base' 			=> '',
				'rest_controller_class'	=> 'WP_REST_Posts_Controller',
				'has_archive' 			=> false,
				'show_in_menu' 			=> true,
				'show_in_nav_menus' 	=> false,
				'delete_with_user' 		=> false,
				'exclude_from_search' 	=> true,
				'capability_type' 		=> 'post',
				'map_meta_cap' 			=> true,
				'hierarchical' 			=> false,
				'rewrite' 				=> false,
				'query_var' 			=> true,
				'menu_position' 		=> 2,
				'supports'				=> ['title'],
				'register_meta_box_cb'	=> [$this, 'add_edit_meta_box']
			];
			register_post_type('mgs-addon-forms', $args);
		}

		public function _columns_head_forms($cols){
			unset($cols);
			$cols = [
				'cb'			=> '<input type="checkbox" />',
				'title'			=> __('Título', 'mgs-theme-upgrade'),
				'shortcode'		=> __('Código', 'mgs-theme-upgrade'),
				'date'			=> __('Fecha', 'mgs-theme-upgrade')
			];
			return $cols;
		}

		public function _columns_content_forms($col, $post_ID){
			if( $col=='shortcode' ){
				echo '<code>[mgs-forms id="'.$post_ID.'"]</code>';
			}
		}
	}
}
