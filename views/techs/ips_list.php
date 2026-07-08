<?php

use app\components\ModelFieldWidget;
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */
/* @var $this yii\web\View */
/* @var $model app\models\Techs */

if (!isset($static_view)) $static_view=false;

?>

	<?= ModelFieldWidget::renderFieldTitle($model,'ip') ?>

<?= ModelFieldWidget::renderFieldValue($model,'netIps',['glue'=>'<br />']) ?>

