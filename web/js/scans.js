//посуольку строки в JS иммутабельные, то создаем свою функцию замены части строки
//взято отсюда: https://stackoverflow.com/questions/1431094/how-do-i-replace-a-character-at-a-particular-index-in-javascript
String.prototype.replaceAt=function(index, replacement) {
    return this.substr(0, index) + replacement+ this.substr(index + replacement.length);
};


//удаление скана из БД и плитки из просмотра
function scansDeleteConfirm(scans_id,contracts_id) {
    if (confirm("Удалить этот скан? Операция необратима!")) $.ajax({
        url: '/web/scans/delete?id='+scans_id+"&contracts_id="+contracts_id,
        type: 'POST',
        success: function(data) {
            //если запрос отработался
            //console.log(data[0].id+' -> '+data[0].arm_id);
            //если нам венулся код ошибки
            if (typeof data['code'] != 'undefined'){
                //если он говорит что все ок
                if (data['code'] === '0') {
                    //удаляем картинку из странички
                    $('#scan_thumb_tile_'+scans_id).hide();
                }
            }
        }
    });
}


/*
    При сохранении формы со сканами может возникнуть ситуация, что сканы еще не загружены
    Тогда мы запускаем процедуру заливки сканов, после которой дергается этот обработчик
    Задача обработчика закрыть форму, если режим сохранения это предполагает (save/apply)
 */
function contractFormAfterScansUpload() {
    let save_mode_obj = $('#contract_form_save_mode');
    let save_mode = save_mode_obj.val();
    let yiiform = $('#contracts-edit-form');
    let scans=$('#contract_form_scans_input');
    let pending_upload = scans.fileinput('getFileStack');
    if (pending_upload.length === 0 ) {
        //обрабатываем окончание режима сохранения
        switch (save_mode) {
            case 'save':
                //console.log('data saved, record id: ' + data.model.id);
                //данные сохранились. обрабатываем завершение редактирования (разные сценарии в разных ситуациях)
                yiiform.trigger('afterSubmit');
                break;
            case 'apply':
                console.log('data applied');
                break;
            default:
                console.log('unknown save mode: ' + save_mode);
                break;
        }
    }

}

/*
    При бросании скана в окно документа - пытаемся вытащить информацию из имени файла скана
    подставляем имя скана в имя документа
 */
function scansFileNameChange(name){
    if (name.length > 0) {
        let contract_name= $('#contracts-name');
        let contract_date= $('#contracts-date');
        //тут попытаемся найти дату документа в скане

        let reDate0 =       /\d{2}[-/.,\s]\d{2}[-/.,\s]\d{4}[-/.,\s]/;  //дата в формате дд-мм-гггг
        let reDate0_prfx = /^\d{2}[-/.,\s]\d{2}[-/.,\s]\d{4}[-/.,\s]+/; //дата в формате дд-мм-гггг -

        let reDate1 =       /\d{4}[-/.,\s]\d{2}[-/.,\s]\d{2}[-/.,\s]/;  //дата в формате гггг-мм-дд
        let reDate1_prfx = /^\d{4}[-/.,\s]\d{2}[-/.,\s]\d{2}[-/.,\s]+/; //дата в формате гггг-мм-дд -

        let reDate2 =       /\d{6}[-/.,\s]/;   //дата в формате ггммдд
        let reDate2_prfx = /^\d{6}[-/.,\s]+/;  //дата в формате ггммдд -

        //Порядок приоритета изменен, т.к. по шаблону 0 часто находится дата в названии документа, а префикс введен вручную по шаблону 1
        //
        if (reDate1.test(name)) { //у нас есть совпадение
            console.log('redate1');
            let docDate=reDate1.exec(name)[0];
            if (contract_date.val().length === 0) contract_date.val(docDate.replaceAt(4,'-').replaceAt(7,'-'));
            if (reDate1_prfx.test(name))
                name = name.substr(reDate1_prfx.exec(name)[0].length);
        } else if (reDate0.test(name)) { //у нас есть совпадение
            console.log('redate0');
            let docDate=reDate0.exec(name)[0];
            //выставляем дату документа
            if (contract_date.val().length === 0) contract_date.val(docDate.substr(6,4)+'-'+docDate.substr(3,2)+'-'+docDate.substr(0,2));
            if (reDate0_prfx.test(name))
                name=name.substr(reDate0_prfx.exec(name)[0].length);
        } if (reDate2.test(name)) { //у нас есть совпадение
            console.log('redate2');
            let docDate=reDate2.exec(name)[0];
            yy=docDate.substr(0,2);
            mm=docDate.substr(2,2);
            dd=docDate.substr(4,2);

            //console.log(docDate);
            if (mm<13 && dd<32 && contract_date.val().length === 0) contract_date.val(20+yy+'-'+mm+'-'+dd);
            if (reDate2_prfx.test(name))
                name=name.substr(reDate2_prfx.exec(name)[0].length);
        }

        if (contract_name.val().length === 0) {

            contract_name.val(name.split('.').slice(0, -1).join('.'));


        } //else console.log(contract_name.val()+' / '+contract_name.val().length);

    }
}


//функция обработчик применения изменений в форме редактирования документа
function contractFromApplyChanges(data){
    let save_mode_obj = $('#contract_form_save_mode');
    let save_mode = save_mode_obj.val();
    let yiiform = $('#contracts-edit-form');
    if (data.error === 'OK') {
        //если все сохранилось хорошо
        //экшн формы теперь обновление созданной модели, а не создание новой
        yiiform.attr('action', '/web/contracts/update?id=' + data.model.id);
        //сохраняем ИД созданной модели для обработчиков завершения редактирования
        $('#contract_form_model_id').val(data.model.id);

        let scans=$('#contract_form_scans_input');
        let pending_upload=scans.fileinput('getFileStack');
        let pending_count=pending_upload.length;
        console.log('pending upload : '+pending_count);
        if (pending_count >0 ) {
            scans.fileinput('upload');
        } else {
            //обрабатываем окончание режима сохранения
            switch (save_mode) {
                case 'save':
                    console.log('data saved, record id: ' + data.model.id);
                    //данные сохранились. обрабатываем завершение редактирования (разные сценарии в разных ситуациях)
                    yiiform.trigger('afterSubmit');
                    break;
                case 'apply':
                    console.log('data applied, record id: ' + data.model.id);
                    break;
                default:
                    console.log('unknown save mode: ' + save_mode);
                    break;
            }
        }

    } else if (data.error === 'ERROR') {
        // server validation failed
        yiiform.yiiActiveForm('updateMessages', data.validation, true); // renders validation messages at appropriate places
    } else {
        // incorrect server response
        alert('Ошибка обработки ответа!');
    }
}


function contractFormGotoViewOnSave() {
    //console.log('going view '+$('#contract_form_model_id').val());
    let new_url=window.location.protocol+'//'+window.location.host+'/web/contracts/view?id='+$('#contract_form_model_id').val()
    window.location=new_url;
}

function contractFormSaveClick(mode) {
    //console.log('do '+mode);
    //устанавливаем режим сохранения
    $('#contract_form_save_mode').val(mode);
    //отправляем данные
    $('#contracts-edit-form').submit();
}

