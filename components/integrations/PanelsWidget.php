<?php

namespace app\components\integrations;

use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Блок «Интеграции» в карточке объекта
 * (plans/integrations-contract.md §3.1, §7).
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

			$binding = $provider->binding($model);
			foreach ($provider->panels($model) as $panelId => $descriptor) {
				$panelsHtml .= $this->renderPanel($provider, $panelId, $descriptor, $binding, $classId);
			}

			foreach ($provider->actions($model) as $actionId => $descriptor) {
				if (($descriptor['showInPanel'] ?? true) === false) continue;
				if (!IntegrationsRegistry::userCanRun($provider, $actionId)) continue;
				$buttonsHtml .= Html::a(
					(empty($descriptor['icon']) ? '' : '<i class="'.$descriptor['icon'].'"></i> ')
						.Html::encode($descriptor['title'] ?? $actionId),
					IntegrationsRegistry::actionUrl($provider, $actionId, $descriptor, $model),
					['class' => 'btn btn-sm btn-outline-secondary me-2 open-in-modal-form']
				);
			}
		}

		if ($panelsHtml === '' && $buttonsHtml === '') return '';
		return '<div class="integrations-block">'
			.$panelsHtml
			.($buttonsHtml === '' ? '' : '<div class="integrations-actions mt-1 mb-2">'.$buttonsHtml.'</div>')
			.'</div>';
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

		$cached = is_null($binding) ? null : PanelsCache::fetch($provider->id, $panelId, $binding);
		$fresh = $cached && $cached['age'] <= $provider->panelTtl($panelId, $model);

		$body = $cached ? $cached['html']
			: '<div class="spinner-border spinner-border-sm" role="status">'
				.'<span class="visually-hidden">Loading...</span></div>';

		$html = '<div class="card mb-2 integration-panel">'
			.'<div class="card-header py-1"><small>'.Html::encode($title).'</small></div>'
			.'<div class="card-body py-2" id="'.$containerId.'"'
				.($fresh ? '' : ' style="opacity:.5"')
			.'>'.$body.'</div></div>';

		if (!$fresh) {
			$url = Url::to(['/integrations/panel', 'provider' => $provider->id, 'panel' => $panelId,
				'class' => $classId, 'id' => $model->id]);
			$html .= '<script>$.get('.json_encode($url).', function(data) {'
				.'$("#'.$containerId.'").html(data).css("opacity","");'
				.'});</script>';
		}

		return $html;
	}
}
