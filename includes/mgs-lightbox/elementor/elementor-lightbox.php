<?php
class MGS_Ligtbox_Elementor extends \Elementor\Widget_Base {
    public function get_name(){
        return 'mgs_elementor_wp_menu';
    }

    public function get_title(){
        return 'MGS Lightbox AddOn';
    }

    public function get_icon(){
        return 'fa fa-bars';
    }

    public function get_categories(){
        return ['mgs'];
    }
    
	protected function _register_controls(){
		$this->start_controls_section(
            'content_section',
            [
                'label' => 'Opciones',
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
		
		$this->add_control(
			'img_elementor',
			[
				'label'		=> 'Imagen',
				'type'		=> \Elementor\Controls_Manager::MEDIA,
				'default'	=> [
					'url'		=> \Elementor\Utils::get_placeholder_image_src(),
				],
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
				'label' => 'Mostrar titulo',
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
        echo do_shortcode('[mgs_lightbox_addon img_id="'.$settings['img_elementor']['id'].'" layout="'.$settings['layout'].'" title="'.$settings['title'].'" desc="'.$settings['desc'].'" class="for-elementor '.$settings['class'].'" /]');
    }
    
	protected function _content_template(){}
}