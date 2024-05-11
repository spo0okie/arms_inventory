<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model Services */

use app\components\DynaGridWidget;
use app\components\ShowArchivedWidget;
use app\models\ServiceConnections;
use app\models\Services;

//эта страничка вызывается из другой, где есть этот виджет,
//поэтому хак со сменой поведения архивных элементов по умолчанию делаем руками, а не автоматом
ShowArchivedWidget::$defaultValue=false;
$static_view=false;
$columns=include $_SERVER['DOCUMENT_ROOT'].'/views/service-connections/columns.php';

?>
<div class="service-connections-index">
	<?= DynaGridWidget::widget([
		'id' => 'service-connections-list',
		'pageUrl'=>['/services/view','id'=>$model->id],
		'model' => new ServiceConnections(),
		'panel' => false,
		'columns' => $columns,
		'defaultOrder' => ['initiator_service','initiator_nodes','initiator_details','comment','target_service','target_nodes','target_details',],
		//'filterModel' => $searchModel,
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
					'formSelector'=>'#service-connections-list-pjax form',
				]
			],
			'rowOptions'=>function($data){return[
				'class'=> ShowArchivedWidget::archivedClass($data),
				'style'=> ShowArchivedWidget::archivedDisplay($data),
			];}
		],
	]) ?>
</div>