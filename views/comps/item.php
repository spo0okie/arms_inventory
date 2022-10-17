<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($static_view)) $static_view=true;
if (!isset($fqdn)) $fqdn=false;
if (!isset($icon)) $icon=false;

if (is_object($model)) {
	if (!isset($name)) $name=$model->renderName($fqdn);
	if ($icon) {
		if ($model->isWindows) $name='<span class="fab fa-windows"></span>'.$name;
		elseif ($model->isLinux) $name='<span class="fab fa-linux"></span>'.$name;
		//else $name='<span class="far fa-meh-blank"></span>'.$name;
	}
	
	
	?>
	
<span class="comps-item object-item <?= $model->archived?'text-muted text-decoration-line-through':'' ?>">
    <?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'modal'=>true,
		'noDelete'=>true,
	]) ?>
	</span>
<?php } else echo "Отсутствует";