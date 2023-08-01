function CollapsableCardWidgetSwitch (cardId) {
    let $card=$('#'+cardId);
    $card.toggle();
    $('#'+cardId+'-show-button').toggle();
    $('#'+cardId+'-hide-button').toggle();
    if ($card.attr('data-cookie-name')) {
        document.cookie = $card.attr('data-cookie-name')+'='+$card.is(':visible');
    }
}
