<?php

use app\components\TextFieldWidget;
use yii\helpers\Html;
use app\components\LinkObjectWidget;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */

if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?php // Стандартная шапка учитывает обратные связи и статический режим тултипа. ?>
	<?= LinkObjectWidget::widget([
		'model' => $model,
		'name' => Html::encode($model->sname),
		'static' => $static_view,
		'hideUndeletable' => false,
	]) ?>
</h1>
<?= \app\components\ModelFieldWidget::renderFieldValue($model,'commentRecursive') ?>
<br /><br />

<h4>L2 Домен</h4>
<?= $model->domain_id === null ? 'Резервирование VLAN для всех L2-доменов' : ModelWidget::widget(['model'=>$model->netDomain]) ?>





