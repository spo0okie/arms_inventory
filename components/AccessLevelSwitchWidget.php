<?php
namespace app\components;

use yii\base\Widget;
use yii\helpers\Html;
use yii\web\View;

/**
 * Переключатель уровня показа доступов (docs/dev/access-chains.md, §5).
 *
 * ЗАЧЕМ. Одна и та же запись доступа (ACE) нужна двум читателям:
 *  - информационный уровень — ответственному за сервис: КТО (субъекты: сервисы,
 *    пользователи, ОС, сегменты…) к ЧЕМУ (ресурс ACL) и каким МАРШРУТОМ (цепочка
 *    хопов через прокси по указателям next/prev_aces). Узлы и адреса ему — шум;
 *  - сетевой уровень — сетевику, настраивающему ACL/фаервол на один хоп: с каких
 *    УЗЛОВ и АДРЕСОВ на какие узлы и адреса. Сервис-субъект для него развёрнут в
 *    узлы, на которых сервис крутится, с их IP; маршрут ему не нужен.
 * Гриды ACE и ACL изначально выводят колонки обоих уровней рядом — таблица
 * получается широкой и «про всё сразу». Виджет ничего не пересчитывает и не
 * перезапрашивает: он только прячет колонки чужого уровня.
 *
 * КАКИЕ КОЛОНКИ К КАКОМУ УРОВНЮ (атрибуты гридов, см. attributeData моделей):
 *
 *   уровень         | грид ACE (Aces)  | грид ACL (Acls)  | что в колонке
 *   ----------------+------------------+------------------+------------------------------
 *   информационный  | subjects         | subjects         | объекты-субъекты как заданы
 *                   | resource         | resource         | ресурс ACL как задан
 *                   | transit          | transit          | маршруты (цепочки хопов)
 *   сетевой         | subject_nodes    | subjects_nodes   | субъекты, развёрнутые в узлы+IP
 *                   | resource_nodes   | resource_nodes   | ресурс, развёрнутый в узлы+IP
 *                   | network_hosts    |                  | то же в пределах сети (грид
 *                   |                  |                  | «Вх. соединения» у сети)
 *   оба (не прячутся) — типы доступа (порты/протоколы нужны обоим), пояснение,
 *   временное ограничение, комментарии и прочие колонки
 *
 * Разнобой subject_nodes (ACE) / subjects_nodes (ACL) — исторические имена
 * атрибутов двух моделей; поэтому в NET_COLUMNS перечислены оба.
 *
 * КАК КОЛОНКИ ПОМЕЧЕНЫ. Своих классов под уровни нет — используются метки, которые
 * гриды ставят любой колонке:
 *  - ячейка данных <td> получает класс `<атрибут>_col` (DynaGridWidget::defaultColumn
 *    задаёт его в contentOptions по умолчанию; DefaultColumn дожимает его в ячейку,
 *    даже если contentOptions колонки — замыкание или свой cardClass);
 *  - ячейка строки фильтров (тоже <td>, под заголовками) получает тот же класс
 *    `<атрибут>_col` (defaultColumn дописывает его в filterOptions) — поэтому одно
 *    правило td.<атрибут>_col прячет и данные, и фильтр; без этого фильтр оставался
 *    и шапка съезжала относительно данных;
 *  - заголовок <th> класса не получает, зато у него всегда есть атрибут
 *    `data-resizable-column-id="<атрибут>"` (тот же defaultColumn, для запоминания
 *    ширин колонок) — по нему и адресуется.
 * css() строит из констант правила вида
 *    body.access-level-net td.subjects_col,
 *    body.access-level-net th[data-resizable-column-id="subjects"] {display:none;}
 * т.е. уровень «сетевой» прячет INFO_COLUMNS, «информационный» — NET_COLUMNS,
 * «всё» (класса на body нет) — ничего.
 *
 * ПОЧЕМУ КЛАСС НА BODY, а не скрытие колонок JS-ом у конкретного грида: гриды во
 * вкладках страниц сервиса/сегмента/IP подгружаются асинхронно (async-grid) уже
 * после инициализации виджета, а при PJAX-перезагрузке перерисовываются. CSS-правило
 * от класса на body действует на любую таблицу, появившуюся на странице в любой
 * момент, без подписок на события загрузки.
 *
 * ШИРИНЫ КОЛОНОК (jquery.resizableColumns, проценты в style заголовков). Плагин берёт
 * только видимые заголовки (selector `tr th:visible` в DynaGridWidget; таблицы в скрытых
 * вкладках ждут видимости — visibilityWaitTimeout) и считает ширины один раз. После
 * переключения уровня js() раскладывает видимые колонки от «базовых» ширин — сохранённых
 * на сервере, их DynaGridWidget дублирует в data-base-width, т.к. style плагин
 * перезаписывает, — нормируя их к 100%, и пересобирает заголовки/ручки плагина.
 * Замерять раскладку нельзя: вернувшиеся колонки браузер сжимает до минимума, и замер
 * закрепил бы их схлопнутыми. Нет сохранённых ширин — ширины снимаются, раскладывает
 * браузер. Перетаскивание обновляет базу видимых колонок (событие column:resize:stop).
 *
 * СОСТОЯНИЕ. Выбор (both|info|net) живёт в localStorage браузера под ключом
 * $storageKey — общий для всех страниц: это личное удобство просмотра, а не
 * состояние данных. js() при загрузке читает его и ставит класс на body.
 *
 * ДОБАВИТЬ НОВУЮ КОЛОНКУ К УРОВНЮ: вписать имя атрибута в INFO_COLUMNS или
 * NET_COLUMNS. Колонка должна выводиться через штатный defaultColumn/DefaultColumn —
 * иначе у неё не будет метки `_col` и она не спрячется.
 */
class AccessLevelSwitchWidget extends Widget
{
	/** @var string ключ localStorage */
	public $storageKey='arms-access-level';

	/** колонки (атрибуты гридов ACE и ACL) только информационного уровня —
	 * прячутся при уровне «сетевой» */
	const INFO_COLUMNS=['subjects','resource','transit'];
	/** колонки только сетевого уровня — прячутся при уровне «информационный»;
	 * subject_nodes — грид ACE, subjects_nodes — грид ACL (одно и то же, имена разные);
	 * network_hosts — узлы ресурса в пределах сети (вкладка «Вх. соединения» сети,
	 * views/networks/aces-list.php, заменяет там resource_nodes) */
	const NET_COLUMNS=['subject_nodes','subjects_nodes','resource_nodes','network_hosts'];

	public function run()
	{
		$levels=[
			'both'=>['Всё','Показывать колонки обоих уровней'],
			'info'=>['Информационный','Кто к чему имеет доступ и каким маршрутом (через каких посредников): '
				.'субъекты, ресурс, маршрут. Узлы и адреса скрыты'],
			'net'=>['Сетевой','С каких узлов и адресов на какие узлы и адреса, по каким портам (один хоп): '
				.'то, что нужно для настройки сетевых ACL. Субъекты, ресурс и маршрут скрыты'],
		];

		$buttons=[];
		foreach ($levels as $level=>[$label,$hint]) {
			$buttons[]=Html::button($label,[
				'type'=>'button',
				'class'=>'btn btn-sm btn-outline-secondary access-level-btn',
				'data-access-level'=>$level,
				'qtip_ttip'=>$hint,
				'qtip_side'=>'bottom',
			]);
		}

		$this->view->registerCss($this->css());
		$this->view->registerJs($this->js(),View::POS_END);

		return Html::tag('div',
			Html::tag('span','Уровень доступов:',['class'=>'small opacity-75 me-2'])
			.Html::tag('div',implode('',$buttons),['class'=>'btn-group','role'=>'group']),
			['class'=>'access-level-switch d-flex align-items-center my-2']
		);
	}

	protected function css(): string
	{
		$rules=[];
		foreach (['info'=>static::NET_COLUMNS,'net'=>static::INFO_COLUMNS] as $level=>$hidden) {
			$selectors=[];
			foreach ($hidden as $column) {
				$selectors[]="body.access-level-$level td.{$column}_col";
				$selectors[]="body.access-level-$level th[data-resizable-column-id=\"$column\"]";
			}
			$rules[]=implode(",\n",$selectors).' {display:none;}';
		}
		return implode("\n",$rules);
	}

	protected function js(): string
	{
		$key=json_encode($this->storageKey);
		return <<<JS
(function(){
	var key=$key;
	function apply(level){
		document.body.classList.remove('access-level-info','access-level-net');
		if (level==='info'||level==='net') document.body.classList.add('access-level-'+level);
		document.querySelectorAll('.access-level-btn').forEach(function(btn){
			btn.classList.toggle('active',btn.getAttribute('data-access-level')===level);
		});
		resyncResizable();
	}
	//ширины колонок (jquery.resizableColumns, проценты в style заголовков) плагин считает при
	//инициализации таблицы. После смены набора видимых колонок раскладку НЕ замеряем: у
	//видимых уже стоят проценты на все 100%, и вернувшимся колонкам браузер отдаёт минимум —
	//замер закрепил бы их схлопнутыми. Вместо этого видимые колонки получают базовые ширины
	//(сохранённые на сервере, data-base-width от DynaGridWidget), нормированные к 100%,
	//и только потом плагин пересобирает заголовки и ручки (setHeaders/syncHandleWidths).
	//Плагин kartik вешается на контейнер грида (div[data-resizable-columns-id]), а не на table.
	//Таблицы, чья инициализация ещё ждёт видимости, возьмут видимые колонки сами
	//(selector tr th:visible), им нужна только раскладка ширин.
	//база таблицы: {auto: ширин нет — раскладывает браузер по содержимому, w: {колонка: %}}
	function baseWidths(\$c){
		var base=\$c.data('armsLevelBase');
		if (base) return base;
		base={auto:false,w:{}};
		fillBase(base,\$c,function(th){return parseFloat(th.getAttribute('data-base-width'));});
		\$c.data('armsLevelBase',base);
		//перетаскивание меняет ширины видимых колонок — переносим их в базу в её масштабе,
		//чтобы следующее переключение уровня не откатило сделанное
		\$c.on('column:resize:stop.armsLevel',function(){
			if (base.auto) {
				//до перетаскивания ширин не было: база — текущая раскладка видимых колонок
				fillBase(base,\$c,function(th){return visibleHeaders(\$c).is(th)?parseFloat(th.style.width):NaN;});
				return;
			}
			var visible=visibleHeaders(\$c), sum=0;
			visible.each(function(){sum+=base.w[this.getAttribute('data-resizable-column-id')];});
			visible.each(function(){
				var w=parseFloat(this.style.width);
				if (w>0) base.w[this.getAttribute('data-resizable-column-id')]=w*sum/100;
			});
		});
		return base;
	}
	//ширины колонок из источника; неизвестные — средней из известных; нет ни одной — auto
	function fillBase(base,\$c,source){
		var known=[];
		base.w={};
		\$c.find('thead tr:first th[data-resizable-column-id]').each(function(){
			var w=source(this);
			base.w[this.getAttribute('data-resizable-column-id')]=w>0?w:null;
			if (w>0) known.push(w);
		});
		base.auto=!known.length;
		var def=known.length?known.reduce(function(a,b){return a+b;},0)/known.length:0;
		for (var id in base.w) if (base.w[id]===null) base.w[id]=def;
	}
	function visibleHeaders(\$c){
		return \$c.find('thead tr:first th[data-resizable-column-id]').filter(function(){
			return getComputedStyle(this).display!=='none';
		});
	}
	function resyncResizable(){
		if (!window.jQuery) return;
		jQuery('[data-resizable-columns-id]').each(function(){
			var \$c=jQuery(this), base=baseWidths(\$c), visible=visibleHeaders(\$c), sum=0;
			if (base.auto) visible.each(function(){this.style.width='';});
			else visible.each(function(){sum+=base.w[this.getAttribute('data-resizable-column-id')];});
			if (sum>0) visible.each(function(){
				this.style.width=(base.w[this.getAttribute('data-resizable-column-id')]/sum*100).toFixed(2)+'%';
			});
			var rc=\$c.data('resizableColumns');
			if (!rc || !rc.\$handleContainer) return;
			rc.setHeaders();
			rc.syncHandleWidths();
		});
	}
	var stored='both';
	try {stored=localStorage.getItem(key)||'both';} catch(e){}
	apply(stored);
	document.querySelectorAll('.access-level-btn').forEach(function(btn){
		btn.addEventListener('click',function(){
			var level=btn.getAttribute('data-access-level');
			try {localStorage.setItem(key,level);} catch(e){}
			apply(level);
		});
	});
})();
JS;
	}
}
