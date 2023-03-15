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

function generateRackConfFor(attr) {
    let width=$('#'+attr+'_width').val();
    let height=$('#'+attr+'_height').val();
    let cols=$('#'+attr+'_cols').val();
    let rows=$('#'+attr+'_rows').val();
    let emptyLeft=$('#'+attr+'_empty_left').val();
    let emptyRight=$('#'+attr+'_empty_right').val();
    let emptyTop=$('#'+attr+'_empty_top').val();
    let emptyBottom=$('#'+attr+'_empty_bottom').val();
    let vEnum=$('#'+attr+'_vEnumUp').is(':checked')?'-1':'1';
    let hEnum=$('#'+attr+'_hEnumL').is(':checked')?'-1':'1';
    let priorEnum=$('#'+attr+'_priorEnumH').is(':checked')?'h':'v';
    let evenEnum=$('#'+attr+'_evenEnumB').is(':checked')?'-1':'1';
    let labelPre=$('#'+attr+'_labelPre').is(':checked')?1:0;
    let labelPost=$('#'+attr+'_labelPost').is(':checked')?1:0;
    let labelWidth=$('#'+attr+'_labelWidth').val();

    //------

    let rackWidth=width-emptyLeft-emptyRight;
    let rackHeight=height-emptyTop-emptyBottom;

    let arrCols=[];
    if (emptyLeft && emptyLeft>0) arrCols.push({type:"void",size:emptyLeft});
    arrCols.push({
        type:"units",
        size:rackWidth,
        count:cols
    });
    if (emptyRight && emptyRight>0) arrCols.push({type:"void",size:emptyRight});

    let arrRows=[];
    if (emptyTop && emptyTop>0) arrRows.push({type:"title",size:emptyTop});
    arrRows.push({
        type:"units",
        size:rackHeight,
        count:rows
    });
    if (emptyBottom && emptyBottom>0) arrRows.push({type:"void",size:emptyBottom});

    let data={
        cols:arrCols,
        rows:arrRows,
        hEnumeration:hEnum,
        vEnumeration:vEnum,
        evenEnumeration:evenEnum,
        priorEnumeration:priorEnum,
        labelPre:labelPre,
        labelPost:labelPost,
        labelMode:"h",
        labelWidth:labelWidth
    }

    let strData=JSON.stringify(data);
    $('#'+attr+'-constructor-alert').hide();
    $('#techmodels-'+attr).val(strData);

    previewRackConfFor(attr);
}

function previewRackConfFor(attr) {
    let strData=$('#techmodels-'+attr).val();
    $.ajax({
        type: "POST",
        url: "/web/tech-models/render-rack",
        data: {config:strData}
    }).done(function(preview) {
        $('#preview-'+attr).html(preview);
    })

}