function get_element_ID(panel){
	var id = jQuery(panel);
	id = jQuery(id[0]).attr('id');
	return id.replace('-holder', '');
}

function get_panel_ID(panel){
	var id = jQuery(panel);
	id = jQuery(id[0]).attr('id');
	return id;
}

jQuery(document).ready(function(){
	var data_form = mgs_form_js.form_data;
	var editor_loading = true;
	var fbEditor = document.getElementById('mgs-form-build-wrap');
	var formBuilder = '';
	
	const disabledAttrs = [
		'access',
		'other',
		'toggle'
	]

	const ColWidth = {
		label   : 'Columna',
		options : {
			'mgs-col-12'  : '100%',
			'mgs-col-6'   : '50%',
			'mgs-col-4'   : '33%'
		},
	};

	const ShowLabel = {
		label	: 'Mostrar label?',
		value	: true,
		type	: 'checkbox'
	};

	const EnabledJquery = {
		label	: 'Activar jQuery.',
		value	: true,
		type	: 'checkbox'
	};

	const FormatsDates = {
		label	: 'Formato',
		options	: {
			'dd/mm/YYYY'	: 'dd/mm/YYYY',
			'YYYY-mm-dd'	: 'YYYY-mm-dd'
		}
	};

	const FontAwesomeReplace = {
		label	: 'FA Replace?',
		value	: false,
		type	: 'checkbox'
	};
	
	var options = {
		dataType            : 'json',
		render              : false,
		editOnAdd           : true,
		
		i18n				: {
			locale		: mgs_form_js.lang,
			location	: mgs_form_js.lang_folder,
		},

		controlOrder        : [
			'text',
			'textarea',
		],
		
		controlPosition     : 'left',
		
		showActionButtons	: false,
		
		disableFields		: [
			'autocomplete',
			'file',
			'starRating',
			'hidden'
		],

		disabledAttrs		: disabledAttrs,

		typeUserAttrs       : {
			text        		: {ColWidth : ColWidth, ShowLabel : ShowLabel},
			textarea    		: {ColWidth : ColWidth, ShowLabel : ShowLabel},
			select				: {ColWidth : ColWidth, ShowLabel : ShowLabel},
			checkbox			: {ColWidth : ColWidth, ShowLabel : ShowLabel},
			date				: {FormatsDates : FormatsDates, ColWidth : ColWidth, ShowLabel : ShowLabel, EnabledJquery : EnabledJquery},
			button				: {ColWidth : ColWidth},
			'checkbox-group'	: {ColWidth : ColWidth, ShowLabel : ShowLabel, FontAwesomeReplace : FontAwesomeReplace},
			'radio-group'		: {ColWidth : ColWidth, ShowLabel : ShowLabel, FontAwesomeReplace : FontAwesomeReplace},
			number				: {ColWidth : ColWidth, ShowLabel : ShowLabel, EnabledJquery : EnabledJquery},
		},

		inputSets: [
			{
				label: 'User Agreement',
				fields: [
					{
						type: 'header',
						subtype: 'h2',
						label: 'Téerminos y condiciones',
						className: 'header'
					},
					{
						type: 'paragraph',
						label: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean pretium rhoncus leo. Maecenas porta nec augue at tempor. Pellentesque laoreet orci nec arcu volutpat, sit amet ultricies libero sollicitudin.',
					},
					{
						type: 'checkbox',
						label: 'Acepto los términos y condiciones',
						required: true,
					}
				]
			}
		],
		
		onAddField          : function(IdPanel, data){
			jQuery('li#'+IdPanel).removeClass('mgs-col-12 mgs-col-6 mgs-col-4').addClass(data.ColWidth);
		},
		onOpenFieldEdit     : function(){
			//GetFormData();
		},
		onCloseFieldEdit    : function(panel){
			var id_ele = get_element_ID(panel);
			var col = jQuery('#ColWidth-'+id_ele).val();
			jQuery('li#'+id_ele).removeClass('mgs-col-12 mgs-col-6 mgs-col-4').addClass(col);
			GetFormData();
		},
		
	};
	
	init_FormBuilder();

	
	async function init_FormBuilder(){
		formBuilder = jQuery(fbEditor).formBuilder(options);
		await sleep(5000);
		try {
			formBuilder.actions.setData(data_form);
			editor_loading = false;
			showEditor();
			hiddeLoading();
		}catch(error){
			alert('Imposible leer los datos del formulario, al parecer estos contienen un error.')
			jQuery('#json-form').val(data_form).fadeIn();
			hiddeLoading();
		}
	}

	window.setInterval(function(){
		GetFormData();
	}, 1000);
	
	function GetFormData(){
		console.log('Checking data...', editor_loading);
		if( !editor_loading ){
			jQuery('#json-form').val(formBuilder.actions.getData('json', true));
		}
	}
	
	function sleep(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}

	function hiddeLoading(){
		jQuery('#mgs_forms_edit_meta_box .loading').css('opacity', '0').css('z-index', '-100');
	}

	function showEditor(){
		jQuery('.form-wrap.form-builder').css('opacity', '1');
	}

	jQuery('#post').submit(function(event){
		//event.preventDefault();
		console.log(jQuery('#json-form').val());
    });
});

        7