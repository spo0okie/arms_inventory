<?php

use app\components\ExpandableCardWidget;
use app\components\TextFieldWidget;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

?>
<div class="networks-ttip ttip-card">
	<?php
		echo $this->render('card',['model'=>$model,'static_view'=>true]);
		$descr='';
		
		if (
			Yii::$app->params['networkDescribeSegment']===true
			||
			(Yii::$app->params['networkDescribeSegment']==='auto' && !$model->notepad)
		) {
			if (is_object($model->segment) && $model->segment->history) {
				$descr.= TextFieldWidget::widget(['model'=>$model->segment,'field'=>'history']);
			}
		}
		
		if ($model->notepad) {
			$descr.= TextFieldWidget::widget(['model'=>$model,'field'=>'notepad']);
		}
		
		if ($descr) echo ExpandableCardWidget::widget([
			'content'=>$descr
		]);
	?>
</div>
