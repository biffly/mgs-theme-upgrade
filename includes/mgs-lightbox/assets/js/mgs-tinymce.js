//https://stackoverflow.com/questions/24871792/tinymce-api-v4-windowmanager-open-what-widgets-can-i-configure-for-the-body-op
(function() {
	tinymce.create('tinymce.plugins.mgs_lightbox_mce_button', {
		init			: function(ed, url){			
			var sh_tag = 'mgs_lightbox_addon';
			
			function getAttr(s, n){
				n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
				return n ?  window.decodeURIComponent(n[1]) : '';
			};

			function html(cls, data, con){
				var url = getAttr(data, 'url');
				var img_id = getAttr(data, 'img_id');
				var layout = getAttr(data, 'layout');
				var title = getAttr(data, 'title');
				var desc = getAttr(data, 'desc');
				data = window.encodeURIComponent(data);
				content = window.encodeURIComponent(con);
				return '<img class="mceItem '+cls+'" data-sc-img_id="'+img_id+'" data-sc-url="'+url+'" data-sc-layout="'+layout+'" data-sc-title="'+title+'" data-sc-desc="'+desc+'"  data-sc-cont="'+content+'" data-sc-attr="'+data+'" data-mce-resize="false" data-mce-placeholder="1" style="background-image: url('+url+')">';
			}

			function replaceShortcodes(content){
				return content.replace( /\[mgs_lightbox_addon([^\]]*)\]([^\]]*)\[\/mgs_lightbox_addon\]/g, function(all,attr,con){
					return html('wp-mgs_lightbox_addon', attr , con);
				});
			}

			function restoreShortcodes(content){
				return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function(match, item){
					var data = getAttr(item, 'data-sc-attr');
					var con = getAttr(item, 'data-sc-cont');
					var img_id = getAttr(item, 'data-sc-img_id');
					var url = getAttr(item, 'data-sc-url');
					var layout = getAttr(item, 'data-sc-layout');
					var title = getAttr(item, 'data-sc-title');
					var desc = getAttr(item, 'data-sc-desc');
					if( data ){
						return '['+sh_tag+' img_id="'+img_id+'" url="'+url+'" layout="'+layout+'" title="'+title+'" desc="'+desc+'"]'+con+'[/'+sh_tag+']';
					}
					return match;
				});
			}
			
			function ConfigShortCode(disenio, title, desc, url, img_id){
				var config_win = ed.windowManager.open({
					title		: 'Opciones del Lightbox',
					body		: [
						{
							type		: 'listbox',
							name		: 'disenio',
							label		: 'Diseño',
							values 		: [
								{value:'image', text:'Solo imagen'},
								{value:'image_text', text:'Imagen e información'},
								{value:'text', text:'Solo información'},
							],
							value		: disenio,
							minWidth	: 500,
							onselect	: function(){},
						},
						{
							type		: 'checkbox',
							name		: 'title',
							label		: 'Titulo',
							text		: 'Mostrar el titulo de la imagen en el lightbox?',
							minWidth	: 500,
							checked		: title,
						},
						{
							type		: 'listbox',
							name		: 'descripcion',
							label		: 'Descripción',
							values : [
								{value:'none', text:'Ocultar'},
								{value:'plano', text:'Texto plano'},
								{value:'html', text:'Texto HTML'},
							],
							minWidth	: 500,
							value		: desc
						},
						{
							type		: 'button',
							name		: 'upload',
							label		: '',
							text		: 'Imagen',
							onclick	: function(){
									OpentMediaUpload_2(img_id);
							},
						},
						
						{
							type	: 'container',
							html	: '<div id="mgs_lightbox_config_fack_img" data-url="'+url+'" data-img_id="'+img_id+'" style="background:url('+url+') center no-repeat; background-size: cover; border: #ddd solid 1px; height: 200px; width: 350px; margin: 0 auto"></div>',
						}
					],
					onsubmit	: function(e){
						console.log('submit',e);
						var url = jQuery('#mgs_lightbox_config_fack_img').data('url');
						var img_id = jQuery('#mgs_lightbox_config_fack_img').data('img_id');
						var content =  '[mgs_lightbox_addon img_id="'+img_id+'" url="'+url+'" layout="'+e.data.disenio+'" title="'+e.data.title+'" desc="'+e.data.descripcion+'"][/mgs_lightbox_addon]';
						ed.insertContent(content);
					},
					onrepaint		: function(e){},
				});
			}
			
			function OpentMediaUpload(e){
				if( e ){
					var cls  = e.target.className.indexOf('wp-mgs_lightbox_addon');
					var attr = e.target.attributes['data-sh-attr'].value;
					var content = e.target.attributes['data-sh-content'].value;
					var img_id = e.target.attributes['data-sh-imag_id'].value;
					attr = window.decodeURIComponent(attr);
				}
				custom_uploader = wp.media.frames.file_frame = wp.media({
					title	: 'MGS Lightbox AddOn',
					button	: {
						text	: 'Selecciona'
					},
					library	: {
						type	: 'image',
					},
					multiple: false
				});
				
				custom_uploader.on('open', function(){
					var imgIDs = img_id;
					var selection = custom_uploader.state().get('selection');
					if( imgIDs ){
						attachment = wp.media.attachment(imgIDs);
						attachment.fetch();
						selection.add( attachment ? [ attachment ] : [] );
					}
				});
				
				custom_uploader.on('select', function(){
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					var content =  '[mgs_lightbox_addon img_id="'+attachment.id+'" url="'+attachment.url+'" type="'+attachment.subtype+'"]'+attachment.title+'[/mgs_lightbox_addon]';
					ed.insertContent(content);
					ConfigShortCode();
				});

				custom_uploader.open();
			}
			
			function OpentMediaUpload_2(imgIDs){
				custom_uploader = wp.media.frames.file_frame = wp.media({
					title	: 'MGS Lightbox AddOn',
					button	: {
						text	: 'Selecciona'
					},
					library	: {
						type	: 'image',
					},
					multiple: false
				});
				
				custom_uploader.on('open', function(){
					var selection = custom_uploader.state().get('selection');
					if( imgIDs ){
						attachment = wp.media.attachment(imgIDs);
						attachment.fetch();
						selection.add( attachment ? [ attachment ] : [] );
					}
				});
				
				custom_uploader.on('select', function(){
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					jQuery('#mgs_lightbox_config_fack_img').css('background-image', 'url('+attachment.url+')').data('url', attachment.url).data('img_id', attachment.id);
				});

				custom_uploader.open();
			}
			
			ed.on('DblClick',function(e) {
				ConfigShortCode(e.target.dataset['scLayout'], e.target.dataset['scTitle'], e.target.dataset['scDesc'], e.target.dataset['scUrl'], e.target.dataset['scImg_id']);
			});
			
			ed.on('BeforeSetcontent', function(event){
				event.content = replaceShortcodes(event.content);
			});

			ed.on('GetContent', function(event){
				event.content = restoreShortcodes(event.content);
			});
			
			ed.addButton('mgs_lightbox_mce_button', {
				title		: 'MGS Lightbox',
				image		: url+'/tinymce/icon.png',
				onclick		: function(){
					ConfigShortCode();
				}
			});
		},
		createControl	: function(n, cm){
			return null;
		},
		getInfo			: function(){
			return {
				longname	: "MGS Lightbox AddOn",
				author		: 'Marcelo Scenna',
				authorurl	: 'https://www.marceloscenna.com.ar',
				infourl		: '',
			};
		}
	});
	tinymce.PluginManager.add('mgs_lightbox_mce_button', tinymce.plugins.mgs_lightbox_mce_button);
	
	
	
	
})();