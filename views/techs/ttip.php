<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
?>
<div class="tech-models-ttip ttip-card">

	<h1><?= Html::a($model->num,['/techs/view','id'=>$model->id]) ?></h1>
	<?= $this->render('/tech-models/item',['model'=>$model->model]) ?>
	<p>
		<?= strlen($model->comment)?(Yii::$app->formatter->asNtext($model->comment).'<br />'):'' ?>
		<?= \app\components\UrlListWidget::Widget(['list'=>$model->url]) ?>
	</p>


    <h4>Идентификаторы:</h4>
    <p>
        Бухг. инв. №: <?= $model->inv_num ?> <br />
        Серийный №: <?= $model->sn ?>
    </p>

    <h4>Место установки и сотрудники:</h4>
    <p>
        АРМ: <?= $this->render('/arms/item',['model'=>$model->arm]) ?> <br />
        Помещение: <?= $this->render('/places/item',['model'=>$model->place]) ?> <br />
        Пользователь: <?= $this->render('/users/item',['model'=>$model->user]) ?> <br />
        Сотрудник ИТ: <?= $this->render('/users/item',['model'=>$model->itStaff]) ?> <br />
    </p>

    <h4>Сеть:</h4>
    <p>
        IP: <?= Yii::$app->formatter->asNtext($model->ip) ?>
        MAC: <?= Yii::$app->formatter->asNtext($model->mac) ?>
    </p>

    <h4>документы:</h4>
    <p>

		<?php if(is_array($contracts = $model->contracts) && count($contracts)) foreach ($contracts as $contract) {
			echo $this->render('/contracts/item',['model'=>$contract]).'<br />';
		} else { ?>
            отсутствуют<br />
		<?php } ?>
    </p>
</div>