<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\components\ShowArchivedWidget;
use app\models\Comps;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CompsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */

Url::remember();

if (isset($switchArchivedCount)) {
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}

$filtered=false;
if (isset(Yii::$app->request->get()['CompsSearch'])) {
	foreach (Yii::$app->request->get()['CompsSearch'] as $field) if ($field) $filtered=true;
}

$this->title = Comps::$titles;
$this->params['breadcrumbs'][] = Comps::$titles;
$renderer=$this;
?>
<div class="comps-index">
	<?= DynaGridWidget::widget([
		'id' => 'comps-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Еще раз обращаю внимание, что это делать надо только для тех компьютеров, на которых не запускается автоматический скрипт!']),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\Comps','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
			'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
			'labelBadge'=>$switchArchivedDelta,
			'state'=>$searchModel->archived
		]).'<span>',
	]) ?>
</div>
