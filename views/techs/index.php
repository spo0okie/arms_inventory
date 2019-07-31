<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;
$this->title = \app\models\Techs::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="techs-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
	        [
		        'attribute'=>'num',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){
			        return $renderer->render('/techs/item',['model'=>$data]);
		        }
	        ],
	        [
		        'attribute'=>'model',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){
			        return is_object($data->model)?$renderer->render('/tech-models/item',['model'=>$data->model,'long'=>true]):null;
		        }
	        ],
	        'sn',
            'inv_num',
            //'arms_id',
	        [
		        'attribute'=>'place',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){
			        return $renderer->render('/places/item',['model'=>$data->effectivePlace,'full'=>true]);
		        }
	        ],
            //'user_id',
            //'it_staff_id',
            //'comment',
	        'ip',
	        //'url:ntext',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
