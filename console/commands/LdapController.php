<?php

namespace app\console\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Диагностика LDAP/AD (app\components\ldap\LdapService поверх ldaprecord).
 * Для проверки миграции и настройки инстанса против живого контроллера
 * домена — юнит-тесты AD не ходят.
 *
 * Использование:
 *   yii ldap/ping                     проверить доступность DC и bind сервисной учётки
 *   yii ldap/account <login>          показать справку об учётке AD (как в панели)
 *   yii ldap/auth <login> <password>  проверить логин/пароль (bind под пользователем)
 */
class LdapController extends Controller
{
	/**
	 * Проверка доступности контроллера домена и bind сервисной учётки.
	 * @return int
	 */
	public function actionPing()
	{
		if (!Yii::$app->has('ldap')) {
			$this->stderr("Компонент 'ldap' не настроен\n");
			return ExitCode::CONFIG;
		}
		try {
			$ok = Yii::$app->ldap->ping();
			$this->stdout($ok ? "OK: DC доступен, bind сервисной учётки прошёл\n" : "FAIL: не подключились\n");
			return $ok ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
		} catch (\Throwable $e) {
			$this->stderr('ERROR: '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
	}

	/**
	 * Справка об учётке AD (те же данные, что в панели AdUserProvider).
	 * @param string $login sAMAccountName
	 * @return int
	 */
	public function actionAccount($login)
	{
		try {
			$info = Yii::$app->ldap->accountInfo($login);
		} catch (\Throwable $e) {
			$this->stderr('ERROR: '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		if (is_null($info)) {
			$this->stdout("Учётка '$login' не найдена в AD\n");
			return ExitCode::OK;
		}
		foreach ($info as $key => $value) {
			if (is_bool($value)) $value = $value ? 'да' : 'нет';
			if (is_int($value) && in_array($key, ['password_last_set', 'password_expires', 'last_logon', 'account_expires'], true)) {
				$value = date('Y-m-d H:i:s', $value)." ($value)";
			}
			$this->stdout(str_pad($key, 22).': '.$value."\n");
		}
		return ExitCode::OK;
	}

	/**
	 * Проверка логина/пароля bind'ом под учёткой пользователя.
	 * @param string $login
	 * @param string $password
	 * @return int
	 */
	public function actionAuth($login, $password)
	{
		try {
			$ok = Yii::$app->ldap->authenticate($login, $password);
		} catch (\Throwable $e) {
			$this->stderr('ERROR (служба недоступна): '.$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$this->stdout($ok ? "OK: креды верны\n" : "FAIL: неверный логин/пароль\n");
		return $ok ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
	}

	/**
	 * НЕДЕСТРУКТИВНАЯ проверка: может ли исполнитель сбросить пароль цели
	 * (валидность кредов + право по allowedAttributesEffective). В AD
	 * ничего не пишет. Та же проверка, что делает сброс пароля шагом 0.
	 * @param string $targetLogin чью учётку проверяем
	 * @param string $execLogin логин исполнителя
	 * @param string $execPassword пароль исполнителя
	 * @return int
	 */
	public function actionCanReset($targetLogin, $execLogin, $execPassword)
	{
		try {
			Yii::$app->ldap->verifyResetPermission($targetLogin, $execLogin, $execPassword);
		} catch (\Throwable $e) {
			$this->stdout("FAIL: ".$e->getMessage()."\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$this->stdout("OK: креды верны и есть право сбросить пароль '$targetLogin'\n");
		return ExitCode::OK;
	}
}
