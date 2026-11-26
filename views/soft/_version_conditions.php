<?php

use app\helpers\SoftwareVersionFilter;
use yii\helpers\Html;
?>
<?php foreach ($format['fields'] ?? [] as $field=>[$label]) {
		$prefix = $name.'['.$field.']';
		$condition = $conditions[$field] ?? [];
		$id = $idPrefix.$field;
	?>
	<div class="d-flex align-items-center gap-2 mt-2">
		<?= Html::label($label, $id) ?>
		<?= Html::dropDownList($prefix.'[op]', is_string($condition['op'] ?? null) ? $condition['op'] : '=', SoftwareVersionFilter::OPERATORS,
			['class'=>'form-select w-auto', 'aria-label'=>'Сравнение: '.$label]) ?>
		<?= Html::textInput($prefix.'[value]', is_string($condition['value'] ?? null) ? $condition['value'] : '',
			['id'=>$id, 'class'=>'form-control w-auto', 'placeholder'=>'Любое значение']) ?>
	</div>
	<?php } ?>
