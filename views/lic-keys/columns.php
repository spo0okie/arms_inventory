<?php

/* @var $this yii\web\View */

use app\components\ExpandableCardWidget;

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
		'attribute' => 'links',
		'format' => 'raw',
		'value' => function ($item) use ($renderer) {
			$output = '';
			foreach ($item->arms as $arm)
				$output .= ' ' . $renderer->render('/techs/item', ['model' => $arm,'icon'=>true,'static_view'=>true]);
			foreach ($item->comps as $comp)
				$output .= ' ' . $renderer->render('/comps/item', ['model' => $comp,'icon'=>true,'static_view'=>true]);
			foreach ($item->users as $user)
				$output .= ' ' . $renderer->render('/users/item', ['model' => $user,'icon'=>true,'static_view'=>true]);
			return $output;
		}
	],
	'comment',
];
