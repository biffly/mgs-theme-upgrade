jQuery(document).ready(function(){
    console.log(mgs_tu_js_vars);
    
    jQuery('.mgs-tu-readmore').each(function(){
        jQuery('p', this).each(function(){
            jQuery(this).addClass('mgs-tu-readmore-p').addClass('hidden');    
        });
        jQuery('p:first', this).removeClass('mgs-tu-readmore-p').addClass('mgs-tu-readmore-pf').removeClass('hidden').append(' <a href="#" class="mgs-tu-readmore-link">' + mgs_tu_js_vars.mgs_tu_readmore_text + '</a>');
    });
        
    
    jQuery('.mgs-tu-readmore-link').on('click', function(event){
        event.preventDefault;
        var parent = jQuery(this).parent().parent();
        jQuery('.mgs-tu-readmore-p', parent)
            .stop(true, true)
            .fadeIn({
                duration    : parseInt(mgs_tu_js_vars.mgs_tu_readmore_text_speed),
                queue       : false }
            )
            .css('display', 'none')
            .slideDown(parseInt(mgs_tu_js_vars.mgs_tu_readmore_text_speed));
        jQuery(this).fadeOut();
    })
});