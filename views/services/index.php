<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\Services[] */

\yii\helpers\Url::remember();
$this->title = \app\models\Services::$titles;
$this->params['breadcrumbs'][] = $this->title;
$models=$dataProvider->models;

$showChildren=Yii::$app->request->get('showChildren',false);
$showArchived=Yii::$app->request->get('showArchived',false);

$childrenLabel=$showChildren?'Скрыть дочерние сервисы':'Показать дочерние сервисы';
$childrenUrl=array_merge(['index'],Yii::$app->request->get());
$childrenUrl['showChildren']=!$showChildren;

$archivedLabel=$showArchived?'Скрыть архивные':'Показать архивные';
$archivedUrl=array_merge(['index'],Yii::$app->request->get());
$archivedUrl['showArchived']=!$showArchived;

$renderer=$this;

//var_dump($dataProvider->query->createCommand()->getRawSql());
//var_dump($dataProvider->models[7]->arms);
if (true) {
?>
<div class="services-index">

	<div class="pull-right">
		<?= Html::a(
			'Распределение по сотрудникам',
			'index-by-users'
		) ?>
		//
		<?= Html::a(
			$childrenLabel,
			$childrenUrl
		) ?>
		//
		<?= Html::a(
			$archivedLabel,
			$archivedUrl
		) ?>
		
	</div>
	
	<?php Pjax::begin(); ?>
	<?= DynaGridWidget::widget([
		'id' => 'services-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['name','sites','segment','providingSchedule','supportSchedule','responsible','compsAndTechs'],
		'createButton' => Html::a('Новый сервис', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Services','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	<?php Pjax::end(); ?>
</div>

<?php }