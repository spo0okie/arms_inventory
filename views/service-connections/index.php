<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\components\ShowArchivedWidget;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServiceConnectionsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */


Url::remember();

$this->title = app\models\ServiceConnections::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;

$filtered=false;
if (isset(Yii::$app->request->get()['app\models\ServiceConnectionsSearch'])) {
	foreach (Yii::$app->request->get()['app\models\ServiceConnectionsSearch'] as $field) if ($field) $filtered=true;
}

if (isset($switchArchivedCount)) {
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}

?>
<div class="service-connections-index">
	<?= DynaGridWidget::widget([
		'id' => 'service-connections-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['initiator_service','initiator_nodes','initiator_details','comment','target_service','target_nodes','target_details',],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Добавить новый элемент']),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\ServiceConnections','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
			'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
			'labelBadge'=>$switchArchivedDelta
		]).'<span>',
	]) ?>
</div>
