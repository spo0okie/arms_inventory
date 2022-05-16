<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

if (!isset($static_view)) $static_view=false;
if (is_object($model)) {
?>

<h4>
	<?= \app\models\LicTypes::$title ?>:
	<?= Html::a($model->descr,['/lic-types/view','id'=>$model->id]) ?>
	<?= Html::a('<span class="fas fa-pencil-alt"/>',['/lic-types/update','id'=>$model->id]) ?>
</h4>
<?php if (!$static_view) { ?>
	<p>
		<?= Yii::$app->formatter->asNtext($model->comment) ?>
	</p>
<?php } else echo '<br />' ?>
<br />
<?php } else echo "Ошибка описания типа лицензии<br />";