<?php
/**
 * Элемент пользователей
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var Users $model */

use app\components\LinkObjectWidget;
use app\models\Users;

if (!isset($icon)) $icon=false;
if (!isset($static_view)) $static_view=true;
if (!isset($noDelete)) $noDelete=false;

if (is_object($model)) {
	if (!isset($name)) {
		if (isset($short))
			$name=$model->shortName;
		else
			$name=$model->Ename;
	}
	
	if ($icon) $name='<span class="fas fa-user small"></span>'.$name;
?>

<span class="users-item object-item <?= $model->Uvolen?'uvolen':'' ?>">
	
	<?= LinkObjectWidget::widget(['model'=>$model,'name'=>$name,'static'=>$static_view,'noDelete'=>$noDelete]) ?>
</span>

<?php } else echo "Отсутствует";