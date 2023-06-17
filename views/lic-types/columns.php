<?php

/* @var $this yii\web\View */

$renderer=$this;

return [
	[
		'attribute' => 'descr',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/lic-types/item', ['model' => $data]);
		}
	],
	[
		'attribute'=>'comment',
		'format'=>'raw',
		'value'=>function($item) {
			return \app\components\ExpandableCardWidget::widget([
				'content'=>Yii::$app->formatter->asNtext($item->comment)
			]);
		}
	],
];