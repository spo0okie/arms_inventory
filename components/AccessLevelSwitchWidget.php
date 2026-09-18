<?php
namespace app\components;

use yii\base\Widget;
use yii\helpers\Html;
use yii\web\View;

/**
 * Переключатель уровня показа доступов (plans/access-chains.md, итерация 3).
 *
 * Списки ACE/ACL несут колонки двух уровней сразу:
 *  - информационный — кто (субъекты) к чему (ресурс) и каким маршрутом (транзит):
 *    уровень документирования межсервисных связей;
 *  - сетевой — с каких узлов/адресов на какие узлы/адреса по каким портам:
 *    уровень настройки сетевых ACL (один хоп).
 * Виджет прячет колонки «чужого» уровня классом на body, поэтому действует и на
 * гриды, подгружаемые во вкладки асинхронно. Выбор живёт в localStorage браузера:
 * это личное удобство просмотра, а не состояние данных.
 */
class AccessLevelSwitchWidget extends Widget
{
	/** @var string ключ localStorage */
	public $storageKey='arms-access-level';

	/** колонки (атрибуты гридов ACE и ACL), принадлежащие только одному уровню */
	const INFO_COLUMNS=['subjects','resource','transit'];
	const NET_COLUMNS=['subject_nodes','subjects_nodes','resource_nodes'];

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
