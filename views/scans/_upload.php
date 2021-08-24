<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\file\FileInput;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $form yii\widgets\ActiveForm */

/* @var string $link */

/**
 *
 */
$js = <<<JS
    function setPreviewScan(model,id,stack,response=null) {
    	if (Array.isArray(stack) && stack.length) {
    	    console.log(stack[0].key)
    	} else if (Array.isArray(response) && response.length) {
    	    console.log(response[0].id)
    	}
    }
JS;
$this->registerJs($js);

?>
	
	<div class="scans-form">
		
		<?php $form = ActiveForm::begin([
			'id'=>'scans-form',
			'options' => ['enctype' => 'multipart/form-data'],
		]); ?>
		
		<?php
		
		if (!$model->isNewRecord) $scans=$model->scans;
		else $scans=[];
		$preview=[];
		$config=[];
		foreach ($scans as $scan) {
			$preview[]=$scan->thumbUrl;
			$config[]=(object)[
				'caption'=>$scan->noidxFname,
				'downloadUrl'=>$scan->fullFname,
				'size'=>$scan->fileSize,
				'key'=>$scan->id
			];
		}
		
		echo FileInput::widget([
			'name' => 'Scans[scanFile]',
			'language' => 'ru',
			'options' => [
				'accept' => 'image/*',
				'id' => 'form_scans_input',
				'multiple' => true
			],
			'pluginOptions' => [
				'initialPreview' => $preview,
				'initialPreviewAsData' => true,
				'initialPreviewConfig' => $config,
				'overwriteInitial' => false,
				'uploadUrl' => \yii\helpers\Url::to(['scans/create']),
				'deleteUrl' => \yii\helpers\Url::to(['scans/delete']),
				'uploadExtraData' => new \yii\web\JsExpression('function(previewId, index) {
				return {"Scans['.$link.']" : '.$model->id.'};
			}'),
			],
			'pluginEvents' => [
				'filesorted' =>	new \yii\web\JsExpression('function(event, params) {
    			setPreviewScan("'.$link.'",'.$model->id.',params.stack);
			}'),
				'filedeleted' => new \yii\web\JsExpression('function(event, key, jqXHR, data) {
    			console.log(\'deleted \' + key);
    			setPreviewScan("'.$link.'",'.$model->id.',$("#form_scans_input").fileinput(\'getPreview\').config);
			}'),
				'fileuploaded' => new \yii\web\JsExpression('function(event, data, previewId, index, fileId) {
				//var form = data.form, files = data.files, extra = data.extra,
        		//response = data.response, reader = data.reader;
    			console.log(\'File uploaded triggered\', fileId, data);
    			setPreviewScan("'.$link.'",'.$model->id.',$("#form_scans_input").fileinput(\'getPreview\').config,data.response);
			}'),
			]
		]);
		?>
		
		<?php ActiveForm::end(); ?>
		<br />
		<br />
		
		<?= Html::a('Назад', ['view','id' => $model->id],['class'=>'btn btn-success']) ?>
	
	
	</div>

