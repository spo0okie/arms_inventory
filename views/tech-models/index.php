<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechModelsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\TechModels::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="tech-models-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить модель', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute'=>'type',
                'format'=>'raw',
                'value'=>function($data) use ($renderer) {
                    return $renderer->render('/tech-types/item',['model'=>$data['type']]);
                }
            ],
	        [
		        'attribute'=>'name',
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/tech-models/item',['model'=>$data,'hideUndeletable'=>true]);
		        }
	        ],
	        //'comment:ntext',
	        'usages',
        ],
    ]); ?>
</div>
