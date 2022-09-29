<?php
/**
 * Элемент пользователей
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\Users $model */

use yii\helpers\Html;
if (!isset($icon)) $icon=false;

if (is_object($model)) {
	if (!isset($name)) {
		if (isset($short))
			$name=$model->shortName;
		else
			$name=$model->Ename;
	}
	
	if ($icon) $title='<span class="fas fa-user"></span>'.$title;
?>

<span class="users-item object-item <?= $model->Uvolen?'uvolen':'' ?>"
      qtip_ajxhrf="<?= \yii\helpers\Url::to(['/users/ttip','id'=>$model->id])?>"
>
	<?= Html::a($name,['users/view','id'=>$model->id]) ?>
</span>

<?php } else echo "Отсутствует";