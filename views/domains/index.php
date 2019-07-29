<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Домены AD';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domains-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
    Домены AD должны быть перечислены, т.к. у нас пока гетерогенная среда. Теоретически тут должны наверно быть добавлены и рабочие группы,
    но пока мне непонятно как будет скрипт отрабатывать на компьютерах без домена.
    </p>

    <p>
        <?= Html::a('Добавить домен', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',
            'name',
            'fqdn',
            'comment',

            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]); ?>
</div>
