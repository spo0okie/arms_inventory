<?php

/* @var $this yii\web\View */
/* @var $models app\models\Aces[] */
/* @var $hintModel \app\models\base\ArmsModel модель-владелец списка: даёт тултип-«?» заголовка (атрибут acls) */
/* @var $static_view bool */

use app\components\ListObjectsWidget;
use app\components\ModelFieldWidget;

if (!isset($static_view)) $static_view=false;
//не полагаемся на ShowArchivedWidget::$defaultValue: тогглер (CornerWidget) на
//страницах карточек рендерится ПОЗЖЕ этого блока, и дефолт еще не переключен
//в «скрывать» — архивные строки вместо скрытия оставались бы зачеркнутыми
if (!isset($show_archived)) $show_archived=(bool)Yii::$app->request->get('showArchived',false);

//сводки «куда и какой доступ»: [расписание:] ресурс — типы доступа: пояснение
//(см. aces/summary.php); архивные записи скрываются/зачеркиваются поэлементно
echo ListObjectsWidget::widget([
	'models'=>$models,
	'itemViewPath'=>'/aces/summary',
	'item_options'=>['show'=>'resource','static_view'=>$static_view],
	'show_archived'=>$show_archived,
	'title'=>isset($hintModel) && is_object($hintModel)
		? ModelFieldWidget::fieldTitle($hintModel,'acls',null,'Имеет доступ к:')[0]
		: 'Имеет доступ к:',
	'glue'=>'<br />',
	'lineBr'=>false,
	'card_options'=>['cardClass'=>'mb-3'],
]);
