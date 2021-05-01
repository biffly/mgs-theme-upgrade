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

	const UserDisabledAttrs = {
		politicas		: [
			'description',
            'placeholder'
		],
	};

	const ColWidth = {
		label   : 'Columnas',
		options : {
			'col-12'  	: '12',
			'col-11'  	: '11',
			'col-10'  	: '10',
			'col-9'  	: '9',
			'col-8'  	: '8',
			'col-7'		: '7',
			'col-6'   	: '6',
			'col-5'   	: '5',
			'col-4'   	: '4',
			'col-3'   	: '3',
			'col-2'   	: '2',
			'col-1'   	: '1',
		},
	};

	const ShowLabel = {
		label	: 'Mostrar label?',
		value	: true,
		type	: 'checkbox'
	};

    const FloatLabel = {
		label	: 'Label flotante?',
		value	: false,
		type	: 'checkbox'
	};

    const SelectFild = {
        label   : 'Fuente de datos',
        options : {
            ''          : 'Especificar manual',
            'paises'    : 'Paises'
        }
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

    const SwitchesStyle = {
        label	: 'Switches?',
		value	: false,
		type	: 'checkbox'
    }

    const PoliticasLinkText = {
        label   : 'Texto del link',
        value   : 'link text'
    }

    const PoliticasText = {
        label   : 'Texto de las políticas',
        value   : 'Acepto las {{link_politicas}} Lorem ipsum....',
        type    : 'textarea'
    }
	
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
		
		controlPosition     : 'top',
		
		showActionButtons	: false,
		
		disableFields		: [
			'autocomplete',
			'file',
			//'starRating',
			'hidden'
		],

		disabledAttrs		: disabledAttrs,

		typeUserAttrs       : {
			text        		: {
                ColWidth            : ColWidth,
                ShowLabel           : ShowLabel,
                FloatLabel          : FloatLabel
            },
			textarea    		: {
                ColWidth            : ColWidth,
                ShowLabel           : ShowLabel,
                FloatLabel          : FloatLabel
            },
			select				: {
                ColWidth            : ColWidth,
                ShowLabel           : ShowLabel,
                FloatLabel          : FloatLabel,
                SelectFild          : SelectFild
            },
			checkbox			: {
                ColWidth            : ColWidth, 
                ShowLabel           : ShowLabel
            },
			date				: {
                FormatsDates        : FormatsDates, 
                ColWidth            : ColWidth, 
                ShowLabel           : ShowLabel, 
                FloatLabel          : FloatLabel,
                EnabledJquery       : EnabledJquery
            },
			button				: {
                ColWidth            : ColWidth
            },
			'checkbox-group'	: {
                ColWidth            : ColWidth, 
                ShowLabel           : ShowLabel,
                SwitchesStyle       : SwitchesStyle
            },
			'radio-group'		: {
                ColWidth            : ColWidth, 
                ShowLabel           : ShowLabel
            },
			number				: {
                ColWidth            : ColWidth, 
                ShowLabel           : ShowLabel,
                FloatLabel          : FloatLabel,
                EnabledJquery       : EnabledJquery
            },
            politicas           : {
                PoliticasLinkText   : PoliticasLinkText,
                PoliticasText       : PoliticasText,
                ColWidth            : ColWidth,
            }
		},

		typeUserDisabledAttrs	: UserDisabledAttrs,

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
			jQuery('li#'+IdPanel).removeClass('mgs-col-12 mgs-col-11 mgs-col-10 mgs-col-9 mgs-col-8 mgs-col-7 mgs-col-6 mgs-col-5 mgs-col-4 mgs-col-3 mgs-col-2 mgs-col-1').addClass('mgs-' + data.ColWidth);
            fixCheckDOM(IdPanel);
            FixPreview(IdPanel);
		},
		onOpenFieldEdit     : function(){
			//GetFormData();
            //fixCheckDOM();
		},
		onCloseFieldEdit    : function(panel){
			var id_ele = get_element_ID(panel);
			var col = jQuery('#ColWidth-'+id_ele).val();
			jQuery('li#'+id_ele).removeClass('mgs-col-12 mgs-col-11 mgs-col-10 mgs-col-9 mgs-col-8 mgs-col-7 mgs-col-6 mgs-col-5 mgs-col-4 mgs-col-3 mgs-col-2 mgs-col-1').addClass('mgs-' + col);
			GetFormData();
            FixPreview(panel);
		},
		
	};
	
	init_FormBuilder();

	
	async function init_FormBuilder(){
		formBuilder = jQuery(fbEditor).formBuilder(options);
		await sleep(2000);
		try {
			formBuilder.actions.setData(data_form);
			editor_loading = false;
			showEditor();
            //fixCheckDOM();
			hiddeLoading();
            
		}catch(error){
			alert('Imposible leer los datos del formulario, al parecer estos contienen un error.')
			jQuery('#json-form').val(data_form).fadeIn();
            //fixCheckDOM();
			hiddeLoading();
		}
	}

	window.setInterval(function(){
		GetFormData();
	}, 1000);
	
    function fixCheckDOM(IdPanel){
        //checkboxs
        var doms = ['ShowLabel', 'FloatLabel', 'EnabledJquery', 'required', 'SwitchesStyle'];
        doms.forEach(function(item){
            if( jQuery('#' + IdPanel + '-holder .' + item + '-wrap').length ){
                var label = jQuery('#' + IdPanel + '-holder .' + item + '-wrap label');
                var check = jQuery('#' + IdPanel + '-holder .' + item + '-wrap .input-wrap input');
                jQuery('#' + IdPanel + '-holder .' + item + '-wrap').html(check[0].outerHTML + label[0].outerHTML);
            }
        });

        //select fuente de datos
        if( jQuery('#' + IdPanel + '-holder .SelectFild-wrap').length ){
            var selectfild = jQuery('#' + IdPanel + '-holder .SelectFild-wrap');
            var val = jQuery('#SelectFild-' + IdPanel).val();
            if( val!='' ){
                jQuery('#' + IdPanel + '-holder .multiple-wrap').fadeOut();
                jQuery('#' + IdPanel + '-holder .field-options').fadeOut();
            }
            jQuery('#' + IdPanel + '-holder .SelectFild-wrap').remove();
            jQuery('#' + IdPanel + '-holder .name-wrap').after(selectfild);
        }

        //politicas
        if( jQuery('#' + IdPanel + '-holder').length ){
            jQuery('#' + IdPanel + '-holder .form-elements').prepend('<div class="form-group"><p>Este campo utuliza como destino del link la pagina de wordpress de políticas de privacidad. Verifique que la misma esta establecida</p></div>');
        }


    }

    function FixPreview(IdPanel){
        console.log('fixin preview', IdPanel)
        //politicas
        if( jQuery('#' + IdPanel + '-holder').length ){
            var val_prev = jQuery('#' + IdPanel + '-holder .PoliticasText-wrap .input-wrap input').val();
            jQuery('#' + IdPanel + ' .prev-holder .formbuilder-politicas input').after(val_prev)
        }
    }

	function GetFormData(){
		//console.log('Checking data...', editor_loading);
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
		//console.log(jQuery('#json-form').val());
	});


	jQuery('.mgs-forms-button-copy-shortcode').on('click', function(event){
		event.preventDefault();
		var target = jQuery(this).data('target');
		copyToClipboard(document.getElementById(target));
		console.log('Shortcode copiado');
	});

    jQuery(document).on('change', 'select.fld-SelectFild', function(){
        var id = jQuery(this).attr('id');
        id = id.replace('SelectFild-', ''); 
        
        var val = jQuery(this).val();
        if( val!='' ){
            jQuery('#' + id + '-holder .multiple-wrap').fadeOut();
            jQuery('#' + id + '-holder .field-options').fadeOut();
        }else{
            jQuery('#' + id + '-holder .multiple-wrap').fadeIn();
            jQuery('#' + id + '-holder .field-options').fadeIn();
        }
    })
});
