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

<div class="d-flex flex-row flex-wrap mb-2">
<?php if ($model->cabinet_url) { ?>
	<div class="me-5">

		<h4><?= $model->getAttributeLabel('cabinet_url')?> </h4>
		<?= \app\components\UrlListWidget::widget([
				'list'=>$model->cabinet_url,
		]) ?>
	</div>
<?php } ?>

<?php if ($model->support_tel) { ?>
	<div>
		<h4><?= $model->getAttributeLabel('support_tel')?> </h4>
		<?= \Yii::$app->formatter->asNtext($model->support_tel) ?>
		<br />
	</div>
<?php } ?>
</div>

<?php if ($model->comment) { ?>
	<h4><?= $model->getAttributeLabel('comment')?> </h4>
	<?= \Yii::$app->formatter->asNtext($model->comment) ?>
<?php } ?>

