<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\Services[] */

$this->title = \app\models\Services::$title;
$this->params['breadcrumbs'][] = $this->title;
$models=$dataProvider->models;

$renderer=$this;
?>
<div class="services-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Новый сервис', ['create'], ['class' => 'btn btn-success']) ?>
    </p>


<?php
$render_columns=[
	[
		'attribute' => 'name',
		//'header' => 'Инв. номер',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/services/item', ['model' => $data]);
		},
		//'contentOptions' => ['class' => $column . '_col']
	],
	[
		'attribute' => 'support',
		'header' => '#',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return empty($data->responsible_id)?0:1+count($data->support_ids);
		},
		'contentOptions' => function ($data) use ($renderer) {
			switch (empty($data->responsible_id)?0:1+count($data->support_ids)) {
				case 0:
					return ['class' => ['col_serviceSum','status_Alert']];
					break;
				case 1:
					return ['class' => ['col_serviceSum','status_Warning']];
					break;
				default:
					return ['class' => ['col_serviceSum','status_OK']];
					break;
			};
		},
	],
];

$users=[];
//собираем Какие пользователи сколько раз встречаются в сервисах
foreach ($dataProvider->models as $model) {
	foreach (array_merge([$model->responsible_id],$model->support_ids) as $user_id) {
		if (isset($users[$user_id])) {
			$users[$user_id]++;
		} else {
			$users[$user_id]=1;
		}
	}
}

arsort($users);

foreach ($users as $user=>$total) {
	
	if (!empty($user))	{
		$objUser=\app\models\Users::findIdentity($user);
		$render_columns[]=[
			'attribute' => $user,
			'header' => $objUser->shortName,
			'format' => 'raw',
			'value' => function ($data) use ($renderer,$user) {
				if ($data->responsible_id == $user) return 'Ответственный';
				if (in_array($user,$data->support_ids)) return 'Поддержка';
				return '';
			},
			'footer'=>$total,
			'contentOptions' => function ($data) use ($user) {
				if ($data->responsible_id == $user) return ['class' => ['col_serviceUser','col_responsible']];
				if (in_array($user,$data->support_ids)) return ['class' => ['col_serviceUser','col_support']];
				return ['col_serviceUser'];
			}
			
		];
	}
}

echo GridView::widget([
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	'columns' => $render_columns,
	'showFooter' => true,
	'floatHeader'=>true,
	'floatHeaderOptions'=>['top'=>'50']
]);
?>

</div>
