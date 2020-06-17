<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;
$comps=$model->comps;
$techs=$model->techs;
$services=$model->depends;
$dependants=$model->dependants;
$support=$model->support;
$deleteable=!count($comps)&&!count($services)&&!count($dependants)&&!count($support)&&!count($techs);
?>

<h1>
    <?= Html::encode($model->name) ?>
    <?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['services/update','id'=>$model->id])) ?>
    <?php if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['services/delete', 'id' => $model->id], [
	    'data' => [
		    'confirm' => 'Удалить этот сервис? Это действие необратимо!',
		    'method' => 'post',
	    ],
    ]); else { ?>
		<span class="small">
			<span class="glyphicon glyphicon-lock" title="Невозможно в данный момент удалить этот сервис, т.к. присутствуют привязанные объекты: привязанные пользователи, компьютеры или другие сервисы."></span>
		</span>
	<?php } ?>
</h1>
<h4>
    (<?php
		echo $model->is_end_user?'Предоставляется пользователям':'Внутренний сервис';
		if (is_object($model->segment)) echo " // Сегмент ИТ: {$model->segment->name}"
	?>)
</h4>
<?php
$schedules=[];
if (!empty($model->providingSchedule))
	$schedules[]='<strong>Предоставляется:</strong> '.$model->providingSchedule->name;

if (!empty($model->supportSchedule))
	$schedules[]='<strong>Поддерживается:</strong> '.$model->supportSchedule->name;

if (count($schedules)) {
	echo implode('; ',$schedules).'<br />';
}
?>

<?php if(!$static_view&&!$deleteable) { ?>
    <p>
    
    </p>
<?php } ?>
<br />
<p>
	<?= Yii::$app->formatter->asNtext($model->description) ?>
</p>
<?= \app\components\UrlListWidget::Widget(['list'=>$model->links]) ?>
<br />

<h4>
	Ответственный: <?= $this->render('/users/item',['model'=>$model->responsible,'static_view'=>$static_view]) ?>
</h4>
<?php if (count($support)) { ?>
	При поддержке:
    <p>
    <?php
        foreach ($support as $user)
            echo $this->render('/users/item',['model'=>$user,'static_view'=>$static_view]).'<br />';
    ?>
    </p>
    <br />
<?php } ?>

<?php if (count($comps)) { ?>
	<h4>Выполняется на компьютерах:</h4>
	<p>
		<?php
		foreach ($comps as $comp)
			echo $this->render('/comps/item',['model'=>$comp,'static_view'=>$static_view]).'<br />';
		?>
	</p>
	<br />
<?php } ?>

<?php if (count($techs)) { ?>
	<h4>Выполняется на оборудовании:</h4>
	<p>
		<?php
		foreach ($techs as $tech)
			echo $this->render('/techs/item',['model'=>$tech,'static_view'=>$static_view]).'<br />';
		?>
	</p>
	<br />
<?php } ?>

<?php if (count($services)) { ?>
    <h4>Зависит от сервисов:</h4>
    <p>
		<?php
		foreach ($services as $service)
			echo $this->render('/services/item',['model'=>$service,'static_view'=>$static_view]).'<br />';
		?>
    </p>
    <br />
<?php } ?>

<?php if (count($dependants)) { ?>
    <h4>Зависимые сервисы:</h4>
    <p>
		<?php
		foreach ($dependants as $service)
			echo $this->render('/services/item',['model'=>$service,'static_view'=>$static_view]).'<br />';
		?>
    </p>
    <br />
<?php } ?>

<?php if (!$static_view && strlen($model->notebook)) { ?>
    <h4>Записная книжка:</h4>
    <p>
		<?= Yii::$app->formatter->asNtext($model->notebook) ?>
    </p>
    <br />
<?php } ?>

