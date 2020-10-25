<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */
$static_view=true;
?>

<div class="arms-ttip ttip-card">
    <h4>Компьютер:</h4>
    Модель:<?= $this->render('/tech-models/item',['model'=>$model->techModel]) ?><br />
    Серийный номер:<?= $model->sn ?><br/>
    Инвентарный номер:<?= $model->inv_num ?><br />
    <br />
	
	<?php if ($model->techModel->individual_specs) { ?>
	<h4>Спецификация:</h4>
		<?= \Yii::$app->formatter->asNtext($model->specs) ?>
		<br />
	<?php } ?>

    <?= $this->render('att-comps',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <h4>Сотрудники:</h4>
    Пользователь:<?= is_object($model->user)?$this->render('/users/item',['model'=>$model->user]):'-не назначен-' ?><br/>
	<?= is_object($model->head)?('Руководитель отдела:'.$this->render('/users/item',['model'=>$model->head]).'<br/>'):'' ?>
	<?= is_object($model->itStaff)?('Сотрудник ИТ:'.$this->render('/users/item',['model'=>$model->itStaff]).'<br/>'):'' ?>
	<?= is_object($model->responsible)?('Ответственный:'.$this->render('/users/item',['model'=>$model->responsible]).'<br/>'):'' ?>
    <br />


	<?= $this->render('arm-status',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('att-techs',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('att-lics',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('att-contracts',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('arm-history',['model'=>$model,'static_view'=>$static_view]) ?>
</div>