<?php


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
$glue='<br/>';
return [
	//['class' => 'yii\grid\SerialColumn'],
	
	[
		'attribute'=>'aces',
		'value'=>function($data) use ($glue,$renderer) {
			$items=[];
			foreach ($data->aces as $ace)
				$items[]=$renderer->render('/aces/card',['model'=>$ace]);
			return implode(' ',$items);
		}
	],
	[
		'attribute'=>'resource',
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data))
				return $renderer->render('/acls/item',['model'=>$data,'static_view'=>false,'modal'=>true]);
			return '';
		}
	],
	[
		'attribute'=>'resource_nodes',
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data))
				return implode($glue,$data->renderNodes($renderer,[
					'static_view'=>true,
					'show_ips'=>$data->hasIpAccess(),
					'ips_prefix'=>':<br>',
					'ips_glue'=>'<br>',
					'ips_options'=>['class'=>'ms-2','static_view'=>true]
				]));
			return '';
		}
	],

	//['class' => 'yii\grid\ActionColumn'],
];
