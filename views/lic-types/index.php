<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Типы лицензирования';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lic-types-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
	Тут должны быть описаны все используемые в организации типы лицензий, т.к. у любой внесенной в БД лицензии
	должен быть явно указан ее тип. 

	</p>
    <p>
        <?= Html::a('Добавить тип', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',
            'descr',
            'comment',
            'name',
            //'created_at',

            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]); ?>
</div>
