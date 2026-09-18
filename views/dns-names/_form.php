<?php

use app\components\Forms\ArmsForm;
use app\helpers\ArrayHelper;
use app\models\Domains;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\DnsNames */
/* @var $form app\components\Forms\ArmsForm */
if (!isset($modalParent)) $modalParent = null;

//зоны — по fqdn (NetBIOS-имя тут ни при чём); список из общего кэша справочника
$zones = ArrayHelper::map(Domains::getAllItems(true), 'id', 'fqdn');
asort($zones);
?>

<div class="dns-names-form">

	<?php $form = ArmsForm::begin(['model' => $model]); ?>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'domain_id')->select2(['data' => $zones]) ?>
		</div>
		<div class="col-md-8">
			<?= $form->field($model, 'host') ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<?= $form->field($model, 'ip')->textAutoresize(['rows' => 2]) ?>
		</div>
		<div class="col-md-8">
			<?= $form->field($model, 'comment') ?>
		</div>
	</div>

	<div class="form-group">
		<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
	</div>

	<?php ArmsForm::end(); ?>

</div>
