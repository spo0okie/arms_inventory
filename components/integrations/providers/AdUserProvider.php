<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\models\base\ArmsModel;
use app\models\Users;
use Yii;

/**
 * Справка ActiveDirectory о пользователе (docs/dev/integrations.md):
 * панель в карточке сотрудника — OU, статус учётки
 * (активна/отключена/заблокирована), смена/истечение пароля, last logon.
 *
 * Транспорт — НЕ HTTP: LDAP-компонент приложения
 * (`Yii::$app->ldap`, app\components\ldap\LdapService поверх ldaprecord),
 * та же сервисная учётка, что и у аутентификации. Отдельный веб-сервис
 * из исходной идеи не понадобился.
 *
 * Конфиг (params-local.php):
 * ```php
 * 'integrations' => [
 *     'ad' => [
 *         'class' => \app\components\integrations\providers\AdUserProvider::class,
 *         //'cacheTtl' => 0, //сек; 0 = запрашивать AD при каждом открытии
 *     ],
 * ],
 * ```
 */
class AdUserProvider extends IntegrationProvider
{
	/** id единственной панели */
	const PANEL = 'account';

	public function getTitle(): string
	{
		return $this->config['title'] ?? 'Active Directory';
	}

	public function isConfigured(): bool
	{
		return Yii::$app->has('ldap');
	}

	public function appliesTo(ArmsModel $model): bool
	{
		return $model instanceof Users && !empty($model->Login);
	}

	public function binding(ArmsModel $model): ?string
	{
		/** @var Users $model */
		return empty($model->Login) ? null : mb_strtolower(trim($model->Login));
	}

	public function panels(ArmsModel $model): array
	{
		return [
			static::PANEL => [
				'title' => $this->getTitle(),
				//0 = обновлять при каждом открытии карточки: AD рядом и
				//отвечает быстро, а показывать устаревший статус учётки
				//(особенно сразу после сброса пароля) нельзя. Кэш-файл
				//остаётся - он нужен для мгновенной отрисовки и как
				//запасной вариант, если контроллер домена недоступен
				'ttl' => $this->config['cacheTtl'] ?? 0,
			],
		];
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		$account = $this->fetchAccount($this->binding($model));
		return $this->renderView('account', [
			'account' => $account,
			'model' => $model,
		]);
	}

	/**
	 * Атрибуты учётки из AD, нормализованные для рендера. Делегирует
	 * LdapService (единственная точка, знающая о LDAP-библиотеке).
	 * Вынесено в отдельный метод: тесты подменяют его, не трогая LDAP.
	 *
	 * @return array|null null = учётка не найдена; состав — см.
	 *   {@see \app\components\ldap\LdapService::accountInfo()}
	 * @throws \Throwable при недоступности LDAP (ловит ядро)
	 */
	protected function fetchAccount(string $login): ?array
	{
		return Yii::$app->ldap->accountInfo($login);
	}
}
