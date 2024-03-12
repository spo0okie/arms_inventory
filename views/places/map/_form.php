<?php

use app\helpers\ArrayHelper;
use app\models\Scans;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\ui\MapItemForm */
/* @var $mapImage Scans */
/* @var $techs app\models\ArmsModel[] */
/* @var $places app\models\ArmsModel[] */
/* @var $form yii\widgets\ActiveForm */



$srcWidth=$mapImage->getImageWidth();
$srcHeight=$mapImage->getImageHeight();
if ($srcWidth>$srcHeight) {
	$imgWidth=MAP_SIZE;
	$imgHeight=$srcHeight*MAP_SIZE/$srcWidth;
} else {
	$imgHeight=MAP_SIZE;
	$imgWidth=$srcWidth*MAP_SIZE/$srcHeight;
}

$jsCrop= /** @lang JavaScript */
<<<JS
		CropSelectJs.MINIMUM_SELECTION_BOX_WIDTH = 20;
		CropSelectJs.MINIMUM_SELECTION_BOX_HEIGHT = 20;

		jQuery('#edit-map').CropSelectJs({
  			imageSrc: '{$mapImage->fullFname}',
  			animatedBorder: false,
  			imgWidth: $imgWidth,
  			imgHeight: $imgHeight,
  			
            selectionMove: function (data) {
                //console.log(data);
  			    jQuery('input#mapitemform-x').val(Math.round(data.x));
  			    jQuery('input#mapitemform-y').val(Math.round(data.y));
  			},
        
            selectionResize: function (data) {
  			    //console.log(data)
  			    jQuery('input#mapitemform-width').val(Math.round(data.width));
  			    jQuery('input#mapitemform-height').val(Math.round(data.height));
  			}
		})
		$('img.crop-image').css('width','100%');
		
		
		function CropSelectInitRect(rect) {
    		//console.log(rect);
			let \$map=jQuery('#edit-map');
  			\$map.CropSelectJs('setSelectionBoxX',rect.x);
  			\$map.CropSelectJs('setSelectionBoxY',rect.y);
  			\$map.CropSelectJs('setSelectionBoxWidth',rect.width);
  			\$map.CropSelectJs('setSelectionBoxHeight',rect.height);
  			jQuery('input#mapitemform-x').val(rect.x);
  			jQuery('input#mapitemform-y').val(rect.y);
			jQuery('input#mapitemform-width').val(rect.width);
  			jQuery('input#mapitemform-height').val(rect.height);
		}
JS;
$this->registerJs($jsCrop);

?>
<div id="edit-map" style="width: <?= $imgWidth ?>px"></div>
<div class="map-item-form mt-1">
    <?php $form = ActiveForm::begin([
		'fieldConfig' => [
			'template' => "{label}{input}",
			'labelOptions'=>[
				'class'=>'form-label me-1'
			],
			'inputOptions'=>[
				'class'=>'form-control w-75 d-inline-block'
			]
		],
		'action' => Url::to(['places/map-set','id'=>$model->place_id]),
	]); ?>
	<?= $form->field($model,'place_id')->hiddenInput()->label(false)->hint(false) ?>
	<?= $form->field($model,'item_type')->hiddenInput()->label(false)->hint(false) ?>
	<div class="row text-nowrap">
		<div class="col-6">
			<div id="techs_id">
				<?= $form->field($model, 'techs_id')->widget(Select2::class, [
					'data' => ArrayHelper::map($techs,'id','name'),
					'toggleAllSettings'=>['selectLabel'=>null],
					'pluginOptions' => ['multiple' => false],
				])->label(false) ?>
			</div>
			<div id="places_id">
				<?= $form->field($model, 'places_id')->widget(Select2::class, [
					'data' => ArrayHelper::map($places,'id','name'),
					'toggleAllSettings'=>['selectLabel'=>null],
					'pluginOptions' => ['multiple' => false],
				])->label(false) ?>
			</div>
		</div>
		<div class="col-1">
			<?= $form->field($model, 'x')->textInput() ?>
		</div>
		<div class="col-1">
			<?= $form->field($model, 'y')->textInput() ?>
		</div>
		<div class="col-1">
			<?= $form->field($model, 'width')->textInput() ?>
		</div>
		<div class="col-1">
			<?= $form->field($model, 'height')->textInput() ?>
		</div>
		<div class="form-group col-2 text-end">
			<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
			<?= Html::tag('span','Cancel', [
				'class' => 'btn btn-danger',
				'onClick' => new JsExpression("$('#place-map').show();$('#item-edit').hide();")
			]) ?>
		</div>
	</div>
    <?php ActiveForm::end(); ?>

</div>
