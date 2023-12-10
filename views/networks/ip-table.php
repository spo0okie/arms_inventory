<?php

use app\components\UrlParamSwitcherWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

$showEmpty= Yii::$app->request->get('showEmpty',false);
?>
<table class="table table-bordered table-condensed net-ips">
	<tr>
		<th>
			#
		</th>
		<th>
			addr
		</th>
		<th>
			Name
		</th>
		<th>
			comment
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