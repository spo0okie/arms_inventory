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
	<?= $model->id ?>
    -
	<?= $model->Uvolen?'Уволен':'Работает' ?>
    <p>
		<?= $model->Doljnost ?>
        <br />
		<?= is_object($model->orgStruct)?$model->orgStruct->name:'- отдел не указан -' ?>
    </p>

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