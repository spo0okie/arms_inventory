<?php

/* @var $this yii\web\View */

$renderer=$this;
return [
	[
		'attribute' => 'lic_item',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return  $renderer->render('/lic-items/item', ['model' => $data->licItem]);
		}
	],
	[
		'attribute' => 'key_text',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return  $renderer->render('/lic-keys/item', ['model' => $data]);
		}
	],
	[
		'attribute' => 'arms_ids',
		'format' => 'raw',
		'value' => function ($item) use ($renderer) {
			$output = '';
			foreach ($item->arms as $arm)
				$output .= ' ' . $renderer->render('/techs/item', ['model' => $arm]);
			return $output;
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
