<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\components\ShowArchivedWidget;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SandboxesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */


Url::remember();

$this->title = app\models\Sandboxes::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;

$filtered=false;
if (isset(Yii::$app->request->get()['app\models\SandboxesSearch'])) {
	foreach (Yii::$app->request->get()['app\models\SandboxesSearch'] as $field) if ($field) $filtered=true;
}

if (isset($switchArchivedCount)) {
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}

?>
<div class="sandboxes-index">
	<?= DynaGridWidget::widget([
		'id' => 'sandboxes-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Добавить новый элемент']),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\app\models\Sandboxes','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
			'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
			'labelBadge'=>$switchArchivedDelta
		]).'<span>',
	]) ?>
</div>
