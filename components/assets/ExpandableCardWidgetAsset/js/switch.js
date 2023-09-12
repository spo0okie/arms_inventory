function ExpandableCardOversizeCheck($card) {
    $card=$($card);
    let $content=$card.find('.expandable-card-content')
    let maxHeight=$card.attr('data-expandable-max-height');
    if ($content.prop('scrollHeight') > maxHeight && $card.hasClass('compressed')) {
        if (!$content.hasClass('oversize')) {
            $content.addClass('oversize');
            $content.css('max-height',maxHeight+'px');
            let $toggle=$('<span class="expandable-card-switch">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 2">' +
                '<path d="m 0 0 l 4 0 l -2 2" fill="currentColor"/>' +
                '</svg>' +
                '</span>')
            if ($card.hasClass('switch-only-on-button'))
                $toggle.on('click',function (){ExpandableCardSwitch($card)});
            //    .on("mouseover", function(e){console.log("!");e.stopPropagation();});
            $card.prepend($toggle);
        }
    } else {
        if ($content.hasClass('oversize')) {
            $content.removeClass('oversize');
            $content.css('max-height',"");
        }
        $card.find('span.expandable-card-switch').remove();
    }
}


function ExpandableCardSwitch ($card) {
    $card.toggleClass('compressed');
    ExpandableCardOversizeCheck($card);
}

function ExpandableCardInit (card) {
    let $card=$(card);
    if (!$card.hasClass('switch-only-on-button'))
        $card.on('click',function (){ExpandableCardSwitch($card)});
    ExpandableCardOversizeCheck($card);
}


function ExpandableCardInitAll() {
    $('.expandable-card-outer').each(function (index,item){ExpandableCardInit(item)})
}

ExpandableCardInitAll();
