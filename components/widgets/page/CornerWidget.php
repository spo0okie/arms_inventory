<?php
namespace app\components\widgets\page;

use app\components\assets\CornerWidgetAsset;
use app\components\HistoryWidget;
use app\components\ShowArchivedWidget;
use app\models\base\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Угловой блок страницы/колонки: тогглер "Архивные" + отметка "Изменено..."
 * (+произвольные добавки: иконки действий, теги, статистика).
 *
 * По умолчанию блок висит оверлеем в правом верхнем углу родительского элемента
 * и не занимает отдельную строку; если оверлей накрывает контент соседей по
 * родителю - скрипт (js/corner.js в CornerWidgetAsset) переводит его в
 * собственную строку (класс own-row). position:relative родителю скрипт
 * добавляет сам, вьюхе об этом заботиться не нужно.
 *
 * Рекомендуемое место вставки - ПЕРВЫМ элементом последней (правой) колонки:
 * тогда при коллизии вниз сдвигается только эта колонка, а не вся страница,
 * и H1 остаётся в левом верхнем углу.
 *
 * Типовые формы вызова:
 *   CornerWidget::widget(['model'=>$model])                     // Архивные + Изменено
 *   CornerWidget::widget(['model'=>$model,'archived'=>false])   // только Изменено
 *   CornerWidget::begin(['model'=>$model]); ...добавки... CornerWidget::end();
 *
 * Примечание: сам факт рендера ShowArchivedWidget переключает страницу в режим
 * "архивные по умолчанию скрыты" (ShowArchivedWidget::$defaultValue) - эта
 * неочевидная связь сохраняется и при вызове через этот виджет.
 */
class CornerWidget extends Widget
{
	/** @var ?ArmsModel модель страницы (нужна для отметки об изменениях) */
	public ?ArmsModel $model=null;

	/** @var bool рисовать ли переключатель архивных */
	public bool $archived=true;

	/** @var array опции ShowArchivedWidget */
	public array $archivedOptions=['reload'=>false];

	/** @var bool рисовать ли отметку об изменениях
	 * (если у модели нет данных об изменениях - отметка скрывается сама) */
	public bool $history=true;

	/** @var array опции HistoryWidget */
	public array $historyOptions=[];

	/** @var string произвольное содержимое ниже стандартных элементов
	 * (альтернатива begin/end для вьюх, собирающих разметку строками) */
	public string $content='';

	/** @var array HTML-опции контейнера */
	public array $options=[];

	public function init()
	{
		parent::init();
		CornerWidgetAsset::register($this->view);
		ob_start();
		ob_implicit_flush(false);
	}

	public function run()
	{
		$content=$this->content.ob_get_clean();

		$parts=[];
		if ($this->archived)
			$parts[]=ShowArchivedWidget::widget($this->archivedOptions);

		if ($this->history && $this->model) {
			$history=HistoryWidget::widget(array_merge(
				['model'=>$this->model,'empty'=>''],
				$this->historyOptions
			));
			if (strlen(trim($history)))
				$parts[]='<small class="opacity-75">'.$history.'</small>';
		}

		if (strlen(trim($content))) $parts[]=$content;
		if (!count($parts)) return '';

		Html::addCssClass($this->options,['text-end','page-corner-widget']);
		return Html::tag('div',implode('<br/>',$parts),$this->options);
	}
}
