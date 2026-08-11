<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\models\base\ArmsModel;
use Yii;

/**
 * Декларативный HTTP-провайдер (docs/dev/integrations.md):
 * интеграция уровня «одна панель по одному GET-запросу» описывается целиком
 * конфигом, без написания класса. Всё сложнее (логин-флоу, несколько
 * запросов, действия) — полноценный класс-наследник IntegrationProvider.
 *
 * Конфиг (params-local.php), пример — панель телефонии ast22-phones:
 * ```php
 * 'integrations' => [
 *     'pbx' => [
 *         'class' => \app\components\integrations\providers\HttpTemplateProvider::class,
 *         'title' => 'Телефония',
 *         //применимость: класс модели + булев атрибут-условие (опционально)
 *         'appliesTo' => ['model' => \app\models\Techs::class, 'attribute' => 'isVoipPhone'],
 *         //ключ привязки: шаблон из атрибутов модели
 *         'binding' => '{phone}',
 *         //запрос: {binding} + любые {атрибуты} модели
 *         'request' => 'https://phones.local/api/v1/subscribers/status?extension={binding}',
 *         'headers' => ['Authorization' => 'Bearer <token сервисной учетки>'],
 *         //панель: заголовок, ttl кэша и view-файл рендера ответа
 *         'panel' => [
 *             'title' => 'Телефония',
 *             'ttl' => 30,
 *             'template' => '@app/components/integrations/providers/views/pbx/status.php',
 *         ],
 *     ],
 * ],
 * ```
 *
 * View-файл рендера получает: $data (JSON-ответ массивом), $model, $provider.
 * Без template ответ рендерится плоским списком ключ-значение.
 */
class HttpTemplateProvider extends IntegrationProvider
{
	/** id единственной панели провайдера */
	const PANEL = 'main';

	public function getTitle(): string
	{
		return $this->config['title'] ?? $this->id;
	}

	public function isConfigured(): bool
	{
		return !empty($this->config['request'])
			&& !empty($this->config['appliesTo']['model'])
			&& class_exists($this->config['appliesTo']['model'])
			&& !empty($this->config['panel']);
	}

	public function appliesTo(ArmsModel $model): bool
	{
		$class = $this->config['appliesTo']['model'] ?? null;
		if (!$class || !$model instanceof $class) return false;

		$attribute = $this->config['appliesTo']['attribute'] ?? null;
		if ($attribute && empty($model->$attribute)) return false;

		return true;
	}

	public function binding(ArmsModel $model): ?string
	{
		$binding = trim($this->substitute($this->config['binding'] ?? '', $model));
		return $binding === '' ? null : $binding;
	}

	public function panels(ArmsModel $model): array
	{
		$panel = $this->config['panel'] ?? [];
		return [
			static::PANEL => [
				'title' => $panel['title'] ?? $this->getTitle(),
				'ttl' => $panel['ttl'] ?? $this->config['cacheTtl'] ?? 60,
			],
		];
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		$binding = $this->binding($model);
		if (is_null($binding)) {
			return '<span class="text-secondary opacity-75">нет привязки (не заполнен атрибут)</span>';
		}

		$data = $this->fetch($binding, $model);

		$template = $this->config['panel']['template'] ?? null;
		if ($template) {
			return Yii::$app->view->renderFile(Yii::getAlias($template), [
				'data' => $data,
				'model' => $model,
				'provider' => $this,
			]);
		}
		return $this->renderFlat($data);
	}

	/**
	 * GET-запрос по шаблону конфига
	 * @return array декодированный JSON-ответ
	 * @throws \RuntimeException при недоступности/некорректном ответе
	 *   (ловит ядро — «панель недоступна»)
	 */
	protected function fetch(string $binding, ArmsModel $model): array
	{
		$url = str_replace('{binding}', urlencode($binding),
			$this->substitute($this->config['request'], $model, true));

		$headers = '';
		foreach ($this->config['headers'] ?? [] as $name => $value) {
			$headers .= "$name: $value\r\n";
		}

		$context = stream_context_create([
			'http' => [
				'timeout' => $this->timeout(),
				'header' => $headers,
				'ignore_errors' => true,
			],
			'ssl' => [
				'verify_peer' => $this->config['verifySsl'] ?? true,
				'verify_peer_name' => $this->config['verifySsl'] ?? true,
			],
		]);

		$response = @file_get_contents($url, false, $context);
		if ($response === false) throw new \RuntimeException("Request failed: $url");

		$data = json_decode($response, true);
		if (!is_array($data)) throw new \RuntimeException('Response is not a valid JSON object');
		return $data;
	}

	/** Подстановка {атрибутов} модели в шаблон */
	protected function substitute(string $template, ArmsModel $model, bool $urlencode = false): string
	{
		return preg_replace_callback('/{(\w+)}/', function ($matches) use ($model, $urlencode) {
			$attribute = $matches[1];
			if ($attribute === 'binding') return $matches[0]; //подставляется отдельно
			$value = $model->canGetProperty($attribute) ? (string)$model->$attribute : '';
			return $urlencode ? urlencode($value) : $value;
		}, $template);
	}

	/** Рендер ответа без template: плоский список ключ-значение */
	protected function renderFlat(array $data, string $prefix = ''): string
	{
		$html = '';
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$html .= $this->renderFlat($value, $prefix.$key.'.');
				continue;
			}
			if (is_bool($value)) $value = $value ? 'да' : 'нет';
			$html .= '<div><small class="text-secondary">'
				.htmlspecialchars($prefix.$key).':</small> '
				.htmlspecialchars((string)$value).'</div>';
		}
		return $html;
	}
}
