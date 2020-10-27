<?php
class MGS_Gallery_Ligtbox_Elementor extends \Elementor\Widget_Base {
    public function get_name(){
        return 'mgs_elementor_wp_menu_1';
    }

    public function get_title(){
        return 'MGS Lightbox Gallery AddOn';
    }

    public function get_icon(){
        return 'fa fa-bars';
    }

    public function get_categories(){
        return ['mgs'];
    }
    
	protected function _register_controls(){
		$_img_sizes = mgs_tu_get_all_image_sizes();
		$img_sizes = [];
		foreach($_img_sizes as $k=>$v){
			$img_sizes[$k] = $k;
		}
		
		
		$this->start_controls_section(
            'content_section',
            [
                'label' => 'Opciones',
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
		
		$this->add_control(
			'imgs_elementor',
			[
				'label'		=> 'Imagenes',
				'type'		=> \Elementor\Controls_Manager::GALLERY,
				'default'	=> [
					'url'		=> \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);
		$this->add_control(
			'cols',
			[
				'label' => 'Columnas',
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '3',
				'options' => [
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10'  => '10',
				],
			]
		);
		$this->add_control(
			'theme',
			[
				'label' => 'Tema/style',
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''			=> 'Sin estilo',
					'basico'	=> 'Basico',
				],
			]
		);
		$this->add_control(
			'size',
			[
				'label' => 'Tamaño thumb',
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $img_sizes,
			]
		);
		$this->add_control(
			'layout',
			[
				'label' => 'Diseño',
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'image',
				'options' => [
					'image'  => 'Solo imagen',
					'image_text' => 'Imagen y texto',
					'text' => 'Texto solo',
				],
			]
		);
		$this->add_control(
			'title',
			[
				'label' => 'Mostrar titulo en lightbox',
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => 'Mostrar',
				'label_off' => 'Ocultar',
				'return_value' => 'true',
				'default' => 'true',
			]
		);
		$this->add_control(
			'title_list',
			[
				'label' => 'Mostrar titulo en listado',
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => 'Mostrar',
				'label_off' => 'Ocultar',
				'return_value' => 'true',
				'default' => 'true',
			]
		);
		$this->add_control(
			'desc',
			[
				'label' => 'Descripción',
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'html',
				'options' => [
					'html'  => 'Texto en HTML',
					'plano' => 'Texto plano',
				],
			]
		);
		$this->add_control(
			'class',
			[
				'label' => 'Class',
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			]
		);
		
		
		
		$this->end_controls_section();
    }
    
    protected function render(){
		
        $settings = $this->get_settings_for_display();
        //echo do_shortcode('[mgs_lightbox_addon img_id="'.$settings['img_elementor']['id'].'" layout="'.$settings['layout'].'" title="'.$settings['title'].'" desc="'.$settings['desc'].'" class="for-elementor '.$settings['class'].'" /]');
		$array_images = [];
		foreach( $settings['imgs_elementor'] as $k=>$v ){
			$array_images[] = $v['id'];
		}
		$array_images_raw = implode(',', $array_images);
		//echo '<pre>'.print_r($settings, true).'</pre>';
		echo do_shortcode('[mgs_gallery_lightbox_addon 
			img_id="'.$array_images_raw.'" 
			layout="'.$settings['layout'].'" 
			title="'.$settings['title'].'" 
			title_list="'.$settings['title_list'].'" 
			desc="'.$settings['desc'].'" 
			class="for-elementor '.$settings['class'].'" 
			cols="'.$settings['cols'].'" 
			theme="'.$settings['theme'].'" 
			size="'.$settings['size'].'"
		/]');
    }
    
	protected function _content_template(){}
}