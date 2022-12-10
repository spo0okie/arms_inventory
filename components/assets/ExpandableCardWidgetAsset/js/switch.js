function ExpandableCardOversizeCheck($card) {
    let $content=$card.find('.expandable-card-content')
    if ($content.prop('scrollHeight') > 100 && $card.hasClass('compressed')) {
        if (!$content.hasClass('oversize')) {
            $content.addClass('oversize');
            $card.prepend('<span class="expandable-card-switch"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 2"><path d="m 0 0 l 4 0 l -2 2" fill="currentColor"/></svg></span>');
        }
    } else {
        if ($content.hasClass('oversize')) {
            $content.removeClass('oversize');
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
    $card.on('click',function (){ExpandableCardSwitch($card)});
    ExpandableCardOversizeCheck($card);
}




$('.expandable-card-outer').each(function (index,item){ExpandableCardInit(item)})