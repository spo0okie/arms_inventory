<?php

namespace app\components;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Блок «Добавить группу портов» к текстовому объявлению портов.
 *
 * Порты объявляются текстом (строка = порт, порядок строк = порядок портов
 * на корпусе), и вводить 48 строк руками незачем: блок дописывает их разом
 * по «с какого — по какой — с каким префиксом». Комментарий к отдельному
 * порту («сгорел», «в патч-панель 3») дописывается уже руками — именно он и
 * есть то, ради чего объявление хранится списком, а не правилом.
 *
 * Используется и у модели оборудования ({@see \app\models\TechModels::$ports}),
 * и у конкретного устройства ({@see \app\models\Techs::$ports_override}) — формат
 * объявления один и тот же, поэтому и редактор общий.
 */
class PortsGroupWidget extends Widget
{
	/** @var string id textarea, в которую дописываются порты */
	public string $fieldId = '';

	/** @var string заголовок блока */
	public string $title = 'Добавить группу портов';

	/** @var int|string номер первого порта по умолчанию */
	public $min = 1;

	/** @var int|string номер последнего порта по умолчанию */
	public $max = 16;

	/** @var array кнопки предзаполнения: подпись => текст, которым заполнить */
	public array $prefill = [];

	/**
	 * Сторож флажка «переименовать порты по позициям».
	 *
	 * Флажок по умолчанию включён, а снимается сам, как только число строк в
	 * объявлении расходится с исходным: одинаковое число строк - это
	 * переименование (были 1-48, стали Gi0/1-48), другое - сдвиг списка
	 * (добавили CON в начало), и переезд связей по позициям тут навредит.
	 * Вернуть число строк - флажок включится обратно; человек волен
	 * переключить его руками в любой момент.
	 *
	 * @param \yii\web\View $view
	 * @param string $fieldId id textarea с объявлением
	 * @param string $checkboxId id флажка
	 * @param int|null $baseline число строк «до»; null - считать по полю при загрузке
	 */
	public static function registerRenameGuard($view, string $fieldId, string $checkboxId,
		?int $baseline = null): void
	{
		$base = is_null($baseline) ? 'null' : (int)$baseline;
		$view->registerJs(<<<JS
(function () {
	var field = $('#$fieldId'), box = $('#$checkboxId');
	if (!field.length || !box.length) return;
	var lines = function (text) {
		return text.split(/\\r?\\n/).filter(function (line) {
			line = line.trim(); return line.length && line[0] !== '#';
		}).length;
	};
	var baseline = $base;
	if (baseline === null) baseline = lines(field.val());
	field.on('input', function () { box.prop('checked', lines(field.val()) === baseline); });
})();
JS
		);
	}

	public function run()
	{
		if (!$this->fieldId) return '';

		//id полей уникальны на страницу: блоков может быть несколько
		$scope = 'ports-group-'.$this->id;
		$field = Json::encode('#'.$this->fieldId);

		$add = 'var box=$('.$field.'), min=parseInt($("#'.$scope.'-min").val()),'
			.'max=parseInt($("#'.$scope.'-max").val()), prefix=$("#'.$scope.'-prefix").val(),'
			.'lines=[];'
			.'if (isNaN(min)||isNaN(max)||max<min) return;'
			.'for (var i=min;i<=max;i++) lines.push(prefix+i);'
			//дописываем к тому, что уже введено: грядки бывают из нескольких групп
			.'box.val(box.val().length ? box.val().replace(/\n+$/,"")+"\n"+lines.join("\n")'
			.' : lines.join("\n"));';

		$html = Html::beginTag('div', ['class' => 'ports-group-adder'])
			.Html::tag('h4', Html::encode($this->title))
			.$this->numberInput($scope.'-min', 'Начиная с номера', $this->min,
				'С какого номера начинается нумерация портов на устройстве. '
					.'Иногда 0, иногда 1, иногда 2, если первый порт называется WAN')
			.$this->numberInput($scope.'-max', 'До номера', $this->max,
				'На каком номере заканчивается нумерация портов (4/8/16/24/48/52)')
			.$this->numberInput($scope.'-prefix', 'С префиксом', '',
				'Если порты не просто пронумерованы, а с префиксом: LAN, Eth, Gi1/0/')
			.Html::button('Добавить', ['class' => 'btn btn-default mt-1', 'onClick' => $add]);

		foreach ($this->prefill as $label => $text) {
			//предзаполнение затирает поле целиком: это «начать с того, что есть»,
			//а не «дописать» - иначе объявление удвоится
			$html .= ' '.Html::button(Html::encode($label), [
				'class' => 'btn btn-outline-secondary mt-1',
				'onClick' => 'if ($('.$field.').val().length && !confirm('
					.Json::encode('Заменить объявленные порты?').')) return;'
					.'$('.$field.').val('.Json::encode($text).');',
			]);
		}

		return $html.Html::endTag('div');
	}

	/** Поле ввода с подписью и подсказкой — как в остальных формах */
	protected function numberInput(string $id, string $label, $value, string $hint): string
	{
		return Html::label($label, $id)
			.Html::textInput($id, $value, ['id' => $id, 'class' => 'form-control', 'maxlength' => 8])
			.Html::tag('div', Html::encode($hint), ['class' => 'hint-block']);
	}
}
