<?php

use app\components\DynaGridWidget;
use app\components\ShowArchivedWidget;
use app\models\Acls;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */

$this->title = Acls::$scheduleTitles;
$this->params['breadcrumbs'][] = $this->title;
$this->params['layout-container'] = 'container-fluid';

$filtered=false;
if (isset(Yii::$app->request->get()['SchedulesSearchAcl'])) {
	foreach (Yii::$app->request->get()['SchedulesSearchAcl'] as $field) if ($field) $filtered=true;
}

$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;

$renderer=$this;
?>
<div class="schedules-index">
	<?= DynaGridWidget::widget([
		'id' => 'schedules-acl-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => [
			'acePartners',
			'objects',
			'accessTypes',
			'resources',
			'name',
			'accessPeriods',
		],
		/*'gridOptions'=>[
			'rowOptions' => function ($data) {
				$archived=!$data->isWorkTime( date('Y-m-d'),date('H:i:s'));
				return [
					'class'=>$archived?'archived-item':'',
					'style'=>$archived&&!ShowArchivedWidget::isOn()?'display:none':'',
				];
			}
		],*/
		'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
			'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
			'labelBadge'=>$switchArchivedDelta,
			'reload'=>true,
		]).'<span>',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		//'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Arms','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>


</div>
