function persistResizeColumn(key,val) {
    let debug=false;
    let tokens=key.split('-');
    if (!tokens.length) {
        if (debug) console.log("persistResizeColumn: incorrect tokens length: "+key);
        return;
    } //???must be like kv-2364-arms-index-place

    if (tokens[0]!=='kv') {
        if (debug) console.log("persistResizeColumn: incorrect tokens prefix");
        return;
    } //
    tokens.splice(0,1); //remove 'kv'

    let userId=tokens[0];
    if (userId=='guest') {
        if (debug) console.log("persistResizeColumn: guest user ignored: "+userId);
        return;
    } //not saving for guests
    tokens.splice(0,1); //remove 'userId'

    let column=tokens[tokens.length-1];
    tokens.splice(tokens.length-1,1); //remove 'column'

    let table=tokens.join('-');
    if (debug) console.log('SET',table,column,userId,val);

    $.ajax({
        url: "/web/ui-tables-cols/set"+
            "?table="+table+
            "&column="+column+
            "&user="+userId+
            "&value="+val
    })
}