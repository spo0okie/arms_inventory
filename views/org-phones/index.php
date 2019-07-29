<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\OrgPhones::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-phones-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            //'id',
            'fullNum',
	        'comment',
	        'place.fullName',
	        'provTel.name',
	        'account',
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
