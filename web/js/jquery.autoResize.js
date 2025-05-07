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
            
                // Get rid of scrollbars and disable WebKit resizing:
            let $textarea = jQuery(this).css({resize:'none','overflow-y':'hidden'}),
            
                // Cache original height, for use later:
                //origHeight = $textarea.height(),
				
				                
                // Need clone of textarea, hidden off-screen:
                clone = (function(){
                    // Clone the actual textarea removing unique properties
                    // and insert before original textarea:
                    return $textarea.clone().removeAttr('id').removeAttr('name').removeAttr('rows').css({
                        position: 'absolute',
                        top: 0,
                        left: -9999,
                        //height: '',
                    }).attr('tabIndex','-1').insertBefore($textarea);
					
                })(),
                lastScrollTop = null,
                updateSize = function() {
                    if (!$textarea.is(":visible")) {
                        //У нас невидимый объект. Откладываем ресайз пока не станет видимым, иначе ничего не сработает
                        console.log("invisible!?");
                        setTimeout(updateSize,200);
                        return;
                    }
                    //делаем так, чтобы в тексте было не меньше minLines строк
                    let text=$textarea.val();
                    let textLines=text.split("\n");
                    //console.log("got " + textLines.length + " lines with min of " + settings.minLines);
                    for (let i=textLines.length; i<settings.minLines; i++) {
                        textLines.push('fake');
                    }
                    text=textLines.join("\n");
                    //console.log(text);

                    clone
                        .attr('class', $textarea.attr('class'))
                        .height('')
                        .val(text)
                        .scrollTop(10000);

                    // Find the height of text:
                    let scrollTop = clone.scrollTop()+clone.height() + settings.extraSpace;
                    let toChange = jQuery(this);

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
                        toChange.stop().animate({height:scrollTop}, settings.animateDuration, settings.animateCallback)
                        : toChange.height(scrollTop).attr('rows',null);
						
					
                };
                                  
            // Bind namespaced handlers to appropriate events:
            $textarea
                .unbind('.dynSiz')
                .bind('keyup.dynSiz', updateSize)
                .bind('keydown.dynSiz', updateSize)
                .bind('change.dynSiz', updateSize)
                .trigger('change.dynSiz');
            
        });
        
        // Chain:
        return this;
        
    };
    
    
    
})(jQuery);
