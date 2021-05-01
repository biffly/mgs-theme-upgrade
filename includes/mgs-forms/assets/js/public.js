jQuery(document).ready(function(){
    var data_form = JSON.parse(mgs_form_js.data);
    console.log(data_form);

    feather.replace();

    //https://bootstrap-datepicker.readthedocs.io/en/stable/index.html
    forEach(data_form.datepicker, function(value, prop, obj){
        console.log('ID :: ',value.id);
        console.log('Format :: ',value.format);
        jQuery('#mgs_forms_item_wrap-' + value.id + '').addClass('date');
        jQuery('#mgs_forms_item_wrap-' + value.id + ' input').datepicker({
            format: "dd/mm/yyyy",
            autoclose: true,
            toggleActive: true
        });
    });
});


var forEach = function (collection, callback, scope) {
	if (Object.prototype.toString.call(collection) === '[object Object]') {
		for (var prop in collection) {
			if (Object.prototype.hasOwnProperty.call(collection, prop)) {
				callback.call(scope, collection[prop], prop, collection);
			}
		}
	} else {
		for (var i = 0, len = collection.length; i < len; i++) {
			callback.call(scope, collection[i], i, collection);
		}
	}
};