<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

$this->title = $model->manufacturer->name.' '.$model->descr;
$this->params['breadcrumbs'][] = ['label' => 'ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="soft-view">

    <h1>
	    <?= $this->render('/manufacturers/item',['model'=>$model->manufacturer]) ?>
        <?= $model->descr ?>
	    <?= Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->id]) ?>
	    <?= Html::a('<span class="glyphicon glyphicon-trash"></span>',
            ['delete', 'id' => $model->id], [
		    'data' => [
			    'confirm' => 'Are you sure you want to delete this item?',
			    'method' => 'post',
		    ],
	    ]) ?>
    </h1>


    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            //'id',
            [
                'label' => 'Разработчик',
                'attribute'=>'manufacturer.name',
            ],
            'descr',
            'comment',
			'items:ntext',
			'additional:ntext',
            //'created_at',
        ],
    ]) ?>

    <h3>Членство в списках ПО</h3><p>
    <?php if (is_array($model->softLists)&&count($model->softLists)) foreach ($model->softLists as $item) { ?>
        <?= \yii\helpers\Html::a($item->descr,['soft-lists/view','id'=>$item->id]) ?><br/>

    <?php } else { ?>
        Отсутствуют
    <?php } ?>
    </p>
</div>
