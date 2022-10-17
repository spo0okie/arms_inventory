function attach_qTip(el,force=false){
    if (el.hasClass('tooltipstered')) {
        if (force) {
            el.tooltipster('destroy');
        } else return;
    }
    let $url=el.attr('qtip_ajxhrf');
    let $text=el.attr('qtip_ttip');
    let $side=el.attr('qtip_side') || 'right,left,bottom,top';
    let $theme=el.attr('qtip_theme') || 'tooltipster-shadow tooltipster-shadow-yellow';
    let $b64text=el.attr('qtip_b64ttip');

    if (typeof $b64text !== 'undefined') $text=atob($b64text);

    let $load=undefined;
    if (typeof $url !== 'undefined') {
        if (typeof $text == 'undefined') $text='Загрузка...';
        $load=function(instance, helper) {
            let $origin = $(helper.origin);
            if ($origin.data('loaded') !== true) {
                $.get($url, function(data) {
                    instance.content(data);
                    $origin.data('loaded', true);
                });
            }
        }
    }
    el.tooltipster({
        animationDuration: 100,
        content: $text,
        contentAsHTML: true,
        delay: 50,
        interactive: true,
        theme: $theme,
        updateAnimation: 'fade',
        side: $side.split(','),
        functionBefore: $load,
    });
}


function attachAllTTips(){
    $('[qtip_ajxhrf]').not(".tooltipstered").each(function(){attach_qTip($(this));});
    $('[qtip_ttip]').not(".tooltipstered").each(function(){attach_qTip($(this));});
    $('[qtip_b64ttip]').not(".tooltipstered").each(function(){attach_qTip($(this));});
}

$(document).ready(function(){
    //setTimeout(attachAllTTips,500);
    setInterval(attachAllTTips,500);
});
