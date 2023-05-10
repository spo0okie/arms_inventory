<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$this->title = 'Программное обеспечение';
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
$manufacturers=\app\models\Manufacturers::fetchNames();
?>
<div class="soft-index">

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
			/*[
                'attribute'=>'manufacturers_id',
                'format'=>'raw',
                'value' => function($data) use ($manufacturers) {return isset($manufacturers[$data['manufacturers_id']])?$manufacturers[$data['manufacturers_id']]:'производитель не найден';}
            ],
            [
	            'attribute' => 'manufacturers_id',
	            'value' => 'manufacturer.name',
                'filter' => \app\models\Manufacturers::fetchNames(),
            ],*/
			[
				'attribute'=>'descr',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('/soft/item',[
						'model'=>$data,
						'name'=>(is_object($data->manufacturer)?$data->manufacturer->name.' ':'').$data->descr
					]);
				}
			],
            'comment',
            //'items:ntext',
            //'created_at',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
