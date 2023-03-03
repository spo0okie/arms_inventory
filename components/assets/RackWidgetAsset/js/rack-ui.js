function rackWidgetHighlightUnit(unitSelector) {
    $(unitSelector).addClass('highlight');
    console.log("hovering "+unitSelector)
}

function rackWidgetUnselectUnit(unitSelector) {
    $(unitSelector).removeClass('highlight');
}


$('td.rack-unit').each(function(){
    let $item=$(this);
    let id=$item.attr('id');
    $item
        .on('mouseover',function(){
            rackWidgetHighlightUnit("td."+id);
        })
        .on('mouseout',function(){
            rackWidgetUnselectUnit("td."+id);
        })
})