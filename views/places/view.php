<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $models app\models\Places[] */

\yii\helpers\Url::remember();
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Places::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$arms_cnt=count($model->arms);
$techs_cnt=count($model->techs);
$phones_cnt=count($model->phones);
$inets_cnt=count($model->inets);
$deleteable=!($arms_cnt||$techs_cnt||$phones_cnt||$inets_cnt);
?>
<div class="places-view">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= Html::a('<span class="glyphicon glyphicon-pencil">', ['update', 'id' => $model->id]) ?>
        <?= $deleteable?Html::a('<span class="glyphicon glyphicon-trash">', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Are you sure you want to delete this item?',
		        'method' => 'post',
	        ],
        ]):'' ?>
    </h1>

    <?= $this->render('hdr_create_obj',['places_id'=>$model->id]) ?>
	<?= $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0]) ?>
</div>
