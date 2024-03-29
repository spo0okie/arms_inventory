<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
$static_view=true;
?>

<div class="arms-ttip ttip-card">
    <h4>Компьютер:</h4>
    Модель:<?= $this->render('/tech-models/item',['model'=>$model->model]) ?><br />
    Серийный номер:<?= $model->sn ?><br/>
    Инвентарный номер:<?= $model->inv_num ?><br />
    <br />
	
	<?php if (is_object($model->model) && $model->model->individual_specs) { ?>
	<h4>Спецификация:</h4>
		<?= \Yii::$app->formatter->asNtext($model->specs) ?>
		<br />
	<?php } ?>

    <?= $this->render('attaches/comps',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <h4>Сотрудники и помещение:</h4>
	Помещение: <?= $this->render('/places/item',['model'=>$model->place]) ?> <br />
	Пользователь:<?= is_object($model->user)?$this->render('/users/item',['model'=>$model->user]):'-не назначен-' ?><br/>
	<?= is_object($model->head)?('Руководитель отдела:'.$this->render('/users/item',['model'=>$model->head]).'<br/>'):'' ?>
	<?= is_object($model->itStaff)?('Сотрудник ИТ:'.$this->render('/users/item',['model'=>$model->itStaff]).'<br/>'):'' ?>
	<?= is_object($model->admResponsible)?($model->getAttributeLabel('responsible_id').':'.$this->render('/users/item',['model'=>$model->admResponsible]).'<br/>'):'' ?>
    <br />


	<?= $this->render('arm-status',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('attached/techs',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('attached/lics',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('attached/contracts',['model'=>$model,'static_view'=>$static_view]) ?>
    <br />

    <?= $this->render('arm-history',['model'=>$model,'static_view'=>$static_view]) ?>
</div>