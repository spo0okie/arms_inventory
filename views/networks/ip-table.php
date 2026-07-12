<?php

use app\components\ModelFieldWidget;
use app\components\UrlParamSwitcherWidget;
use app\models\NetIps;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

$showEmpty= Yii::$app->request->get('showEmpty',false);
$ipModel=new NetIps(); //для подписей колонок (label + «?» с hint атрибута)
?>
<table class="table table-bordered table-sm table-hover net-ips">
	<tr>
		<th>
			№
		</th>
		<th>
			<?= ModelFieldWidget::renderFieldTitle($ipModel,'text_addr',null,'span') ?>
		</th>
		<th>
			<?= ModelFieldWidget::renderFieldTitle($ipModel,'name',null,'span') ?>
		</th>
		<th>
			<?= ModelFieldWidget::renderFieldTitle($ipModel,'comment',null,'span') ?>
			<?= UrlParamSwitcherWidget::widget([
				'cssClass'=>'float-end',
				'param'=>'showEmpty',
				'hintOff'=>'Скрыть не занятые IP',
				'hintOn'=>'Показать не занятые IP',
				'label'=>'Пустые',
				'reload'=>false,
				'scriptOn'=>"\$('.empty-item').show();",
				'scriptOff'=>"\$('.empty-item').hide();",
			]) ?>

		</th>
	</tr>
	<?php
		for ($i=0; $i<$model->capacity; $i++) {
			$addr=$model->addr+$i;
			echo $this->render('ip-row',[
				'model'=>$model,
				'i'=>$i,
				'showEmpty'=>$showEmpty,
			]);
	} ?>
</table>