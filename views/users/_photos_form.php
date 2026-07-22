<?php

use yii\bootstrap5\ActiveForm;
use kartik\file\FileInput;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

/**
 * Форма загрузки фотографий сотрудника (kartik FileInput).
 *
 * Отличие от /scans/_form: у сотрудника нет «предпочтительной» миниатюры
 * (scans_id), портрет определяется как последнее по дате изображение —
 * поэтому вызов scans/thumb (setPreviewScan) здесь не нужен.
 * Загружаемые файлы привязываются к сотруднику через Scans[users_id].
 */

$scans = $model->isNewRecord ? [] : $model->photos;
$preview = [];
$config = [];
foreach ($scans as $scan) {
	$preview[] = $scan->thumbUrl;
	$config[] = (object)[
		'caption' => $scan->noidxFname,
		'downloadUrl' => $scan->fullFname,
		'size' => $scan->fileSize,
		'key' => $scan->id,
	];
}
?>

<div class="scans-form">

	<?php $form = ActiveForm::begin([
		'id' => 'scans-form',
		'options' => ['enctype' => 'multipart/form-data'],
	]); ?>

	<?= FileInput::widget([
		'name' => 'Scans[scanFile]',
		'language' => 'ru',
		'options' => [
			'accept' => 'image/*',
			'id' => 'form_scans_input',
			'multiple' => true,
		],
		'pluginOptions' => [
			'initialPreview' => $preview,
			'initialPreviewAsData' => true,
			'initialPreviewConfig' => $config,
			'overwriteInitial' => false,
			'uploadUrl' => \yii\helpers\Url::to(['scans/create']),
			'deleteUrl' => \yii\helpers\Url::to(['scans/delete']),
			'uploadExtraData' => new \yii\web\JsExpression('function(previewId, index) {
				return {"Scans[users_id]" : ' . $model->id . '};
			}'),
		],
	]); ?>

	<?php ActiveForm::end(); ?>

</div>
