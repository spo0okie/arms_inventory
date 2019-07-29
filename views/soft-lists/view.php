<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */

$this->title = $model->descr;
$this->params['breadcrumbs'][] = ['label' => 'Списки ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="soft-lists-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Изменить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php /* Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) */?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            //'id',
            'name',
            'descr',
            'comment:ntext',
        ],
    ]) ?>

    <h3>Содержимое списка:</h3><p>
        <?php if (is_array($model->soft)&&count($model->soft)) { ?>
            <table class="table table-bordered table-striped">
            <?php
            $sortlist=[];
            foreach ($model->soft as $item) $sortlist[$item->fullDescr]=$item;
            ksort($sortlist);
            foreach ($sortlist as $item) { ?>
                <tr>
                    <td>
                        <?= \yii\helpers\Html::a($item->manufacturer->name, ['manufacturers/view', 'id' => $item->manufacturers_id]) ?>
                    </td>
                    <td>
	                    <?= \yii\helpers\Html::a(
		                    $item->descr,
		                    ['/soft/view', 'id' => $item->id],
		                    ['title' => 'Перейти к программному продукту']
	                    ) ?>
	                    <?= \yii\helpers\Html::a(
		                    '<span class="glyphicon glyphicon-pencil"></span>',
		                    ['/soft/update', 'id' => $item->id],
		                    ['class'=>'passport_tools','title'=>'Редактировать програмный продукт']
	                    ) ?>
                    </td>
                    <td>
                        <?= $item->comment ?>
                    </td>
                </tr>

            <?php } ?>
            </table>
        <?php } else { ?>
            Отсутствует
        <?php } ?>
    </p>

</div>
