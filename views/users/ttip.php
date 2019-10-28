<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
?>
<div class="users-ttip ttip-card">

    <h1>
	    <?= Html::a($model->Ename,['/users/view', 'id' => $model->id]) ?>
    </h1>
    Табельный №
	<?= $model->employee_id ?> (<?= $model->Persg ?>)
    -
	<?php
	if ($model->Uvolen) {
		if (strlen($model->resign_date))
			echo 'Уволен с '.$model->resign_date;
		else
			echo 'Уволен';
	} else {
		if (strlen($model->employ_date))
			echo 'Работает с '.$model->employ_date;
		else
			echo 'Работает';
	};
	?>
    <p>
		<?= $model->org->name ?>
        //
		<?= $model->Doljnost ?>
        <br />
		<?= (is_object($model->orgStruct))?$model->orgStruct->name:'- отдел не найден -' ?>
    </p>

	<?php if (count($model->arms)) { ?>
        Пользователь АРМ:
		<?php foreach ($model->arms as $arm) echo $this->render('/arms/item',['model'=>$arm]) ?>
        <br />
	<?php } ?>

    <br />

    <h4>Логин в AD</h4>
    <p>
		<?= $model->Login ?>
    </p>

    <br />

    <h4>E-Mail</h4>
    <p>
		<?= Yii::$app->formatter->asEmail($model->Email) ?>
    </p>

    <br />

    <h4>Телефоны</h4>
    <p>
        Внутренний: <?= $model->Phone ?><br />
        Сотовый: <?= $model->Mobile ?><br />
        Городской: <?= $model->work_phone ?><br />
    </p>

    <br />

    <h4>Входы на ПК</h4>
	<?php foreach ($model->lastThreeLogins as $logon) { ?>
		<?= $this->render('/login-journal/item-comp',['model'=>$logon]); ?> <br />
	<?php } ?>
</div>