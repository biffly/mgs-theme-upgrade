<?php
add_action('elementor/elements/categories_registered', 'mgs_add_elementor_widget_categories');

function mgs_add_elementor_widget_categories($elements_manager){
	$elements_manager->add_category(
		'mgs',
		[
			'title' => 'MGS',
			'icon' => 'fas fa-otter',
		]
	);
}