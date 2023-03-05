<?php

use yii\helpers\Html;
use kartik\grid\GridView;

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
            //'code',
	        [
		        'attribute'=>'name',
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/tech-types/item',['model'=>$data]);
		        }
	        ],
	        'techModelsCount',
	        'usages',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
