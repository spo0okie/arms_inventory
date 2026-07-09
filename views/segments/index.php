<?php

use app\components\DynaGridWidget;
use app\components\ShowArchivedWidget;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SegmentsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */


Url::remember();

$this->title = app\models\Segments::$titles;
//крошки собираются автоматически в layout (views/layouts/main.php)
$renderer=$this;

$filtered=false;
if (isset(Yii::$app->request->get()['app\models\SegmentsSearch'])) {
	foreach (Yii::$app->request->get()['app\models\SegmentsSearch'] as $field) if ($field) $filtered=true;
}

if (isset($switchArchivedCount)) {
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}

?>
<div class="segments-index">
	<?= DynaGridWidget::widget([
		'id' => 'segments-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Добавить новый элемент']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
			'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
			'labelBadge'=>$switchArchivedDelta
		]).'<span>',
	]) ?>
</div>
