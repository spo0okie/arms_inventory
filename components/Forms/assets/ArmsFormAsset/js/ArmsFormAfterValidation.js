//нам надо дополнить вот этот код расширением возможностей
//https://github.com/yiisoft/yii2/blob/master/framework/assets/yii.activeForm.js
function armsFormAjaxComplete(event,jqXHR) {
    //console.log(event);
    //console.log(jqXHR);
    if (!('responseJSON' in jqXHR)) {
        console.log('missing [responseJSON]')
        return;
    }
    let response=jqXHR.responseJSON;
    if (!('ATTR_PLACEHOLDERS' in response)) {
        console.log('missing [ATTR_PLACEHOLDERS]')
        return;
    }
    let placeholders=response.ATTR_PLACEHOLDERS;
    let attr;
    for (attr in placeholders) {
        let $input=jQuery('#'+attr);
        $input.attr('placeholder',placeholders[attr]);
        console.log('#'+attr+'=>'+placeholders[attr]);
        let select2 = $input.data('krajeeSelect2');
        // noinspection JSUnfilteredForInLoop
        if (select2) {
            let options = $input.data('s2Options');
            window[select2].placeholder = placeholders[attr];
            if ($input.data('select2')) {
                $input.select2('destroy');
            }
            // noinspection JSUnfilteredForInLoop
            jQuery
                .when($input.select2(window[select2]))
                .done(initS2Loading(attr,options));
        }
    }
}