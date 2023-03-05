<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Списки ПО';
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
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
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
            //'name',
			[
				'attribute'=>'descr',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('item',['model'=>$data]);
				}
			],
            'comment:ntext',

            //['class' => 'yii\grid\ActionColumn','template' => '{view} {update}'],
        ],
    ]); ?>
</div>
