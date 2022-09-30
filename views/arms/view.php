<?php

use yii\helpers\Html;
use yii\bootstrap5\Modal;

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
                    <?= Html::a('<span class="fas fa-pencil-alt"></span>', ['update', 'id' => $model->id]) ?>
                    <?php if ($deletable) echo Html::a('<span class="fas fa-trash"></span>', ['delete', 'id' => $model->id], [
	                    'class' => 'btn btn-danger',
	                    'data' => [
		                    'confirm' => 'Удалить этот АРМ? Операция не обратима!',
		                    'method' => 'post',
	                    ],
                    ]); else { ?>
						<span class="small">
							<span class="fas fa-lock" title="Нельзя удалить АРМ, к которму привязаны другие объекты. Для удаления сначала надо отвязать ОС, Лицензии и Документы."></span>
						</span>
					<?php } ?>
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
            <div class="col-md-1">
		        <?= $this->render('arm-status',['model'=>$model]) ?>
            </div>
            <div class="col-md-2">
                <h4>Помещение</h4>
		        <?= $this->render('/places/item',['model'=>$model->place,'full'=>true]) ?>
            </div>
            <div class="col-md-4">
                <h4>Материалы и ЗиП</h4>
                <?php
				$materialsUsages=$model->materialsUsages;
				\app\helpers\ArrayHelper::multisort($materialsUsages,'date',SORT_DESC);
				foreach($materialsUsages as $usage)
					echo $this->render('/materials-usages/item',['model'=>$usage,'material'=>true,'count'=>true,'cost'=>true,'date'=>true]).'<br />';
				?>
            </div>
            <div class="col-md-5">
	            <?= $this->render('arm-history',['model'=>$model]) ?>
            </div>
        </div>

    </div>

    <?= $this->render('passport',compact('model')) ?>

</div>
