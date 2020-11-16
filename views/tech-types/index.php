<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\TechTypes::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="tech-types-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Новый тип', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
	        [
		        'attribute'=>'name',
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/tech-types/item',['model'=>$data]);
		        }
	        ],
			'code',
	        'techModelsCount',
	        'usages',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
