<?php

use app\models\Tags;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Правило тегирования — '.$model->descr;
$formats = Yii::$app->params['soft.version_formats'];
$selected = is_string($rule->format) ? $rule->format : '';
$tags = Tags::find()->where(['archived'=>0])->orderBy('name')->all();
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm() ?>
	<?= Html::errorSummary($rule) ?>
	<?= Html::label('Тег', 'rule-tag') ?>
	<?= Html::dropDownList('tag_id', $rule->tag_id, ArrayHelper::map($tags,'id','name'), [
		'id'=>'rule-tag','class'=>'form-select w-auto mb-2','prompt'=>'Выберите тег','required'=>true,
	]) ?>
	<?= Html::label('Формат версии', 'rule-format') ?>
	<?= Html::dropDownList('format', $selected, ArrayHelper::getColumn($formats,'label'), [
		'id'=>'rule-format','class'=>'form-select w-auto','prompt'=>'Выберите формат','required'=>true,
	]) ?>
	<?php foreach ($formats as $key=>$format): ?>
		<fieldset data-format="<?= Html::encode($key) ?>" <?= $key === $selected ? '' : 'hidden disabled' ?>>
			<?= $this->render('_version_conditions', [
				'format'=>$format,'conditions'=>$key === $selected && is_array($conditions) ? $conditions : [],
				'name'=>'conditions['.$key.']','idPrefix'=>'rule-'.$key.'-',
			]) ?>
		</fieldset>
	<?php endforeach ?>
	<p class="form-text">Пустое поле допускает любое значение. Все условия должны совпасть на одной версии пакета.</p>
	<?= Html::submitButton('Сохранить правило',['class'=>'btn btn-primary']) ?>
	<?= Html::a('Отмена',['view','id'=>$model->id],['class'=>'btn btn-secondary']) ?>
<?= Html::endForm() ?>
<?php
// В POST уходят только условия выбранного формата.
$this->registerJs(<<<'JS'
jQuery('#rule-format').on('change', function () {
	const format = this.value;
	jQuery(this.form).find('fieldset[data-format]').each(function () {
		this.hidden = this.disabled = this.dataset.format !== format;
	});
});
JS);
