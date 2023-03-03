<?php
/**
 * Выводит кнопки создания АРМов и Оборудования в помещениях
 * User: aareviakin
 * Date: 02.05.2019
 * Time: 16:24
 */


use yii\helpers\Html;

if (!isset($places_id)) $places_id=null;


echo Html::a('Новый АРМ/оборудование',
	['/techs/create','Techs[places_id]'=>$places_id],
	['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
).' ';

echo Html::a('Новые ЗиП и материалы',
	['/materials/create','Materials[places_id]'=>$places_id],
	['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
).' ';

echo Html::a('Добавить помещение',
	['places/create','Places[parent_id]'=>$places_id],
	['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
)

?>

<span class="float-end p-2"><?= \app\components\ShowArchivedWidget::widget(['reload' => false]) ?></span>
