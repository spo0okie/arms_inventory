<?php

use app\components\ModelFieldWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

$users=$model->users;
if (!isset($static_view)) $static_view=false;
$deleteable=!count($users);
?>
<h1>
    <?= Html::encode($model->name) ?>
    <?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['user-groups/update','id'=>$model->id])) ?>
    <?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['user-groups/delete', 'id' => $model->id], [
        'data' => [
            'confirm' => 'Удалить эту группу? Это действие необратимо!',
            'method' => 'post',
        ],
    ]) ?>
</h1>

<?php if (strlen($model->ad_group)) { ?>
   <h4>
       Синхронизируется с группой AD: <?= ModelFieldWidget::renderFieldValue($model,'ad_group') ?>
   </h4>
    (<?= strlen($model->sync_time)?
        'последняя синхронизация '.ModelFieldWidget::renderFieldValue($model,'sync_time'):
        'синхронизация еще ни разу не производилась'
    ?>)
<?php } ?>

<?php if(!$static_view&&!$deleteable) { ?>
    <p>
        <span class="fas fa-warning-sign"></span> Невозможно в данный момент удалить эту группу, т.к. в ней присутствуют сотрудники.
    </p>
<?php } ?>
<br />

<p>
	<?= ModelFieldWidget::renderFieldValue($model,'description') ?>
</p>
<br />

<?= ModelFieldWidget::widget([
	'model'=>$model,
	'field'=>'users_ids',
	'label'=>'Участники:',
	'item_options'=>['static_view'=>$static_view],
]) ?>

<?php if (!$static_view) echo ModelFieldWidget::widget([
	'model'=>$model,
	'field'=>'notebook',
	'label'=>'Записная книжка:',
]) ?>
