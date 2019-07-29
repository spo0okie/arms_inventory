<?php
/**
 * Выводит кнопки создания АРМов и Оборудования в помещениях
 * User: aareviakin
 * Date: 02.05.2019
 * Time: 16:24
 */

use yii\bootstrap\Modal;

if (!isset($places_id)) $places_id=null;

Modal::begin([
	'id'=>'arms_add_modal',
	'size' => 'modal-lg',
	'header' => '<h2>Добавление АРМ</h2>',
	'toggleButton' => [
		'label' => 'Новый АРМ',
		'tag' => 'button',
		'class' => 'btn btn-success',
	],
]);
$arm=new \app\models\Arms();
$arm->places_id=$places_id;
echo $this->render('/arms/_form',	['model'=>$arm]);
Modal::end();

Modal::begin([
	'id'=>'techs_add_modal',
	'size' => 'modal-lg',
	'header' => '<h2>Добавление оборудования</h2>',
	'toggleButton' => [
		'label' => 'Новое оборудование',
		'tag' => 'button',
		'class' => 'btn btn-success',
	],
]);
$tech=new \app\models\Techs();
$tech->places_id=$places_id;
echo $this->render('/techs/_form',	['model'=>$tech]);
Modal::end();

$js = <<<JS
    $('#arms_add_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
    $('#techs_add_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2

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
