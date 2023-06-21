<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\DynaGridWidget;

?>
<div class="comps-index">
	<?= DynaGridWidget::widget([
		'id' => 'service-comps-index',
		'model' => new \app\models\Comps(),
		'panel' => false,
		'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
		'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		//'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Еще раз обращаю внимание, что это делать надо только для тех компьютеров, на которых не запускается автоматический скрипт!']),
		//'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Comps','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		//'filterModel' => $searchModel,
		'gridOptions' => [
			'pjax' => true,
			'pjaxSettings' => ['options'=>[
				'enablePushState'=>false,
				'enableReplaceState'=>false,
			]],
		],
	]) ?>
</div>