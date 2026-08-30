<?php
/**
 * Блок «Сетевые порты» карточки устройства.
 *
 * Таблица здесь одна и её владелец — карточка. Опрос коммутатора
 * (интеграция macsearch) не рисует рядом вторую таблицу, а заменяет
 * содержимое этого же блока обогащённой версией: где записанное совпало с
 * найденным, где разошлось и что предлагается сделать
 * (plans/network-map.md, этап 3.4).
 */

/* @var \app\models\Techs $model */
/* @var $this yii\web\View */

$containerId = 'techs-ports-'.$model->id;
//весь блок целиком - один узел: раскладка корпуса не влезает в колонку
//карточки, и по кнопке блок переезжает под обе колонки, а потом обратно
$blockId = 'techs-ports-block-'.$model->id;
//раскладка рисуется рядом с таблицей (внутри контейнера) и показывается
//вместо неё стилем, поэтому кнопка живёт снаружи и переживает перерисовку
$layoutAvailable = \app\components\PortsLayoutWidget::available($model);
?>
<div id="<?= $blockId ?>" class="techs-ports-block">
<?php

//порты объявляет модель оборудования, но у экземпляра имена могут отличаться
//(стек, переименование на MikroTik) - тогда действует его «порты фактически»
if (!count($model->portsTemplate)) { ?>
	<div class="alert alert-striped">
		У этой модели оборудования нет сетевых портов.
		Если это неверно - <?= \yii\helpers\Html::a('отредактируйте модель оборудования',[
			'/tech-models/update',
			'id'=>$model->model_id,
			'return'=>'previous'
		]) ?>
		или объявите порты этого устройства в поле
		<?= \yii\helpers\Html::a('«Порты фактически»',[
			'/techs/update',
			'id'=>$model->id,
			'return'=>'previous'
		]) ?>.
	</div>
	<br/>
<?php }

//контейнер есть всегда, даже пустой: сюда ложится результат опроса, и у
//устройства без объявленных портов это единственный способ узнать, как они
//называются («взять имена портов с коммутатора» как раз для этого)
?>
	<div id="<?= $containerId ?>">
		<?php if (count($model->portsList)) { ?>
			<?= $this->render('_ports-table', ['model' => $model, 'ports' => null]) ?>
		<?php } ?>
	</div>
<?php

//кнопка опроса появляется только у коммутатора с настроенной интеграцией;
//результат ложится в контейнер выше, а не рядом с ним
echo \app\components\integrations\PanelsWidget::widget([
	'model' => $model,
	'only' => 'macsearch/switch',
	'target' => $containerId,
]);

echo ' '.\yii\helpers\Html::a(
	'Добавить нестандартный порт',
	[
		'/ports/create',
		'Ports[techs_id]'=>$model->id,
		'return'=>'previous'
	],[
		'class'=>'btn btn-sm btn-info',
		'qtip_ttip' => 'Стандартные порты редактируются в модели оборудования'
	]
);

//вид «раскладка» показывает то же самое, но напротив розеток; таблица при
//этом прячется. Кнопки нет, когда корпус не описан или он не стандартный
//(блоки в три ряда и больше) - раскладывать нечего
if ($layoutAvailable) echo ' '.\yii\helpers\Html::button('Раскладка портов', [
	'class' => 'btn btn-sm btn-outline-secondary',
	'data-ports-layout' => $model->id,
	'qtip_ttip' => 'Показать то же самое напротив розеток корпуса: '
		.'пояснения встают колонками над своим портом и под ним',
]);

//«ёлочка» - вопрос читаемости, а не данных: под 45° строка разбирается
//быстрее, чем под 90°, но занимает больше места вбок. Выбор за человеком,
//поэтому галочка, а не третья кнопка вида
if ($layoutAvailable) echo ' '.\yii\helpers\Html::tag('label',
	\yii\helpers\Html::checkbox('ports-diagonal', false, [
		'class' => 'form-check-input me-1',
		'data-ports-diagonal' => $model->id,
		'label' => null,
	]).'ёлочкой',
	[
		'class' => 'ports-layout-diagonal-toggle align-items-center small text-secondary ms-2',
		'qtip_ttip' => 'Довернуть подписи на 45°: начинаются они всё так же '
			.'у своей розетки, но читаются почти слева направо',
	]);
?>
</div>
<?php if ($layoutAvailable) { ?>
<?php /* Тогглер вида портов. Отдельным vanilla-<script> по тем же причинам, что
   и тогглер справки в layouts/main.php: независимое исполнение и делегирование
   на document (обработчик переживает и перерисовку блока опросом, и его переезд
   под колонки). Сам переезд - перенос ОДНОГО узла: контейнер таблицы при этом
   не пересоздаётся, поэтому AJAX-опрос продолжает находить его по id. */ ?>
<script>
(function(){
	//ключ общий, не по устройству: раскладку включают не «для этого
	//коммутатора», а на время работы со шкафом - и дальше она ожидается везде
	var id = <?= (int)$model->id ?>, key = 'portsLayout';
	var block = document.getElementById(<?= json_encode($blockId) ?>);
	if (!block) return;
	//переезжает вся секция вместе с заголовком «Сетевые порты», если карточка
	//её выделила: заголовок над опустевшим местом читался бы как ошибка
	var moving = document.getElementById('techs-ports-section-' + id) || block;
	//куда возвращать: место в карточке запоминаем до первого переезда
	var home = moving.parentNode, after = moving.nextSibling;

	//«ёлочка» живёт отдельным флажком: это оформление подписей внутри раскладки
	function diagonal(on){
		var layout = block.querySelector('.ports-layout');
		if (layout) layout.classList.toggle('ports-layout-diagonal', on);
		var check = block.querySelector('[data-ports-diagonal]');
		if (check) check.checked = on;
		try { localStorage.setItem(key + ':diagonal', on ? 'on' : 'off'); } catch(_) {}
	}

	function apply(on){
		block.classList.toggle('ports-view-layout', on);
		var wide = document.getElementById('techs-ports-wide-' + id);
		if (on && wide) wide.appendChild(moving);
		else if (!on && home) home.insertBefore(moving, after);
		var button = block.querySelector('[data-ports-layout]');
		if (button) button.textContent = on ? 'Таблица портов' : 'Раскладка портов';
	}

	document.addEventListener('click', function(e){
		if (!e.target.closest) return;
		var check = e.target.closest('[data-ports-diagonal="' + id + '"]');
		if (check) return diagonal(check.checked);

		var button = e.target.closest('[data-ports-layout="' + id + '"]');
		if (!button) return;
		e.preventDefault();
		apply(!block.classList.contains('ports-view-layout'));
		try { localStorage.setItem(key,
			block.classList.contains('ports-view-layout') ? 'on' : 'off'); } catch(_) {}
	});

	//выбранный вид держится между открытиями карточки: инженер, разбирающий
	//шкаф, смотрит на раскладку весь день. Ждём загрузки документа: место под
	//раскладку лежит НИЖЕ этого скрипта, и на момент его исполнения его нет
	document.addEventListener('DOMContentLoaded', function(){
		try { if (localStorage.getItem(key) === 'on') apply(true); } catch(_) {}
		//раскладку перерисовывает опрос - флажок применяем к свежей разметке
		try { if (localStorage.getItem(key + ':diagonal') === 'on') diagonal(true); } catch(_) {}
	});
})();
</script>
<?php } ?>
