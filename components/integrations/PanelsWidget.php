<?php

namespace app\components\integrations;

use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Блок «Интеграции» в карточке объекта
 * (docs/dev/integrations.md).
 *
 * Обходит реестр (включён + настроен + RBAC + appliesTo) и для каждой
 * панели каждого подошедшего провайдера выводит контейнер с содержимым
 * файлового кэша в приглушённом виде — БЕЗ обращений к внешним ИС.
 * Если кэш отсутствует или устарел — дописывает скрипт ajax-обновления
 * (паттерн WikiTextWidget): ответ /integrations/panel подменяет
 * содержимое и снимает приглушение.
 *
 * Кнопки действий провайдеров (L2/L2+, если у действия не выставлен
 * showInPanel=false) выводятся под панелями и открываются в модалке.
 *
 * Если ни один провайдер не подошёл — не выводит ничего (пустой блок
 * не занимает место, слот безопасно вставлять в любую карточку).
 */
class PanelsWidget extends Widget
{
	/** @var ArmsModel|null объект, карточка которого рендерится */
	public ?ArmsModel $model = null;

	/**
	 * @var bool компактный режим - для встраивания в списки вложенных
	 * объектов (ОС внутри АРМ в карточке сотрудника): плотнее отступы
	 * и без кнопок действий (действия с журналом - на карточке самого
	 * объекта)
	 */
	public bool $compact = false;

	/**
	 * @var string|null вывести только эту панель: 'провайдер' либо
	 * 'провайдер/панель'. Нужно, когда панель встраивается не в общий блок
	 * интеграций, а в свой раздел карточки
	 */
	public ?string $only = null;

	/**
	 * @var string|null id контейнера НА СТРАНИЦЕ, содержимое которого панель
	 * заменяет собой (вместо собственного контейнера). Так панель обогащает
	 * готовый блок карточки, а не рисует рядом вторую его копию: у таблицы
	 * портов коммутатора владелец — карточка, опрос лишь дополняет её
	 */
	public ?string $target = null;

	public function run()
	{
		if (!is_object($this->model) || $this->model->isNewRecord) return '';
		$model = $this->model;
		$classId = StringHelper::class2Id(get_class($model));

		$panelsHtml = '';
		$buttonsHtml = '';
		foreach (IntegrationsRegistry::providers() as $provider) {
			if (!IntegrationsRegistry::userCanView($provider)) continue;
			if (!$provider->appliesTo($model)) continue;

			if (!$this->wanted($provider->id)) continue;

			$binding = $provider->binding($model);
			foreach ($provider->panels($model) as $panelId => $descriptor) {
				if (!$this->wanted($provider->id, $panelId)) continue;

				//панель, запрошенную поимённо, рисуем независимо от auto: её
				//позвал конкретный блок карточки, значит она там и нужна
				$auto = $this->explicit() ? 'button' : ($descriptor['auto'] ?? true);

				//'auto' => false: панель существует (её отдаёт proxy), но в
				//карточке сама не появляется - её открывают по действию
				//пользователя. Так живут дорогие запросы, которые незачем
				//делать при каждом открытии карточки (опрос коммутаторов)
				if ($auto === false) continue;

				//'auto' => 'button': тот же дорогой запрос, но точка входа
				//нужна в самой карточке - рисуем кнопку, а панель грузим по клику
				if ($auto === 'button') {
					$panelsHtml .= $this->renderButtonPanel($provider, $panelId, $descriptor,
						$binding, $classId);
					continue;
				}

				$panelsHtml .= $this->renderPanel($provider, $panelId, $descriptor, $binding, $classId);
			}

			//встроенная панель - только она сама, без кнопок действий провайдера
			if ($this->compact || $this->target || $this->only) continue;

			foreach ($provider->actions($model) as $actionId => $descriptor) {
				if (($descriptor['showInPanel'] ?? true) === false) continue;
				if (!IntegrationsRegistry::userCanRun($provider, $actionId)) continue;
				$buttonsHtml .= Html::a(
					(empty($descriptor['icon']) ? '' : '<i class="'.$descriptor['icon'].'"></i> ')
						.Html::encode($descriptor['title'] ?? $actionId),
					IntegrationsRegistry::actionUrl($provider, $actionId, $descriptor, $model),
					['class' => 'btn btn-sm btn-secondary me-2 open-in-modal-form']
				);
			}
		}

		if ($panelsHtml === '' && $buttonsHtml === '') return '';
		return '<div class="integrations-block">'
			.$panelsHtml
			.($buttonsHtml === '' ? '' : '<div class="integrations-actions mt-1 mb-2">'.$buttonsHtml.'</div>')
			.'</div>';
	}

	/** Панель запрошена поимённо ('провайдер/панель'), а не по провайдеру? */
	protected function explicit(): bool
	{
		return $this->only && strpos($this->only, '/') !== false;
	}

	/** Эту панель просили вывести? ($only: 'провайдер' либо 'провайдер/панель') */
	protected function wanted(string $providerId, ?string $panelId = null): bool
	{
		if (!$this->only) return true;
		[$wantedProvider, $wantedPanel] = array_pad(explode('/', $this->only, 2), 2, null);
		if ($wantedProvider !== $providerId) return false;
		return is_null($panelId) || is_null($wantedPanel) || $wantedPanel === $panelId;
	}

	/**
	 * Панель по кнопке ('auto' => 'button'): дорогой запрос, которому всё же
	 * нужна точка входа в карточке.
	 *
	 * Свежий результат из кэша показываем сразу — он уже оплачен, и прятать
	 * его за кнопкой незачем. Нет или протух — рисуем кнопку, которая грузит
	 * панель тем же proxy-запросом.
	 */
	protected function renderButtonPanel(IntegrationProvider $provider, string $panelId,
		array $descriptor, ?string $binding, string $classId): string
	{
		$model = $this->model;
		$cached = is_null($binding) ? null
			: PanelsCache::fetch($provider->id, $panelId, $binding, $this->compact);
		$fresh = $cached && $cached['age'] <= $provider->panelTtl($panelId, $model);
		if ($fresh && !$this->target) {
			return $this->renderPanel($provider, $panelId, $descriptor, $binding, $classId);
		}

		$containerId = $this->target
			?: 'integration-'.$provider->id.'-'.$panelId.'-'.$classId.'-'.$model->id;
		$title = $descriptor['title'] ?? $provider->getTitle();
		$url = Url::to(['/integrations/panel', 'provider' => $provider->id, 'panel' => $panelId,
			'class' => $classId, 'id' => $model->id]
			+ ($this->compact ? ['compact' => 1] : []));

		static::registerLoader($this->view);
		$button = Html::button(Html::encode($descriptor['button'] ?? 'Загрузить'), [
			'class' => 'btn btn-sm btn-secondary',
			'data-panel-url' => $url,
			'data-panel-target' => $containerId,
			'onclick' => 'integrationPanelLoad(this)',
		]);

		//панель целится в готовый блок карточки: выводим только кнопку, а
		//свежий результат сразу вкладываем в тот блок (он уже оплачен)
		if ($this->target) {
			return $button.(!$fresh ? '' : '<script>$(function(){$("#'.$containerId.'").html('
				.json_encode($cached['html']).');});</script>');
		}

		return ('<h4>'.Html::encode($title).'</h4>')
			.'<div class="'.($this->compact ? 'mb-2' : 'mb-3').'" id="'.$containerId.'">'
			.$button
			.'</div>';
	}

	/**
	 * Загрузчик панели по кнопке.
	 *
	 * Отдельной функцией, а не инлайном в onclick, ровно из-за обработки
	 * неудачи: опрос внешней ИС занимает секунды и вполне может не
	 * состояться, а спиннер, который крутится до конца времён, — худший из
	 * возможных ответов: человек не знает ни что случилось, ни как повторить.
	 * Поэтому кнопка возвращается в исходное состояние, а причина пишется
	 * прямо в контейнер панели.
	 */
	public static function registerLoader($view): void
	{
		$view->registerJs(<<<'JS'
window.integrationPanelLoad = function (button) {
	var element = $(button), label = element.html(),
		container = $('#' + element.data('panel-target'));

	element.prop('disabled', true)
		.html('<span class="spinner-border spinner-border-sm"></span> опрос…');

	$.ajax({url: element.data('panel-url'), timeout: 120000})
		.done(function (data) {
			container.html(data);
			//кнопка живёт вне контейнера (target-режим) и сама не обновится:
			//возвращаем подпись, иначе спиннер на ней крутится вечно
			element.prop('disabled', false).html(label);
		})
		.fail(function (xhr, status) {
			var reason = status === 'timeout'
				? 'опрос не уложился в 2 минуты'
				: (xhr.status ? 'сервер ответил ' + xhr.status : 'нет ответа от сервера');
			element.prop('disabled', false).html(label);
			container.prepend($('<div class="alert alert-warning py-1 px-2 small"></div>')
				.text('Опрос не состоялся: ' + reason + '. Можно повторить.'));
		});
};
JS
		, \yii\web\View::POS_END, 'integration-panel-load');
	}

	/**
	 * Один контейнер панели: кэш (приглушённый) либо спиннер + скрипт
	 * ajax-обновления, если кэш отсутствует/устарел
	 */
	protected function renderPanel(IntegrationProvider $provider, string $panelId, array $descriptor,
		?string $binding, string $classId): string
	{
		$model = $this->model;
		$containerId = 'integration-'.$provider->id.'-'.$panelId.'-'.$classId.'-'.$model->id;
		$title = $descriptor['title'] ?? $provider->getTitle();

		$cached = is_null($binding) ? null
			: PanelsCache::fetch($provider->id, $panelId, $binding, $this->compact);
		$fresh = $cached && $cached['age'] <= $provider->panelTtl($panelId, $model);

		$body = $cached ? $cached['html']
			: '<div class="spinner-border spinner-border-sm" role="status">'
				.'<span class="visually-hidden">Loading...</span></div>';

		/* карточка
		$html = '<div class="card mb-2 integration-panel">'
			.'<div class="card-header py-1"><small>'.Html::encode($title).'</small></div>'
			.'<div class="card-body py-2" id="'.$containerId.'"'
				.($fresh ? '' : ' style="opacity:.5"')
			.'>'.$body.'</div></div>';
		 */

		$html = ('<h4>'.Html::encode($title).'</h4>')
			.'<div class="'.($this->compact ? 'mb-2' : 'mb-3').'" id="'.$containerId.'"'
				.($fresh ? '' : ' style="opacity:.5"')
			.'>'.$body.'</div>';

		if (!$fresh) {
			$url = Url::to(['/integrations/panel', 'provider' => $provider->id, 'panel' => $panelId,
				'class' => $classId, 'id' => $model->id]
				+ ($this->compact ? ['compact' => 1] : []));
			$html .= '<script>$.get('.json_encode($url).', function(data) {'
				.'$("#'.$containerId.'").html(data).css("opacity","");'
				.'});</script>';
		}

		return $html;
	}
}
