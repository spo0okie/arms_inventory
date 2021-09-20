<?php

use yii\helpers\Html;
//use kartik\tabs\TabsX;
use yii\bootstrap5\Tabs;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */
/* @var $techsSearchModel /app/models/TechsSearch */
/* @var $techsDataProvider \yii\data\ActiveDataProvider */
/* @var $armsSearchModel /app/models/ArmsSearch */
/* @var $armsDataProvider \yii\data\ActiveDataProvider */

\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$techModels=$model->techModels;

$tabs = [
	[
		'label'=>'Список моделей',
		'linkOptions'=>['id'=>'models'],
		'content'=>$this->render('list-models',['model'=>$model,'techModels'=>$techModels]),
	],
];

if (\app\models\TechTypes::isPC($model->id)) {
	$tabs[] = [
		'label'=>'Экземпляры рабочих мест',
		'linkOptions'=>['id'=>'items'],
		'content'=>$this->render('/arms/table', [
			'searchModel' => $armsSearchModel,
			'dataProvider' => $armsDataProvider,
		]),
		'active'=>is_array(Yii::$app->request->get('ArmsSearch'))||Yii::$app->request->get('page')||Yii::$app->request->get('sort')
	];
} else {
	$tabs[] = [
		'label'=>'Экземпляры оборудования',
		'linkOptions'=>['id'=>'items'],
		'content'=>$this->render('/techs/table', [
			'searchModel' => $techsSearchModel,
			'dataProvider' => $techsDataProvider,
			'columns' => ['attach', 'num', 'model', 'mac', 'ip', 'state', 'user', 'place', 'inv_num', 'comment'],
		]),
		'active'=>is_array(Yii::$app->request->get('TechsSearch'))||Yii::$app->request->get('page')||Yii::$app->request->get('sort')
	];
}


?>
<div class="tech-types-view">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= Html::a('<span class="fas fa-pencil-alt"></span>', ['update', 'id' => $model->id]) ?>
        <?= !count($techModels)?Html::a('<span class="fas fa-trash"></span>', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Удалить этот тип оборудования?',
		        'method' => 'post',
	        ],
        ]):'' ?>
    </h1>

    <?php if (count($techModels)) { ?>
        <p>
            <span class="fas fa-exclamation-triangle"></span> Невозможно удалить этот тип оборудования, т.к. заведены модели оборудования этого типа. (см ниже)
        </p>
    <?php } ?>
	
	<?= Tabs::widget([
		'items'=>$tabs,
		//'position'=>TabsX::POS_ABOVE,
		'encodeLabels'=>false,
		//'fade'=>false,
		//'dropDownClass' => 'kartik\bs5dropdown\Dropdown',
	]); ?>

</div>