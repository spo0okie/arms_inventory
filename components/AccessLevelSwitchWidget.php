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
