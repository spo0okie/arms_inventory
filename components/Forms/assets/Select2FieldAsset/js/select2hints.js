function formatSelect2ItemHint(item,url) {
    if (!item.id) return item.text;
    let hint=url+'&id='+item.id;
    return '<span qtip_ajxhrf="'+hint+'">'+item.text+'</span>';
}

/* вариант списка маркеров: option рисуется сразу в целевой раскраске
   (класс marked-item + CSS-переменные из data-marker-style option'а),
   поверх — обычный ttip-хинт, если передан url */
function formatSelect2MarkerItem(item,url) {
    if (!item.id) return item.text;
    let span=document.createElement('span');
    let style=item.element?item.element.getAttribute('data-marker-style'):null;
    if (style) {
        span.className='marked-item';
        span.setAttribute('style',style);
    }
    if (url) span.setAttribute('qtip_ajxhrf',url+'&id='+item.id);
    span.textContent=item.text;
    return span.outerHTML;
}