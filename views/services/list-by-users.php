<?php

use app\components\AttributeHintWidget;
use app\components\DynaGridWidget;
use app\helpers\ArrayHelper;
use app\models\Services;
use app\models\Users;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models Services[] */
/* @var $disabled_ids array */

Url::remember();

$this->title = 'Распределение по сотрудникам';
$this->params['breadcrumbs'][] = ['label' => Services::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$models=$dataProvider->models;

$renderer=$this;

$render_columns=[
	[
		'attribute' => 'name',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/services/item', ['model' => $data,'noDelete'=>true]);
		},
	],
	[
		'attribute' => 'support',
		'header' => AttributeHintWidget::widget([
			'label'=>'#',
			'hint'=>'Количество сотрудников сопровождающих сервис'
		]),
		'format' => 'raw',
		'value' => function ($data) use ($disabled_ids) {
			/* @var $data Services */
			return count($data->supportModeling($disabled_ids));
		},
		'contentOptions' => function ($data) use ($disabled_ids) {
			switch (count($data->supportModeling($disabled_ids))) {
				case 0:
					return ['class' => ['col_serviceSum','status_Alert']];
				case 1:
					return ['class' => ['col_serviceSum','status_Warning']];
				default:
					return ['class' => ['col_serviceSum','status_OK']];
			}
		},
	],
];

$usersFilter=[];
$users=[];
//собираем Какие пользователи сколько раз встречаются в сервисах
foreach ($dataProvider->models as $model) {
	/** @var $model Services */
	foreach (array_merge([$model->responsibleRecursive],$model->supportRecursive) as $user) if (is_object($user)) {
		$usersFilter[$user->id]=$user->Ename;
		$user_id=$user->id;
		if (isset($users[$user_id]))
			$users[$user_id]++;
		else
			$users[$user_id]=1;
	}
}

arsort($users);
asort($usersFilter,SORT_STRING);

//если отфильтровали по пользователям, то из рендера убираем посторонних
if (is_array($searchModel->responsible_ids) && count($searchModel->responsible_ids)) {
	foreach ($users as $id=>$user) {
		if (array_search($id,$searchModel->responsible_ids)===false) unset($users[$id]);
	}
}

foreach ($users as $user=>$total) {
	
	if (!empty($user))	{
		$objUser= Users::findIdentity($user);
		$render_columns[]=[
			'attribute' => $user,
			'header' => $objUser->shortName,
			//.'<br>'
			'filter'=>Html::a(
				array_search($user,$disabled_ids)===false?'Отключить':'Восстановить',
					Url::currentNonRecursive([
							'disabled_ids'=> ArrayHelper::itemToggle($disabled_ids,(string)$user)
					]),
					[
						'class'=>'btn '.(array_search($user,$disabled_ids)===false?'btn-warning':'btn-success'),
						'qtip_ttip'=>'Смоделировать отсутствие сотрудника'
					]),
			'format' => 'raw',
			'value' => function ($data) use ($renderer,$user) {
				/** @var $data Services */
				if ($data->responsible_id == $user) return 'Ответственный';
				if (in_array($user,ArrayHelper::getArrayField($data->support,'id'))) return 'Поддержка';
				return '';
			},
			'footer'=>$total,
			'contentOptions' => function ($data) use ($user,$disabled_ids) {
				if ($data->responsible_id == $user) return ['class' => [
					'col_serviceUser',
					array_search($user,$disabled_ids)===false?'col_responsible':'col_missing'
				]];
				if (in_array($user,ArrayHelper::getArrayField($data->support,'id'))) return ['class' => [
					'col_serviceUser',
					array_search($user,$disabled_ids)===false?'col_support':'col_missing'
				]];
				return ['col_serviceUser'];
			},
			
		];
	}
}


echo '<div class="services-index">';

echo DynaGridWidget::widget([
	'id'=>'index-by-users',
	'dataProvider' => $dataProvider,
	'header'=>$this->title,
	'createButton'=> '<div class="d-flex flex-row flex-nowrap">'
		. '<div>'
			. Html::a('Новый сервис', ['create'], ['class' => 'btn btn-success'])
		. '</div>'
		. '<div class="align-self-stretch row flex-nowrap flex-fill">'
			. $this->render('_search', [
				'model' => $searchModel,
				'action'=>'index-by-users',
				'userFilter'=>$usersFilter
			])
		. '</div>'
	. '</div>',
	'filterModel' => $searchModel,
	'columns' => $render_columns,
	'resizableColumns' => false,
	'gridOptions' => [
		'showFooter' => true,
		'floatHeader'=>true,
		'floatHeaderOptions'=>['top'=>'0'],
	],
]);

echo '</div>';
