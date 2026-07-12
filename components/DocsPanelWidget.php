<?php

namespace app\components;

use app\helpers\DocsHelper;
use yii\base\Widget;

/**
 * Инфоблок встроенной документации (plans/help-inline.md): трансклюзия
 * секций MD-страницы сущности (docs/help/models/<class-id>.md) на страницу,
 * которую она описывает. Текст рендерится как есть, 1:1 с /docs
 * (никаких offline-масок); ссылки на другие MD открываются модалками.
 *
 * Подача: свёрнутый bootstrap-collapse, состояние запоминается в
 * localStorage по ключу страницы; тогглер — HintIconWidget (иконка «?»).
 * Нет страницы или секции пусты — виджет не рендерит ничего.
 */
class DocsPanelWidget extends Widget
{
	/** @var string|object класс модели (или объект) - путь страницы по конвенции */
	public $model;

	/** @var string|null явный относительный путь MD-страницы (вместо model) */
	public $path;

	/**
	 * @var array какие фрагменты показать (по порядку):
	 * null - преамбула-концепция, строка - каноническая H2-секция.
	 * index-страница: [null,'Список']; карточка: ['Просмотр'].
	 */
	public $sections=[null,'Список'];

	/**
	 * @var bool показывать H2-заголовки секций внутри панели. Одиночной
	 * канонической секции заголовок не нужен (его даёт подача страницы),
	 * но когда панель собирает несколько секций гайда (как IPAM),
	 * без заголовков они сливаются в сплошной текст.
	 */
	public $headings=false;

	/** @var string ограничение высоты панели (внутренний скролл) */
	public $maxHeight='60vh';

	/** @var string id контейнера-collapse (для тогглера); дефолт - по classId */
	public $panelId;

	/**
	 * id панели документации сущности на этой странице
	 * (соглашение виджета и тогглера HintIconWidget).
	 * @param string|object $model
	 */
	public static function panelId($model): string
	{
		return 'docs-panel-'.DocsHelper::modelClassId($model);
	}

	public function run()
	{
		$classId=$this->model ? DocsHelper::modelClassId($this->model) : null;
		$relPath=$this->path ?? ($classId ? DocsHelper::modelPagePath($classId) : null);
		if (!$relPath) return '';

		$file=DocsHelper::findPage($relPath);
		if (!$file) return '';

		$content='';
		foreach ($this->sections as $section) {
			$content.=DocsHelper::renderSection($file,$relPath,$section,$this->headings);
		}
		if (!strlen(trim($content))) return '';

		return $this->render('docsPanel/panel',[
			'panelId'=>$this->panelId ?? ($classId ? static::panelId($this->model) : 'docs-panel-'.md5($relPath)),
			'content'=>$content,
			'maxHeight'=>$this->maxHeight,
			'moreUrl'=>$classId ? ['/docs/model','class'=>$classId] : ['/docs/page','path'=>$relPath],
		]);
	}
}
