<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $baseIp string */
/* @var $maxPrefix integer */
/* @var $minPrefix integer */

?>

<div class="ipam-form">
	<?php $form = ActiveForm::begin([
		'method' => 'get',
		'action' => ['networks/ipam'], // или текущий route
		'options' => ['class' => 'form-inline'],
	]); ?>

	<div class="d-flex flex-row justify-content-center align-items-end mb-4">
		<div class="p-1">
			<?= Html::label('Адрес сети:', 'baseIp') ?>
			<?= Html::textInput('baseIp', $baseIp, [
				'class' => 'form-control  me-2',
				'style' => 'width: 150px;']) ?>
		</div>
		<div class="p-1">
			<?= Html::label('Маска:', 'maxPrefix') ?>
			<?= Html::input('number', 'maxPrefix', $maxPrefix, [
				'id' => 'maxPrefix',
				'min' => 0,
				'max' => 30,
				'class' => 'form-control me-2',
				'style' => 'width: 70px;'
			]) ?>
		</div>
		<div class="p-1">
			<?= Html::label('Детализация:', 'minPrefix') ?>
			<?= Html::input('number', 'minPrefix', $minPrefix, [
				'id' => 'minPrefix',
				'min' => 0,
				'max' => 30,
				'class' => 'form-control  me-2',
				'style' => 'width: 70px;'
			]) ?>
		</div>
		<div class="p-1">
			<?= Html::textInput('rows', (1 << ($minPrefix - $maxPrefix)).' строк', [
				'id' => 'rowsCount',
				'class' => 'form-control  me-2',
				'style' => 'width: 100px;',
				'disabled' => true
			]) ?>
		</div>
		<div class="p-1">
			<?= Html::submitButton('Построить', [
				'id' => 'buildBtn',
				'class' => 'btn btn-primary'
			]) ?>
		</div>
	</div>
	<?php ActiveForm::end(); ?>
	
</div>
<?php
$this->registerJs(<<<JS
function updateButtonLabel() {
    const min = parseInt(document.getElementById('minPrefix').value);
    const max = parseInt(document.getElementById('maxPrefix').value);
    const info = document.getElementById('rowsCount');

    if (!isNaN(min) && !isNaN(max) && min >= max) {
        const rows = 1 << (min - max);
        info.value = rows + ' строк';
    } else {
        info.value = '0_о';
    }
}

document.getElementById('minPrefix').addEventListener('input', updateButtonLabel);
document.getElementById('maxPrefix').addEventListener('input', updateButtonLabel);

updateButtonLabel(); // инициализация при загрузке
JS);
?>