<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model Services */

/* @var $mode string */
if (!isset($mode)) $mode='aces';


use app\components\DynaGridWidget;
use app\components\ShowArchivedWidget;
use app\models\Aces;
use app\models\Services;
use yii\helpers\Html;

//эта страничка вызывается из другой, где есть этот виджет,
//поэтому хак со сменой поведения архивных элементов по умолчанию делаем руками, а не автоматом
ShowArchivedWidget::$defaultValue=false;
$static_view=false;
$columns=include $_SERVER['DOCUMENT_ROOT'].'/views/aces/columns.php';

echo ($mode=='acls')?Html::a('Добавить вх. доступ',[
		'/acls/create','Acls'=>['services_id'=>$model->id]
],[
	'class'=>'badge text-bg-success m-0 open-in-modal-form',
	'data-reload-page-on-submit'=>1
]):
Html::a('Добавить исх. доступ',[
	'/acls/create','Aces'=>['services_ids'=>[$model->id]]
],[
	'class'=>'badge text-bg-success m-0 open-in-modal-form',
	'data-reload-page-on-submit'=>1
])
?>
<div class="service-<?= $mode ?>-index">
	<?= DynaGridWidget::widget([
		'id' => 'service-'.$mode.'-list',
		'pageUrl'=>['/services/view','id'=>$model->id],
		'model' => new Aces(),
		'panel' => false,
		'columns' => $columns,
		//'defaultOrder' => ['initiator_service','initiator_nodes','initiator_details','comment','target_service','target_nodes','target_details',],
		'filterModel' => $searchModel,
		'dataProvider' => $dataProvider,
		'toggleButtonGrid'=>[
			'label' => '<i class="fas fa-wrench fa-fw"></i>',
			'title' => 'Персонализировать настройки таблицы',
			'data-pjax' => false,
			'class' => 'd-none',
		],
		'gridOptions' => [
			'layout'=>'{dynagrid}{items}',
			'showFooter' => false,
			'pjax' => true,
			'pjaxSettings' => [
				'options'=>[
					'timeout'=>30000,
					'enablePushState'=>false,
					'enableReplaceState'=>false,
					//'linkSelector'=>'tr#service-connections-list-filters td input,thead.service-connections-list tr th a',
					//'linkSelector'=>'thead.service-connections-list tr th a'
					//'formSelector'=>'#service-connections-list-pjax form',
				]
			],
		],
	]) ?>
</div>