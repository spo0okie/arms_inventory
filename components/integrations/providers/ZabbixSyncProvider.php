<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\components\integrations\IntegrationsRegistry;
use app\models\Comps;
use app\models\base\ArmsModel;
use app\models\Techs;
use Yii;
use yii\helpers\Html;

/**
 * Панель «Постановка на мониторинг» в карточке ОС/оборудования:
 * попадёт ли узел в Zabbix при синхронизации arms.zabbix и в каком виде.
 *
 * Источник данных — explain-режим скрипта синхронизации
 * ([arms.zabbix](https://github.com/spo0okie/arms_zabbix), `explain.php`):
 * он прогоняет ОДИН узел через конвейер правил (rules.priv.php) без
 * подключения к Zabbix и без bulk-загрузок, поэтому отвечает за доли
 * секунды. Панель показывает вердикт и совпавшие правила; полный журнал
 * (все проверенные правила и на каком условии срезались) — по клику
 * «подробно». Имена наборов/правил приходят из rules.priv.php, если
 * там заданы (иначе set#N/rule#M).
 *
 * В Zabbix панель сама не ходит вовсе; в инвентори explain.php делает
 * один GET. Ничего никуда не пишет.
 *
 * Композиция с провайдером zabbix: если в конфиге провайдера zabbix
 * выставлен 'embedded' => true, его отдельная карточка исчезает, а живое
 * состояние узла (проблемы/метрики/ссылки) рисуется здесь же, под
 * вердиктом — когда узел на мониторинге или реально заведён в Zabbix
 * (есть привязка hostid). Одна карточка вместо двух и никакого
 * «узел не найден» у узлов, которым в Zabbix быть не положено.
 *
 * Конфиг (params-local.php), ключ обязан быть 'zabbix-sync' (от него
 * зависит путь view-файлов):
 * ```php
 * 'integrations' => [
 *     'zabbix-sync' => [
 *         'class' => \app\components\integrations\providers\ZabbixSyncProvider::class,
 *         'url' => 'https://synchost/arms.zabbix/explain.php',
 *         'token' => '<$explainToken из config.priv.php скрипта синхронизации>',
 *         //'title' => 'Постановка на мониторинг',
 *         //'cacheTtl' => 0, //0 = обновлять при каждом открытии карточки (кэш - буфер отрисовки)
 *         //'timeout' => 5,
 *         //'zabbix' => 'zabbix', //id провайдера Zabbix для встраивания
 *         //  (само встраивание включает 'embedded' => true в ЕГО конфиге)
 *     ],
 * ],
 * ```
 */
class ZabbixSyncProvider extends IntegrationProvider
{
	const PANEL = 'verdict';

	/** тексты и стили бейджа по вердиктам explain.php */
	const VERDICTS = [
		'add'         => ['bg-success', 'будет добавлен в мониторинг'],
		'monitored'   => ['bg-success', 'на мониторинге'],
		'update-only' => ['bg-secondary', 'не будет добавлен'],
		'declined'    => ['bg-secondary', 'не ставится на мониторинг'],
		'skip'        => ['bg-secondary', 'не ставится на мониторинг'],
	];

	public function getTitle(): string
	{
		return $this->config['title'] ?? 'Постановка на мониторинг';
	}

	public function isConfigured(): bool
	{
		return !empty($this->config['url']) && !empty($this->config['token']);
	}

	public function appliesTo(ArmsModel $model): bool
	{
		return $model instanceof Comps || $model instanceof Techs;
	}

	/**
	 * Ключ привязки — id самого объекта в инвентори: explain-режим ищет
	 * узел ровно по нему, отдельная привязка не нужна
	 */
	public function binding(ArmsModel $model): ?string
	{
		return $model->id ? $this->explainClass($model).'/'.$model->id : null;
	}

	public function panels(ArmsModel $model): array
	{
		return [
			static::PANEL => [
				'title' => $this->getTitle(),
				//вердикт зависит только от данных инвентори и правил - дешёвый,
				//поэтому по умолчанию обновляем при каждом открытии карточки
				'ttl' => $this->config['cacheTtl'] ?? 0,
			],
		];
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		$zabbix = $this->embeddedZabbix();

		try {
			$report = $this->fetchExplain($this->explainClass($model), (int)$model->id);
		} catch (\Throwable $e) {
			//без встроенной Zabbix-части падение explain роняет панель целиком
			//(ядро покажет заглушку); с ней половины деградируют независимо:
			//живое состояние узла ценно и без вердикта
			if (!$zabbix || !$this->shouldEmbedZabbix(null, $model, $zabbix)) throw $e;
			return $this->unavailableNote($this->getTitle(), $e)
				.$this->renderEmbedded($zabbix, $model);
		}

		$html = $this->renderView('verdict', [
			'report' => $report,
			'model' => $model,
			'provider' => $this,
		]);
		if ($zabbix && $this->shouldEmbedZabbix($report, $model, $zabbix)) {
			$html .= $this->renderEmbedded($zabbix, $model);
		}
		return $html;
	}

	/**
	 * Провайдер Zabbix, чью панель встраиваем под вердиктом (композиция,
	 * расширение §2.2 контракта на панели). Включается одним
	 * переключателем — 'embedded' => true в конфиге провайдера zabbix:
	 * он же прячет его отдельную карточку, чтобы содержимое не двоилось.
	 * Id провайдера можно переопределить ключом 'zabbix' своего конфига.
	 */
	protected function embeddedZabbix(): ?ZabbixProvider
	{
		$provider = IntegrationsRegistry::provider($this->config['zabbix'] ?? 'zabbix');
		return ($provider instanceof ZabbixProvider && $provider->isEmbedded()) ? $provider : null;
	}

	/**
	 * Рисовать ли Zabbix-блок: узел на мониторинге по вердикту либо реально
	 * заведён в Zabbix — есть привязка hostid (в т.ч. заведён вручную, мимо
	 * правил синка). При вердикте add узла ещё нет — бейдж говорит сам за
	 * себя; при негативных вердиктах без привязки показывать нечего (и
	 * пропадает дублирующее «узел не найден в Zabbix»).
	 * @param array|null $report отчёт explain (null - не получен)
	 */
	protected function shouldEmbedZabbix(?array $report, ArmsModel $model, ZabbixProvider $zabbix): bool
	{
		if (!$zabbix->appliesTo($model)) return false;
		if (($report['verdict'] ?? null) === 'monitored') return true;
		return !is_null($zabbix->binding($model));
	}

	/**
	 * Zabbix-блок под вердиктом. Ошибки ловятся здесь: упавший Zabbix не
	 * прячет вердикт (и наоборот — см. renderPanel)
	 */
	protected function renderEmbedded(ZabbixProvider $zabbix, ArmsModel $model): string
	{
		$zabbix->compact = $this->compact;
		try {
			$html = $zabbix->renderPanel(ZabbixProvider::PANEL, $model);
		} catch (\Throwable $e) {
			$html = $this->unavailableNote($zabbix->getTitle(), $e);
		}
		return '<div class="mt-1">'.$html.'</div>';
	}

	/**
	 * Заглушка недоступной половины панели — как у ядра (§3.1): в debug с
	 * причиной, на проде нейтральная; детали всегда в логе
	 */
	protected function unavailableNote(string $title, \Throwable $e): string
	{
		Yii::warning("Integration panel {$this->id}: '$title' failed: ".$e->getMessage(), __METHOD__);
		return '<div class="text-secondary opacity-75">'
			.Html::encode($title.(YII_DEBUG ? ': '.$e->getMessage() : ': недоступно'))
			.'</div>';
	}

	/** Класс узла в терминах инвентори/синхронизации */
	public function explainClass(ArmsModel $model): string
	{
		return $model instanceof Comps ? 'comps' : 'techs';
	}

	/**
	 * Запрос к explain.php скрипта синхронизации.
	 * @return array отчет explain (verdict/errors/status/sets/actions)
	 * @throws \RuntimeException при ошибке транспорта/ответа (ловит ядро);
	 *   в сообщение попадает HTTP-код и начало не-JSON ответа — иначе по
	 *   заглушке панели не понять, кто ответил (Apache 403, HTML и т.п.)
	 */
	protected function fetchExplain(string $class, int $id): array
	{
		$url = $this->config['url']
			.'?class='.urlencode($class)
			.'&id='.$id
			.'&token='.urlencode($this->config['token']);

		[$response, $status] = $this->httpGet($url);

		$data = json_decode($response, true);
		if (!is_array($data)) {
			//не-JSON: скорее всего ответил не explain.php, а веб-сервер
			//(403 по Require ip, 404 по Alias, HTML-заглушка прокси...)
			$snippet = trim(mb_substr(preg_replace('/\s+/', ' ', strip_tags($response)), 0, 160));
			throw new \RuntimeException(
				"Некорректный ответ сервиса синхронизации (HTTP $status): ".($snippet ?: 'пустой ответ'));
		}
		if (isset($data['error'])) throw new \RuntimeException('Сервис синхронизации: '.$data['error']);
		if (!isset($data['verdict'])) throw new \RuntimeException("Некорректный ответ сервиса синхронизации (HTTP $status): нет verdict");
		return $data;
	}

	/**
	 * HTTP GET. Вынесен отдельным методом: тесты подменяют его, не трогая сеть.
	 * @return array [string тело, int HTTP-код (0 если не распознан)]
	 * @throws \RuntimeException при ошибке транспорта (ловит ядро)
	 */
	protected function httpGet(string $url): array
	{
		$context = stream_context_create([
			'http' => [
				'timeout' => $this->timeout(),
				'ignore_errors' => true, //читать тело и при 4xx/5xx (там JSON с error)
			],
			'ssl' => [
				'verify_peer' => $this->config['verifySsl'] ?? false,
				'verify_peer_name' => $this->config['verifySsl'] ?? false,
			],
		]);

		$response = @file_get_contents($url, false, $context);
		if ($response === false) throw new \RuntimeException('Сервис синхронизации Zabbix недоступен');

		$status = 0;
		if (isset($http_response_header[0]) && preg_match('#^HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
			$status = (int)$m[1];
		}
		return [$response, $status];
	}
}
