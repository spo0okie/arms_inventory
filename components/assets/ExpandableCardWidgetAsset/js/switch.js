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
                $toggle.on('click',function (){
                        ExpandableCardSwitch($card);
                });

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
    console.log("toggle card");

    $card.toggleClass('compressed');
    ExpandableCardOversizeCheck($card);
}

function ExpandableCardInit (card) {
    let $card=$(card);
    if ($card.attr('data-expandable-card')) return;
    if (!$card.hasClass('switch-only-on-button'))
        $card.on('click',function (e){
            //проверяем что нам надо обработать клик, он не пришел со ссылки или кнопки
            let $target=$(e.target);
            let $needHandleClick=true;
            //всплываем от места клика до нашего DIV
            do {
                //если встретили ссылку или что-то с обработчиком onClick, значит уже клик обработан
                if ($target.attr('href') || $target.attr('onClick')!==undefined)
                    $needHandleClick=false;
                $target=$target.parent();
            } while (               //пока
                $needHandleClick    //мы не поняли что обрабатывать не надо
                &&                  //и
                $target.length
                &&
                !$target.hasClass('expandable-card-content')    //мы не добрались до нашего DIV
            )
            //если клик не обработан - обрабатываем
            if ($needHandleClick) ExpandableCardSwitch($card)
        });
    $card.attr('data-expandable-card',1);
    ExpandableCardOversizeCheck($card);
}


function ExpandableCardInitAll() {
    $('.expandable-card-outer').each(function (index,item){ExpandableCardInit(item)})
}

ExpandableCardInitAll();
