<?php

use app\components\DynaGridWidget;
use app\components\ShowArchivedWidget;
use app\helpers\ArrayHelper;
use app\models\Networks;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */

if (isset($switchArchivedCount)) {
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}
$renderer=$this;

if (!isset($panel)) $panel=false;

if (!isset($columns)) $columns=['name','segment','comment','vlan','domain','usage'];

$override=[];
if (!$panel) $override['panel']=false;

$filtered=false;
if (isset(Yii::$app->request->get()['Networks'])) {
	foreach (Yii::$app->request->get()['Networks'] as $field) if ($field) $filtered=true;
}

echo DynaGridWidget::widget(ArrayHelper::recursiveOverride([
	'id'=>'networks-index',
	'header' => Networks::$titles,
	'createButton' => Html::a('Новая', ['create'], ['class' => 'btn btn-success']),
	'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
		'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
		'labelBadge'=>$switchArchivedDelta
	]).'<span>',
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	//'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	'columns' => include 'columns.php',
	'gridOptions'=>[
		'pjax' => true,
		'pjaxSettings' => [
			'neverTimeout'=>true,
		],
	],
	'defaultOrder'=>$columns,
],$override));