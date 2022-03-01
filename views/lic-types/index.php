<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\LicTypesSearch */

$renderer=$this;

$this->title = \app\models\LicTypes::$titles;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lic-types-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
	Тут должны быть описаны все используемые в организации схемы лицензирования. Геморная штука, иногда на один продукт приходится заводить несколько. А некоторые универсальные и можно использовать для нескольких продуктов.

	</p>
    <p>
        <?= Html::a('Новая', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
            [
				'attribute' => 'descr',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/lic-types/item', ['model' => $data]);
				}
			],
            'comment:ntext',
            //'name',
            //'created_at',

            //['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]); ?>
</div>
