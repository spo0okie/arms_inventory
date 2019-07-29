<?php
/**
 * Элемент пользователей
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\Users $model */

use yii\helpers\Html;

if (is_object($model)) {
?>

<span class="users-item<?= $model->Uvolen?' uvolen':'' ?>"
      qtip_ajxhrf="<?= \yii\helpers\Url::to(['/users/ttip','id'=>$model->id])?>"
>
	<?= Html::a($model->Ename,['users/view','id'=>$model->id]) ?>
</span>

<?php } else echo "Отсутствует";