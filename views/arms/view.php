<?php

use yii\helpers\Html;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */

\yii\helpers\Url::remember();
$this->title = $model->num;
$this->params['breadcrumbs'][] = ['label' => 'АРМы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$licGroups=$model->licGroups;
$licItems=$model->licItems;
$comps = $model->comps;
$contracts = $model->contracts;
$techs = $model->techs;

$deletable = !count ($licItems) && !count($licGroups) && !count($comps) && !count($contracts) && !count($techs);
?>
<div class="arms-view">
    <div class="arms-view-header">
	    <div class="row">
            <div class="col-md-2">
                <h3>
                    <?= Html::encode($this->title) ?>
                    <?= Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->id]) ?>
                    <?php if ($deletable) echo Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $model->id], [
	                    'class' => 'btn btn-danger',
	                    'data' => [
		                    'confirm' => 'Удалить этот АРМ? Операция не обратима!',
		                    'method' => 'post',
	                    ],
                    ]) ?>
                </h3>
	            <?= $this->render('att-comps',['model'=>$model]) ?>
            </div>

            <div class="col-md-5" >
                <?= $this->render('att-lics',['model'=>$model]) ?>
            </div>

            <div class="col-md-5" >
                <?= $this->render('att-contracts',['model'=>$model]) ?>
            </div>

        </div>
        <div class="row">
            <div class="col-md-6">
	            <?= $this->render('arm-status',['model'=>$model]) ?>
	            <?php if (!$deletable) { ?>
                    <p><span class="glyphicon glyphicon-warning-sign"></span> Нельзя удалить АРМ, к которму привязаны другие объекты. Для удаления сначала надо отвязать ОС, Лицензии и Документы</p>
	            <?php } ?>
            </div>
            <div class="col-md-6">
	            <?= $this->render('arm-history',['model'=>$model]) ?>
            </div>
        </div>

    </div>

    <?= $this->render('passport',compact('model')) ?>

</div>
