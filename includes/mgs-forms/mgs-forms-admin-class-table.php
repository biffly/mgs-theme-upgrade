<?php
if( !defined('ABSPATH') ){
    exit('Direct script access denied.');
}

if( !class_exists('WP_List_Table') ){
	require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

class MGS_Forms_Builder_Table extends WP_List_Table {
    //public $columns = [];
    private static $post_type;
    private static $slug;
    
    public function __construct() {
		parent::__construct(
			[
				'singular' => esc_html__('Formulario', 'mgs-theme-upgrade'),
				'plural'   => esc_html__('Formularios', 'mgs-theme-upgrade'),
				'ajax'     => false,
				'class'    => 'mgs-form-builder-table',
			]
        );


		//$this->columns = $this->get_columns();
    }
    
    public function get_table_classes(){
		return ['widefat', 'fixed', 'striped', 'mgs-form-builder-table'];
	}

    public function get_status_links(){
        $post_status = [];
        $count_posts = wp_count_posts('mgs-addon-forms');
        $count_posts = (array) $count_posts;

        $post_status['all'] = $count_posts['publish'] + $count_posts['draft'];

        if( isset($count_posts['publish']) && $count_posts['publish'] ){
            $post_status['publish'] = $count_posts['publish'];
        }
        if( isset($count_posts['draft']) && $count_posts['draft'] ){
            $post_status['draft'] = $count_posts['draft'];
        }

        if( isset( $count_posts['trash'] ) && $count_posts['trash'] ){
            $post_status['trash'] = $count_posts['trash'];
        }
        ?>
        <ul class="subsubsub">
        <?php
        $i = 0;
        foreach( $post_status as $status => $count ){
            $i++;
            $current_status = ( isset( $_GET['status'] ) ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';
            $status_attr    = ( 'all' !== $status ) ? '&status=' . $status : '';
            $status_title   = ( 'publish' === $status ) ? __('Publicados', 'mgs-theme-upgrade' ) : $status;
        ?>
            <li class="<?php echo esc_attr( $status ); ?>">
                <a href="<?php echo esc_url( admin_url('admin.php?page=mgs_forms') . $status_attr ); ?>"<?php echo ( $status === $current_status ) ? ' class="current" ' : ''; ?>>
        <?php
            printf(
                esc_html__( '%1$s (%2$s)', 'mgs-theme-upgrade' ),
                esc_html( ucwords( $status_title ) ),
                esc_html( $count )
            );
        ?>
                </a>
            </li>
        <?php
            if( $i < count( $post_status ) ){
                echo ' | ';
            }
        }
        ?>
        </ul>
        <?php
    }

    public function get_bulk_actions(){
		if( isset($_GET['status']) && $_GET['status']==='trash' ){
			$actions = [
				'mgs-forms-acc-untrash' => esc_html__('Restaurar', 'mgs-theme-upgrade'),
				'mgs-forms-acc-delete'  => esc_html__('Borrar permanentemente', 'mgs-theme-upgrade'),
			];
		}else{
			$actions = [
				'mgs-forms-acc-trash'   => esc_html__('Enviar a papelera', 'mgs-theme-upgrade'),
			];
		}
		return $actions;
	}

    public function get_columns() {
		$columns = [
			'cb'        => '<input type="checkbox" />',
            'title'     => esc_html__('Nombre', 'mgs-theme-upgrade' ),
            'sends'     =>  esc_html__('Envios', 'mgs-theme-upgrade' ),
            'shortcode' =>  esc_html__('Shortcode', 'mgs-theme-upgrade' ),
		];
        return $columns;
    }
    
    public function prepare_items(){
		//$columns      = $this->columns;
		$columns      = $this->get_columns();
		$per_page     = 15;
		$current_page = $this->get_pagenum();
		$data         = $this->table_data($per_page, $current_page);
		$hidden       = $this->get_hidden_columns();
		$sortable     = $this->get_sortable_columns();

		$total_items = count($this->table_data());

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);

        $this->_column_headers = [$columns, $hidden, $sortable, 'title'];
        $this->items           = $data;
    }
    
    private function table_data( $per_page = -1, $current_page = 0 ) {
		$data          = [];
		$formularios_query = [];
		$status        = ['publish', 'draft'];

		// Make sure current-page and per-page are integers.
		$per_page     = (int) $per_page;
		$current_page = (int) $current_page;

		// phpcs:disable WordPress.Security.NonceVerification
		if( isset($_GET['status']) ){
			$status = sanitize_text_field(wp_unslash($_GET['status']));
		}

		$args = [
			'post_type'      => ['mgs-addon-forms'],
			'posts_per_page' => $per_page,
			'post_status'    => $status,
			'offset'         => ( $current_page - 1 ) * $per_page,
		];

		// Add sorting.
		if( isset($_GET['orderby']) ){
			$args['orderby'] = sanitize_text_field(wp_unslash($_GET['orderby']));
			$args['order']   = ( isset($_GET['order']) ) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'ASC';
		}

		$formularios_query = new WP_Query($args);

		if( $formularios_query->have_posts() ){
			while( $formularios_query->have_posts() ){
				$formularios_query->the_post();
				$element_post_id = get_the_ID();
				$element_post = [
                    'id'        => $element_post_id,
                    'title'     => get_the_title(),
                    'sends'     => 0,
                    'shortcode' => '[]',
					'date'      => get_the_date('m/d/Y'),
					'time'      => get_the_date('m/d/Y g:i:s A'),
					'status'    => get_post_status(),
				];
				$data[] = $element_post;
            }
		}
        wp_reset_postdata();
		return $data;
    }
    
    public function get_hidden_columns(){
		return [];
    }
    
    public function get_sortable_columns(){
		return [
            'title'     => ['title', false]
        ];
    }
    
    public function column_default($item, $column_name){
        switch($column_name){
            case 'id':
            case 'title':
            case 'description':
            case 'year':
            case 'director':
            case 'rating':
                return $item[$column_name];
            default:
                return print_r($item, true) ;
        }
    }

    public function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="post[]" value="%s" />', $item['id']
		);
    }
    
    public function column_title($item){
        $wpnonce = wp_create_nonce('mgs-forms-builder');
		if( isset($_GET['status']) && $_GET['status']==='trash' ){
            $actions['restore'] = sprintf('<a href="?action=%s&post=%s&_wpnonce=%s">'.esc_html__('Restaurar', 'mgs-theme-upgrade').'</a>', 'mgs-forms-acc-untrash', esc_attr($item['id']), esc_attr($wpnonce));
			$actions['delete'] = sprintf('<a href="?action=%s&post=%s&_wpnonce=%s">'.esc_html__('Borrar permanentemente', 'mgs-theme-upgrade').'</a>', 'mgs-forms-acc-delete', esc_attr($item['id']), esc_attr($wpnonce));
		}else{
			$actions['edit'] = sprintf('<a href="post.php?post=%s&action=%s">'.esc_html__('Editar', 'mgs-theme-upgrade').'</a>', esc_attr($item['id']), 'edit');
			if( current_user_can('edit_others_posts') ){
                //clonar form, ver como....
                $actions['clone_section'] = '<a href="'.$this->get_section_clone_link($item['id']).'">'.__('Duplicar', 'mgs-theme-upgrade').'</a>';
			}
			$actions['trash'] = sprintf('<a href="?action=%s&post=%s&_wpnonce=%s">'.esc_html__('Papelera', 'mgs-theme-upgrade').'</a>', 'mgs-forms-acc-trash', esc_attr($item['id']), esc_attr($wpnonce));
		}

		$status = '';
		if( $item['status']==='draft' ){
			$status = ' &mdash; <span class="post-state">'.__('Borrador', 'mgs-theme-upgrade').'</span>';
		}
		$title = '<strong><a href="post.php?post='.esc_attr($item['id']).'&action=edit">'.esc_html($item['title']).'</a>'.$status.'</strong>';
		return $title.' '.$this->row_actions($actions);
    }
    
    public function column_sends($item){
        return 0;
    }

    public function column_shortcode($item){
        return '
            <div class="mgs-forms-copy-shortcode">
                <input type="text" readonly id="mgs-code-warper-'.$item['id'].'" value="[mgs-forms id='.$item['id'].']">
                <a class="mgs-forms-button-copy-shortcode" data-target="mgs-code-warper-'.$item['id'].'"><i class="far fa-clone"></i></a>
            </div>
        ';
    }

    public function get_section_clone_link($id){
		$args = [
			'_mgs_forms_clone_nonce'    => wp_create_nonce('clone_forms'),
			'item'                      => $id,
			'action'                    => 'mgs-forms-acc-clone',
		];
		$url = add_query_arg($args, admin_url('admin.php'));
		return $url;
	}
}