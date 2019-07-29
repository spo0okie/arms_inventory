<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

$users=$model->users;
$services=$model->services;
if (!isset($static_view)) $static_view=false;
$deleteable=!count($users)&&!count($services);
?>
<h1>
    <?= Html::encode($model->name) ?>
    <?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['user-groups/update','id'=>$model->id])) ?>
    <?php if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['user-groups/delete', 'id' => $model->id], [
        'data' => [
            'confirm' => 'Удалить эту группу? Это действие необратимо!',
            'method' => 'post',
        ],
    ]) ?>
</h1>

<?php if (strlen($model->ad_group)) { ?>
   <h4>
       Синхронизируеется с группой AD: <?= $model->ad_group ?>
   </h4>
    (<?= strlen($model->sync_time)?
        'последняя синхронизация '.Yii::$app->formatter->asDatetime($model->sync_time):
        'синхронизация еще ни разу не производилась'
    ?>)
<?php } ?>

<?php if(!$static_view&&!$deleteable) { ?>
    <p>
        <span class="glyphicon glyphicon-warning-sign"></span> Невозможно в данный момент удалить эту группу, т.к. в ней присутствуют сотрудники или привязаны сервисы.
    </p>
<?php } ?>
<br />

<p>
	<?= Yii::$app->formatter->asNtext($model->description) ?>
</p>
<br />

<?php if (count($users)) { ?>
    <h4>
        Участники:
    </h4>
    <p>
		<?php
		foreach ($users as $user)
			echo $this->render('/users/item',['model'=>$user,'static_view'=>$static_view]).'<br />';
		?>
    </p>
    <br />
<?php } ?>

<?php if (count($services)) { ?>
    <h4>Отвечает за работоспособность сервисов:</h4>
    <p>
		<?php
		foreach ($services as $service)
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

