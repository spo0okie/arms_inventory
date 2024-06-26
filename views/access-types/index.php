<?php

use app\models\AccessTypes;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
Url::remember();

$this->title = AccessTypes::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="access-types-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
			[
				'attribute'=>'name',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('item',['model'=>$data]);
				}
			],
			'code',
            'comment:ntext',
            'notepad:ntext',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
