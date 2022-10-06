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
if (!isset($static_view)) $static_view=true;

if (is_object($model)) {
	if (!isset($name)) {
		if (isset($short))
			$name=$model->shortName;
		else
			$name=$model->Ename;
	}
	
	if ($icon) $title='<span class="fas fa-user"></span>'.$name;
?>

<span class="users-item object-item <?= $model->Uvolen?'uvolen':'' ?>">
	<?= \app\components\LinkObjectWidget::widget(['model'=>$model,'name'=>$name,'static'=>$static_view]) ?>
</span>

<?php } else echo "Отсутствует";