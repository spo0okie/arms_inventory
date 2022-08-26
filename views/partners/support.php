<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.01.2019
 * Time: 2:49
 */

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */

?>

<?php if ($model->cabinet_url) { ?>
	<h4><?= $model->getAttributeLabel('cabinet_url')?> </h4>
	<?= \Yii::$app->formatter->asUrl($model->cabinet_url) ?>
	<br />
<?php } ?>

<?php if ($model->support_tel) { ?>
	<h4><?= $model->getAttributeLabel('support_tel')?> </h4>
	<?= $model->support_tel ?>
	<br />
<?php } ?>

<?php if ($model->comment) { ?>
	<h4><?= $model->getAttributeLabel('comment')?> </h4>
	<?= \Yii::$app->formatter->asNtext($model->comment) ?>
<?php } ?>

