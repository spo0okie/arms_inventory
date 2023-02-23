function CollapsableCardWidgetSwitch (cardId) {

    $('#'+cardId).toggle();
    $('#'+cardId+'-show-button').toggle();
    $('#'+cardId+'-hide-button').toggle();
}
