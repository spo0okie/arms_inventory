/*
 * jQuery autoResize (textarea auto-resizer)
 * @copyright James Padolsey http://james.padolsey.com
 * @version 1.04
 */

(function(jQuery){

    jQuery.fn.autoResize = function(options) {
        
        // Just some abstracted details,
        // to make plugin users happy:
        let settings = jQuery.extend({
            onResize : function(){},
            animate : true,
            animateDuration : 100,
            animateCallback : function(){},
            extraSpace : 20,
            minLines : 1,
            limit: 1000
        }, options);

        //console.log("got minLines " + settings.minLines)
        // Only textarea's auto-resize:
        this.filter('textarea').each(function(){
            
            //получаем оригинальный textarea
            let $textarea = jQuery(this);
            $textarea                   //устанавливаем
                .css({
                    resize:'none',              //отключение уголка ресайза в углу
                    'overflow-y':'hidden',      //скрываем полосы прокрутки (чтобы не моргали)
                    'box-sizing':'border-box'   //позволяет учитывать padding и border в ширине
                })
                .removeAttr('rows');            //это больше не надо

            // Need clone of textarea, hidden off-screen:
            const $clone = $textarea.clone().removeAttr('id').removeAttr('name').css({
                position: 'absolute',
                top: 0,
                left: -99999,
                height: '',
                'box-sizing':'border-box'
            }).attr('tabIndex','-1').insertBefore($textarea);

            let lastScrollTop = null;
            let lastWidth = null;

            const updateSize = function() {
                //$textarea = jQuery(this);
                if (!$textarea.is(":visible")) {
                    //У нас невидимый объект. Откладываем ресайз пока не станет видимым, иначе ничего не сработает
                    //console.log("invisible!?");
                    setTimeout(updateSize,200);
                    return;
                }
                //делаем так, чтобы в тексте было не меньше minLines строк
                let textLines=$textarea.val().split("\n");
                while (textLines.length<settings.minLines) textLines.push('fake');

                //console.log($textarea.attr('class'));
                $clone
                    .attr('class', $textarea.attr('class'))
                    .width($textarea.width())
                    .val(textLines.join("\n"))
                    .scrollTop(10000);

                // Find the height of text:
                let scrollTop = $clone.scrollTop()+$clone.height() + settings.extraSpace;
                //let toChange = jQuery(this);

                // Don't do anything if scrollTop hasn't changed:
                if (lastScrollTop === scrollTop) return;
                lastScrollTop = scrollTop;

                // Check for limit:
                if ( scrollTop >= settings.limit ) {
                    jQuery(this).css('overflow-y','');
                    scrollTop = settings.limit;
                }
                // Fire off callback:
                settings.onResize.call(this);

                // Either animate or directly apply height:
                settings.animate && $textarea.css('display') === 'block' ?
                    $textarea.stop().animate({height:scrollTop}, settings.animateDuration, settings.animateCallback)
                    : $textarea.height(scrollTop);


            };
                                  
            // Bind namespaced handlers to appropriate events:
            $textarea
                .unbind('.dynSiz')
                .bind('keyup.dynSiz', updateSize)
                .bind('keydown.dynSiz', updateSize)
                .bind('change.dynSiz', updateSize);


            // Создаем наблюдатель за изменениями атрибутов
            const attrObserver = new MutationObserver(
                (mutations) => mutations.forEach(mutation=>{
                    if (
                        mutation.type === 'attributes'
                        &&
                        mutation.attributeName === 'class'
                    ) updateSize();
                })
            );

            const resizeObserver = new ResizeObserver(
                entries =>  entries.forEach(
                    entry => {
                        const newWidth = entry.contentRect.width;
                        if (lastWidth !== newWidth) {
                            lastWidth = newWidth;
                            updateSize();
                        }
                    }
                )
            );

            updateSize();
            resizeObserver.observe($textarea[0]);
            attrObserver.observe($textarea[0],{
                attributes: true,
                attributeFilter: ['class', 'style'] // Следим за классами и стилями
            });
            
        });
        
        // Chain:
        return this;
        
    };
    
    
    
})(jQuery);
