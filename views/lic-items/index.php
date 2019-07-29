<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LicItemsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\LicItems::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="lic-items-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [


            [
                'attribute'=>'lic_group_id',
                'format'=>'raw',
                'value'=>function($item) use ($renderer){
                    return $renderer->render('/lic-groups/item',['model'=>$item->licGroup]);
                }
            ],
            [
                'attribute'=>'descr',
                'format'=>'raw',
	            'value'=>function($item) use ($renderer){
		            return $renderer->render('item',['model'=>$item]);
	            }
            ],
            //'count',
            'comment',
            'status'
        ],
    ]); ?>
</div>
