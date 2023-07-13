/*
 * jQuery autoResize (textarea auto-resizer)
 * @copyright James Padolsey http://james.padolsey.com
 * @version 1.04
 */

(function($){
    
    $.fn.autoResize = function(options) {
        
        // Just some abstracted details,
        // to make plugin users happy:
        let settings = $.extend({
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
            let $textarea = $(this).css({resize:'none','overflow-y':'hidden'}),
            
                // Cache original height, for use later:
                origHeight = $textarea.height(),
				
				                
                // Need clone of textarea, hidden off screen:
                clone = (function(){

                    // Properties which may effect space taken up by characters:
                    let props = ['width','lineHeight','textDecoration','letterSpacing'],
                        propOb = {};
  
                    // Create object of styles to apply:
                    $.each(props, function(i, prop){
                        propOb[prop] = $textarea.css(prop);
                    });
                    
                    // Clone the actual textarea removing unique properties
                    // and insert before original textarea:
                    return $textarea.clone().removeAttr('id').removeAttr('name').css({
                        position: 'absolute',
                        top: 0,
                        left: -9999,
                        //height: '',
                    }).css(propOb).attr('tabIndex','-1').insertBefore($textarea);
					
                })(),
                lastScrollTop = null,
                updateSize = function() {
                    if (!$textarea.is(":visible")) {
                        //у нас невидимый объект. откладываем ресайз пока не станет видимым, иначе ничего не сработает
                        console.log("invisible!?");
                        setTimeout(updateSize,200);
                        return;
                    }
                    //делаем так чтобы в тексте было не меньше minLines строк
                    let text=$textarea.val();
                    let textLines=text.split("\n");
                    console.log("got " + textLines.length + " lines with min of " + settings.minLines);
                    for (let $i=textLines.length; $i<settings.minLines; $i++) {
                        textLines.push('fake');
                    }
                    text=textLines.join("\n");
                    //console.log(text);

                    // Prepare the clone:
                    clone.height('').val(text).scrollTop(10000);

                    // Find the height of text:
                    let scrollTop = Math.max(clone.scrollTop()+clone.height(), origHeight) + settings.extraSpace;
                    console.log("scrolltop: " + scrollTop + " vs " + lastScrollTop);
                    let toChange = $(this) //.add(clone);

                    // Don't do anything if scrollTop hasn't changed:
                    if (lastScrollTop === scrollTop) { return; }
                    lastScrollTop = scrollTop;
					
                    // Check for limit:
                    if ( scrollTop >= settings.limit ) {
                        $(this).css('overflow-y','');
                        scrollTop = settings.limit;
                    }
                    // Fire off callback:
                    settings.onResize.call(this);
					
                    // Either animate or directly apply height:
                    settings.animate && $textarea.css('display') === 'block' ?
                        toChange.stop().animate({height:scrollTop}, settings.animateDuration, settings.animateCallback)
                        : toChange.height(scrollTop);
						
					
                };
                                  
            // Bind namespaced handlers to appropriate events:
            $textarea
                .unbind('.dynSiz')
                .bind('keyup.dynSiz', updateSize)
                .bind('keydown.dynSiz', updateSize)
                .bind('change.dynSiz', updateSize);
            
        });
        
        // Chain:
        return this;
        
    };
    
    
    
})(jQuery);
