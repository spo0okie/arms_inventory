<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\models\base\ArmsModel;
use app\models\Comps;
use Yii;

/**
 * Справка ActiveDirectory о компьютере (docs/dev/integrations.md):
 * панель в карточке ОС — путь учётной записи компьютера в дереве AD (DN)
 * и группы, в которых он состоит (по ним раздаются политики и доступы).
 *
 * Транспорт — LDAP-компонент приложения (`Yii::$app->ldap`), та же
 * сервисная учётка, что и у справки о пользователе {@see AdUserProvider}.
 *
 * Конфиг (params-local.php):
 * ```php
 * 'integrations' => [
 *     'ad-comp' => [
 *         'class' => \app\components\integrations\providers\AdComputerProvider::class,
 *         //'windowsOnly' => false, //опрашивать и не-Windows ОС
 *         //'cacheTtl' => 0, //сек; 0 = запрашивать AD при каждом открытии
 *     ],
 * ],
 * ```
 */
class AdComputerProvider extends IntegrationProvider
{
	/** id единственной панели */
	const PANEL = 'computer';

	public function getTitle(): string
	{
		return $this->config['title'] ?? 'Active Directory';
	}

	public function isConfigured(): bool
	{
		return Yii::$app->has('ldap');
	}

	/**
	 * ОС с именем. По умолчанию только Windows: у остальных учётки
	 * компьютера в AD обычно нет, и каждая карточка давала бы напрасный
	 * запрос к контроллеру домена (проверка дешёвая - по полю os).
	 */
	public function appliesTo(ArmsModel $model): bool
	{
		if (!$model instanceof Comps || empty($model->name)) return false;
		return ($this->config['windowsOnly'] ?? true) ? $model->isWindows : true;
	}

	public function binding(ArmsModel $model): ?string
	{
		/** @var Comps $model */
		return empty($model->name) ? null : mb_strtolower(trim($model->name));
	}

	public function panels(ArmsModel $model): array
	{
		return [
			static::PANEL => [
				'title' => $this->getTitle(),
				//0 = обновлять при каждом открытии карточки: перемещение по
				//OU и смена групп меняют политики, устаревшие данные вводят
				//в заблуждение (см. AdUserProvider)
				'ttl' => $this->config['cacheTtl'] ?? 0,
			],
		];
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		return $this->renderView('computer', [
			'computer' => $this->fetchComputer($this->binding($model)),
			'model' => $model,
		]);
	}

	/**
	 * Атрибуты учётки компьютера, нормализованные для рендера. Делегирует
	 * LdapService (единственная точка, знающая о LDAP-библиотеке).
	 * Вынесено в отдельный метод: тесты подменяют его, не трогая AD.
	 *
	 * @return array|null null = компьютер не найден; состав — см.
	 *   {@see \app\components\ldap\LdapService::computerInfo()}
	 * @throws \Throwable при недоступности LDAP (ловит ядро)
	 */
	protected function fetchComputer(string $name): ?array
	{
		return Yii::$app->ldap->computerInfo($name);
	}
}
