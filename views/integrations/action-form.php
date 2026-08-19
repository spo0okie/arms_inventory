<?php

use app\components\Forms\ArmsForm;
use app\components\integrations\IntegrationProvider;
use app\components\widgets\page\ModelWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $provider IntegrationProvider */
/* @var $actionId string */
/* @var $descriptor array дескриптор действия (см. IntegrationProvider::actions()) */
/* @var $form \yii\base\Model форма параметров действия */
/* @var $model \app\models\base\ArmsModel|null объект (null для standalone) */
/* @var $isPersonal bool L2+: запросить личные учетные данные внешней ИС */
/* @var $credentialsError string|null ошибка ввода учетных данных */

if (!isset($modalParent)) $modalParent = null;

$title = $descriptor['title'] ?? $provider->getTitle();

?>

<h1><?= Html::encode($title) ?></h1>
<?php if (is_object($model)) { ?>
	<div class="mb-2">
		<?= ModelWidget::widget(['model' => $model, 'view' => 'item', 'options' => ['static_view' => true]]) ?>
	</div>
<?php } ?>
<div class="integration-action-form disable-on-submit">

	<?php $activeForm = ArmsForm::begin([
		'id' => 'integration-action-form',
		'enableClientValidation' => false,
	]); ?>

	<?php $provider->modalParent = $modalParent; //для select2 dropdownParent в формах провайдера ?>
	<?= $provider->renderActionForm($actionId, $form, $activeForm) ?>

	<?php if ($isPersonal) { ?>
		<div class="card mb-3">
			<div class="card-header py-1">
				Учетные данные во внешней ИС (<?= Html::encode($provider->getTitle()) ?>)
			</div>
			<div class="card-body">
				<?php if ($credentialsError) { ?>
					<div class="alert alert-danger"><?= Html::encode($credentialsError) ?></div>
				<?php } ?>
				<p class="text-secondary">
					Именное действие: выполняется от имени введенной учетной записи.
					Данные используются на один запрос и нигде не сохраняются.
				</p>
				<div class="row">
					<div class="col-6">
						<label class="form-label" for="ext_login">Логин</label>
						<?= Html::textInput('ext_login', '', ['class' => 'form-control', 'id' => 'ext_login', 'autocomplete' => 'off']) ?>
					</div>
					<div class="col-6">
						<label class="form-label" for="ext_password">Пароль</label>
						<?= Html::passwordInput('ext_password', '', ['class' => 'form-control', 'id' => 'ext_password', 'autocomplete' => 'off']) ?>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="form-group">
		<?= Html::submitButton('Выполнить', ['class' => 'btn btn-success spinner-on-submit']) ?>
	</div>

	<?php ArmsForm::end(); ?>

</div>
