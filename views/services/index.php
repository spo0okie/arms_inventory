<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\components\ShowArchivedWidget;
use app\components\UrlParamSwitcherWidget;
use app\models\Services;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models Services[] */
/* @var $switchParentCount */
/* @var $switchArchivedCount */

Url::remember();
$this->title = Services::$titles;
$this->params['breadcrumbs'][] = $this->title;
$models=$dataProvider->models;

$showChildren=Yii::$app->request->get('showChildren',false);

$childrenLabel=$showChildren?'Скрыть дочерние сервисы':'Показать дочерние сервисы';
$childrenUrl=array_merge(['index'],Yii::$app->request->get());
$childrenUrl['showChildren']=!$showChildren;

$renderer=$this;


//признак того что в форму поиска вбиты данные
$filtered=false;
if (isset(Yii::$app->request->get()['ServicesSearch'])) {
	foreach (Yii::$app->request->get()['ServicesSearch'] as $field) if ($field) $filtered=true;
}


$switchParentDelta=$switchParentCount-$dataProvider->totalCount;
if ($switchParentDelta>0) $switchParentDelta='+'.$switchParentDelta;

$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;



//var_dump($dataProvider->query->createCommand()->getRawSql());
//var_dump($dataProvider->models[7]->arms);
if (true) {
?>
<div class="services-index">

	
	<?= DynaGridWidget::widget([
		'id' => 'services-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['name','sites','segment','providingSchedule','supportSchedule','responsible','compsAndTechs'],
		'createButton' => Html::a('Новый сервис', ['create'], ['class' => 'btn btn-success']) .
			' // '.UrlParamSwitcherWidget::widget([
				'param'=>'showChildren',
				'hintOff'=>'Скрыть дочерние сервисы',
				'hintOn'=>'Показать дочерние сервисы',
				'label'=>'Дочерние',
				'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
				'labelBadge'=>$switchParentDelta,
				'state'=>(bool)($searchModel->parent_id),
			]).
			' // '. ShowArchivedWidget::widget([
				'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
				'labelBadge'=>$switchArchivedDelta
			]).
			' // '.Html::a('Распределение по сотрудникам','index-by-users').
			' // '.Html::a('Дерево','index-tree'),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\Services','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>

<?php }