<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\models\Comps;
use app\models\base\ArmsModel;
use app\models\Techs;

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
 * В Zabbix панель не ходит вовсе; в инвентори explain.php делает один GET.
 * Ничего никуда не пишет.
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
		$report = $this->fetchExplain($this->explainClass($model), (int)$model->id);
		return $this->renderView('verdict', [
			'report' => $report,
			'model' => $model,
			'provider' => $this,
		]);
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
