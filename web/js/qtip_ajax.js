function attach_qTip(el){
    if (el.hasClass('tooltipstered')) return;
    let $url=el.attr('qtip_ajxhrf');
    let $text=el.attr('qtip_ttip');
    let $b64text=el.attr('qtip_b64ttip');
    if (typeof $b64text !== 'undefined') {
        //console.log(typeof $b64text);
        $text=atob($b64text);
        //console.log($text);
    }
    let $load=undefined;
    if (typeof $url != 'undefined') {
        if (typeof $text == 'undefined') $text='Загрузка...';
        $load=function(instance, helper) {
            var $origin = $(helper.origin);
            if ($origin.data('loaded') !== true) {
                $.get($url, function(data) {
                    instance.content(data);
                    $origin.data('loaded', true);
                    /*setTimeout(()=>{
                        console.log('repositioning');
                        $origin.tooltipster('reposition');
                    },100);*/
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
        theme: 'tooltipster-shadow tooltipster-shadow-yellow',
        updateAnimation: 'fade',
        side: [ 'right', 'left', 'bottom', 'top'],
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
