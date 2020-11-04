<?php
//https://theme-fusion.com/documentation/avada/configure/add-new-settings-options-panel/
if( class_exists('Fusion_Element') ){
	class MGS_SotpGuests extends Fusion_Element{
		public function __construct() {
			parent::__construct();
			add_filter('fusion_builder_element_params', [$this, 'MGS_SotpGuests_element_params'], 1, 2 );
		}
		
		public function MGS_SotpGuests_element_params($params = [], $shortcode = '') {
			if( $shortcode=='fusion_builder_container' ){
				$new_param = [
					[
						'type'			=> 'radio_button_set',
						'heading'		=> 'Activar',
						'description'	=> '',
						'param_name'	=> 'mgs_stopguests_enabled',
						'value'			=> [
							'true'	=> 'Si',
							'false'	=> 'No',
						],
						'default'		=> 'false',
						'group'			=> 'MGS StopGuests',
					],
					[
						'type'			=> 'radio_button_set',
						'heading'		=> 'Condición',
						'description'	=> '',
						'param_name'	=> 'mgs_stopguests_if',
						'value'			=> [
							'if-logued'  	=> 'Usuario logueado',
							'not_logued'	=> 'Usuario sin loguear',
						],
						'default'		=> 'if-logued',
						'group'			=> 'MGS StopGuests',
						'dependency'	=> [
							[
								'element'	=> 'mgs_stopguests_enabled',
								'value'		=> 'true',
								'operator'	=> '==',
							],
						],
					],
					[
						'type'			=> 'radio_button_set',
						'heading'		=> 'Acción',
						'description'	=> '',
						'param_name'	=> 'mgs_stopguests_action',
						'value'			=> [
							'hide'	=> 'Ocultar',
							'show'	=> 'Mostrar',
						],
						'default'		=> 'hide',
						'group'			=> 'MGS StopGuests',
						'dependency'	=> [
							[
								'element'	=> 'mgs_stopguests_enabled',
								'value'		=> 'true',
								'operator'	=> '==',
							],
						],
					],
				];
				$params = array_merge(
					$params,
					$new_param
				);
			}
			return $params;
		}
		
	}
	new MGS_SotpGuests;
}