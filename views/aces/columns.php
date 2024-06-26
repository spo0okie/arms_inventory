<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
$glue='<br/>';
return [
	//['class' => 'yii\grid\SerialColumn'],
	
	[
		'attribute'=>'subjects',
		'value'=>function($data) use ($glue) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$model=$data;
			/** @noinspection PhpUnusedLocalVariableInspection */
			$hasIp=false;
			/** @noinspection PhpUnusedLocalVariableInspection */
			$hasPhone=false;
			$items=include 'resources.php';
			return implode($glue,$items);
		}
	],
	[
		'attribute'=>'subject_nodes',
		'value'=>function($data) use ($glue){
			/** @noinspection PhpUnusedLocalVariableInspection */
			$model=$data;
			$items=include 'resource-nodes.php';
			return implode($glue,$items);
		}
	],
	[
		'attribute'=>'access_types',
		'value'=>function($data) use ($renderer,$glue){
			$items=[];
			foreach ($data->accessTypes as $type) {
				$params=$data->getIpParams()[$type->id]??null;
				$items[]=$renderer->render('/access-types/item',[
					'model'=>$type,
					'static_view'=>true,
					'suffix'=>$params?': '.$params:'',
				]);
			}
			return implode($glue,$items);
		}
	],
	[
		'attribute'=>'name',
		'value'=>function($data) use ($renderer){
			return $renderer->render('/aces/item',['model'=>$data,'static_view'=>false,'modal'=>true]);
		}
	],
	[
		'attribute'=>'resource',
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data->acl))
				return $renderer->render('/acls/item',['model'=>$data->acl,'static_view'=>false,'modal'=>true]);
			return '';
		}
	],
	[
		'attribute'=>'resource_nodes',
		'value'=>function($data) use ($renderer,$glue){
			if (is_object($data->acl))
				return implode($glue,$data->acl->renderNodes($renderer,[
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
