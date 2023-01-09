<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;

if (
	!empty($searchModel->type_id)
	&&
	(is_object($type=\app\models\TechTypes::findOne($searchModel->type_id)))
) {
	$comment=$type->comment_name;
} else {
	$comment=$searchModel->attributeLabels()['comment'];
}


return [
	'num'=> [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/item', ['model' => $data]);
		}
	],
	'model' => [
		'value' => function ($data) use ($renderer) {
			return is_object($data->model) ? $renderer->render('/tech-models/item', ['model' => $data->model, 'long' => true]) : null;
		}
	],
	'place' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/places/item', ['model' => $data->effectivePlace, 'full' => true]);
		}
	],
	'userDep' => [
		'value' => function ($data) {
			return (is_object($data->user) && is_object($data->user->orgStruct)) ? $data->user->orgStruct->name:null;
		},
	],
	'departments_id' => [
		'value' => function ($data) {
			return (is_object($data->effectiveDepartment)) ? $data->effectiveDepartment->name:null;
		},
	],
	'user' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/users/item', ['model' => $data->user]);
		}
	],
	'state'=>[
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/tech-states/item', ['model' => $data->state]);
		}
	],
	'attach'=>[
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/att-contracts', ['model' => $data]);
		}
	],
	'sn',
	'inv_num'=> [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/techs/sn', ['model' => $data]);
		}
	],
	'comment'=> [
		'header'=> $comment,
		'format' => 'ntext',
		'value' => function ($data) use ($searchModel){return $data->comment.' '.$searchModel->model_id;}
	],
	'ip' => [
		'value' => function ($data) use ($renderer) {
			if (is_object($data)) {
				$output=[];
				foreach ($data->netIps as $ip)
					$output[]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true]);
				return implode(' ',$output);
			}
			return null;
		},
		'contentOptions'=>['class'=>'ip_col']
	],
	'mac',
];