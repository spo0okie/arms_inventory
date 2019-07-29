<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.01.2019
 * Time: 2:49
 */

/* @var $this yii\web\View */
/* @var $model app\models\ProvTel */

if (!isset($static_view)) $static_view=false;

?>

	<h4><?= $model->getAttributeLabel('cabinet_url')?> </h4>
	<?= \Yii::$app->formatter->asUrl($model->cabinet_url) ?>
	<br />
	<h4><?= $model->getAttributeLabel('support_tel')?> </h4>
	<?= $model->support_tel ?>
	<br /><br />
	<?= \Yii::$app->formatter->asNtext($model->comment) ?>
