<?php
/**
 * Выводит кнопки создания АРМов и Оборудования в помещениях
 * User: aareviakin
 * Date: 02.05.2019
 * Time: 16:24
 */


use yii\helpers\Html;

if (!isset($places_id)) $places_id=null;


echo Html::a('Новый АРМ',
	['/arms/create','Arms[places_id]'=>$places_id],
	['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
).' ';

echo Html::a('Новое оборудование',
	['/techs/create','Techs[places_id]'=>$places_id],
	['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
).' ';

echo Html::a('Новые ЗиП и материалы',
	['/materials/create','Materials[places_id]'=>$places_id],
	['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
).' ';



$js = <<<JS
    $('#arms_add_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
    $('#techs_add_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
    $('#materials_add_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2

    $('#materials-form').on('beforeSubmit', function(){
        var data = $(this).serialize();
        $.ajax({
            url: '/web/materials/create',
            type: 'POST',
            data: data,
            success: function(res){window.location.reload();},
            error: function(){alert('Чтото пошло не так...');}
        });
        return false;
    });

    $('#arms-form').on('beforeSubmit', function(){
        var data = $(this).serialize();
        $.ajax({
            url: '/web/arms/create',
            type: 'POST',
            data: data,
            success: function(res){window.location.reload();},
            error: function(){alert('Чтото пошло не так...');}
        });
        return false;
    });

    $('#techs-edit-form').on('beforeSubmit', function(){
        var data = $(this).serialize();
        $.ajax({
            url: '/web/techs/create',
            type: 'POST',
            data: data,
            success: function(res){window.location.reload();},
            error: function(){alert('Чтото пошло не так...');}
        });
        return false;
    });
JS;

$this->registerJs($js);
?>
