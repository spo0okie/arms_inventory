<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Списки ПО';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="soft-lists-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        Список со служебным именем <b>soft_ignore</b> будет использоваться как список игнорируемого ПО.<br />
        Список со служебным именем <b>soft_agreed</b> будет использоваться как список согласованного ПО.<br />
    </p>
    <p>
        <?= Html::a('Добавить список', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',
            'name',
            'descr',
            'comment:ntext',

            ['class' => 'yii\grid\ActionColumn','template' => '{view} {update}'],
        ],
    ]); ?>
</div>
