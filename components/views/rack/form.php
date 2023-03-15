<?php
/**
 * @var $form \yii\widgets\ActiveForm
 * @var $model \app\models\TechModels
 * @var $attr string
 * @var $layout string
 * @var $rack \app\components\RackWidget
 * @var $rackDefault\app\components\RackWidget
 */

use yii\helpers\Html;

?>

<div class="row">
	<div class="col-md-6" >
		<div class="d-flex flex-row">
			<div class="pe-2">
				<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,'contain_'.$attr) ?>
			</div>
			<div class="pe-2">
				<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,$attr.'_two_sided') ?>
			</div>
		</div>
		<?= \app\components\CollapsableCardWidget::widget([
			'openedTitle'=>'Конструктор',
			'closedTitle'=>'Конструктор',
			'initialCollapse'=>!(!strlen($model->$layout) || (is_object($rack) && $rack->isSimpleConfig)),
			'content'=>'<div class="card-body">'.$this->render('constructor',[
				'attr'=>$layout,
				'form'=>$form,
				'model'=>$model,
				'rack'=>$rack,
				'rackDefault'=>$rackDefault,
				'empty'=>!strlen($model->$layout)
			]).'</div>',
		]); ?>
		<?= \app\components\CollapsableCardWidget::widget([
			'openedTitle'=>'Расширенные настройки',
			'closedTitle'=>'Расширенные настройки',
			'initialCollapse'=>!strlen($model->$layout) || (is_object($rack) && $rack->isSimpleConfig),
			'content'=>'<div class="card-body">'.
				\app\helpers\FieldsHelper::TextAutoresizeField($form,$model,$layout,['lines'=>4]).
				Html::button('Предпросмотр',[
					'class'=>'btn btn-secondary',
					'onClick'=>'previewRackConfFor("'.$layout.'")',
				]).'</div>',
		]); ?>
	</div>
	<div class="col-md-6 card p-2" >
		<div class="card-title">Просмотр</div>
		<div class="card-body" id="preview-<?= $layout ?>">
			<?= strlen($model->$layout)?
				\app\components\RackWidget::widget(json_decode($model->$layout,true)):
				Html::tag('div','Отсутствует конфигурация корзины',[
					'class'=>'alert alert-striped'
				]);
			?>
		</div>
	</div>
</div>

