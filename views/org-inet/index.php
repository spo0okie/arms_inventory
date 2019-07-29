<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\OrgInet::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-inet-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
	        'places.name:raw:Объект',
	        //'static',
            'ip_addr',
            //'ip_gw',
            //'ip_dns1',
            //'ip_dns2',
            //'type',
            //'static',
            'provTel.name:raw:Оператор связи',
	        'account',
	        'comment:ntext',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
