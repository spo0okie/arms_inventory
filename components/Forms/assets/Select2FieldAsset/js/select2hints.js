function formatSelect2ItemHint(item,url) {
    if (!item.id) return item.text;
    let hint=url+'&id='+item.id;
    return '<span qtip_ajxhrf="'+hint+'">'+item.text+'</span>';
}