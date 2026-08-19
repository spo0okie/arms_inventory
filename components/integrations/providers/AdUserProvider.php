<?php

namespace app\components\integrations\providers;

use app\components\integrations\ActionResult;
use app\components\integrations\IntegrationProvider;
use app\components\integrations\IntegrationsRegistry;
use app\components\ldap\LdapService;
use app\components\PronounceablePasswordGenerator;
use app\helpers\ArrayHelper;
use app\helpers\FieldsHelper;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use app\models\Users;
use Yii;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Интеграция с учёткой сотрудника в ActiveDirectory (docs/dev/integrations.md):
 * панель-справка + именные действия с учёткой (сброс пароля, создание,
 * восстановление после увольнения).
 *
 * Панель (L1) в карточке сотрудника — OU, статус учётки
 * (активна/отключена/заблокирована), смена/истечение пароля, last logon.
 *
 * Все действия — L2+ (именные): выполняются от имени ЛИЧНОЙ учётки
 * исполнителя, введённой в форме действия (ядро запрашивает её само);
 * нужные делегированные права в AD — у самой учётки исполнителя
 * (Reset Password; Create user objects на OU; Write members на группах).
 * Сервисной учёткой записи не выполняются — ИБ видит в логах AD живого
 * исполнителя. Все действия появляются при настроенном SMS-провайдере
 * (зависимость конфигом, §5 композиция) и следуют единому канону
 * «пароль доставляется до записи»:
 *  0. НЕДЕСТРУКТИВНО проверить креды исполнителя и его права (bind +
 *     чтение конструируемых атрибутов, без записи в AD) — чтобы не
 *     отправить SMS впустую;
 *  1. сгенерировать пароль (по умолчанию «произносимый» — проще
 *     продиктовать), соответствующий парольной политике;
 *  2. отправить его пользователю через SMS-провайдера; неудача = останов,
 *     запись в AD не выполняется — пользователь не останется с паролем,
 *     который ему не доставлен;
 *  3. записать в AD от имени исполнителя. Пароль не попадает ни в журнал,
 *     ни в ответ исполнителю — его знает только пользователь (поэтому и
 *     «требовать смену» не нужно).
 *
 * Действия:
 * - «сброс пароля» (reset-password) — новый пароль + опц. разблокировка;
 * - «создание учётки» (create-account, требует usersOu) — для активного
 *   сотрудника без учётки в AD: форма с логином (предгенерируется
 *   транслитом «фамилия.и», {@see suggestLogin()}), деревом OU под
 *   usersOu и мультиселектом групп; после создания логин записывается
 *   в карточку сотрудника;
 * - «восстановление учётки» (restore-account, требует usersOu+dismissedOu) —
 *   разворачивает увольнение скриптом usr_dismiss.ps1 (тот переносит
 *   учётку в зеркальный подпуть под корнем уволенных, отключает и рандомит
 *   пароль): включение + новый пароль + переезд обратно; предлагается
 *   только когда сотрудник в инвентаризации активен, а учётка отключена
 *   и лежит в контейнере уволенных.
 *
 * Кнопки действий рендерятся внутри панели (showInPanel=false) — по
 * живому состоянию учётки в AD: сброс — у найденной активной, создание —
 * у ненайденной, восстановление — у уволенной. Кнопка — L0-ссылка,
 * одинаковая для всех (кэш панелей общий на инстанс); доступ к самому
 * действию проверяет сервер при открытии формы.
 *
 * Транспорт — НЕ HTTP: LDAP-компонент приложения
 * (`Yii::$app->ldap`, app\components\ldap\LdapService поверх ldaprecord),
 * та же сервисная учётка, что и у аутентификации; запись — отдельным
 * соединением под кредами исполнителя (требуется LDAPS/TLS).
 *
 * Конфиг (params-local.php):
 * ```php
 * 'integrations' => [
 *     'ad' => [
 *         'class' => \app\components\integrations\providers\AdUserProvider::class,
 *         //'cacheTtl' => 0,       //сек; 0 = запрашивать AD при каждом открытии
 *         //действия с паролем (появляются при включённом SMS-провайдере):
 *         //'sms' => 'sms',        //id SMS-провайдера для отправки пароля
 *         //'defaultLength' => 12, //длина пароля по умолчанию в форме
 *         //'smsText' => 'Ваш новый пароль: {password}',
 *         //создание и восстановление учёток - пары корней «рабочий ↔
 *         //уволенные», строго как у скрипта увольнения ($inventory2ad_sync):
 *         //'ouPairs' => [
 *         //    ['users' => 'OU=Пользователи,DC=corp,DC=local',
 *         //     'dismissed' => 'OU=Азимут,OU=Уволенные,DC=corp,DC=local'],
 *         //    ['users' => 'OU=External,DC=corp,DC=local',
 *         //     'dismissed' => 'OU=External,OU=Уволенные,DC=corp,DC=local'],
 *         //],
 *         //одна пара может быть задана и legacy-скалярами:
 *         //'usersOu' => 'OU=Пользователи,DC=corp,DC=local', //корень рабочих учёток (включает создание)
 *         //'dismissedOu' => 'OU=Уволенные,DC=corp,DC=local', //корень уволенных (включает восстановление)
 *         //'groupsOu' => 'OU=Группы,DC=corp,DC=local', //где искать группы для формы (null = весь каталог)
 *         //'defaultGroups' => ['Пользователи JIRA'], //предвыбранные группы (имена или DN)
 *         //'upnSuffix' => null,   //суффикс UPN (null = account_suffix ldap-компонента)
 *     ],
 * ],
 * ```
 */
class AdUserProvider extends IntegrationProvider
{
	/** id единственной панели */
	const PANEL = 'account';

	/** id действия сброса пароля */
	const ACTION = 'reset-password';

	/** id действия создания учётки */
	const ACTION_CREATE = 'create-account';

	/** id действия восстановления уволенной учётки */
	const ACTION_RESTORE = 'restore-account';

	/**
	 * максимальная длина логина - ограничение SAP (регламент сквозных
	 * учёток: логин не длиннее 12 знаков; жёстче лимита sAMAccountName в 20)
	 */
	const LOGIN_MAX = 12;

	/** символы случайного генератора (без визуально неоднозначных 0O1lI) */
	const RND_UPPER = 'ABCDEFGHJKMNPQRSTUVWXYZ';
	const RND_LOWER = 'abcdefghijkmnpqrstuvwxyz';
	const RND_DIGIT = '23456789';
	const RND_SPECIAL = '!@#$%*()-_+=?';

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
		if (!($model instanceof Users)) return false;
		//с логином - всегда (панель-справка); без логина - только когда
		//настроено создание учёток и сотрудник не уволен (иначе панель
		//«учётки нет» без единого действия - бессмысленный шум)
		return !empty($model->Login)
			|| ($this->createConfigured() && !$model->getIsArchived());
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
		/** @var Users $model */
		$login = $this->binding($model);
		$account = is_null($login) ? null : $this->fetchAccount($login);
		$actions = $this->actions($model);

		//«уволенная» учётка: отключена И лежит в контейнере уволенных
		//одной из пар (usr_dismiss.ps1); отключённой другим способом
		//восстановление не предлагается - причина отключения неизвестна
		$dismissedPair = $account ? $this->dismissedPairFor($account['dn']) : null;
		$dismissed = $account && !$account['enabled'] && $dismissedPair !== null;

		//кнопки действий внутри панели: L0-ссылки, одинаковые для всех
		//(кэш панелей общий на инстанс), доступ проверяет сервер при
		//открытии формы. Какая кнопка - решает живое состояние учётки.
		$resetUrl = ($account && !$dismissed && isset($actions[static::ACTION]))
			? Url::to(IntegrationsRegistry::actionUrl($this, static::ACTION, $actions[static::ACTION], $model))
			: null;

		$createUrl = null;
		if (!$account && !$model->getIsArchived() && isset($actions[static::ACTION_CREATE])) {
			$createUrl = Url::to(IntegrationsRegistry::actionUrl(
				$this, static::ACTION_CREATE, $actions[static::ACTION_CREATE], $model));
		}

		$restoreUrl = null;
		if ($dismissed && !$model->getIsArchived() && isset($actions[static::ACTION_RESTORE])) {
			$descriptor = $actions[static::ACTION_RESTORE];
			//целевое OU - строгое зеркало пути увольнения В РАМКАХ СВОЕЙ
			//пары: увольнение переносило учётку users-корень -> dismissed-
			//корень с сохранением подпути, восстановление - ровно обратно
			$target = LdapService::relocateDn(LdapService::parentDn($account['dn']),
				$dismissedPair['dismissed'], $dismissedPair['users']);
			if ($target) $descriptor['prefill']['ou'] = $target;
			$restoreUrl = Url::to(IntegrationsRegistry::actionUrl(
				$this, static::ACTION_RESTORE, $descriptor, $model));
		}

		return $this->renderView('account', [
			'account' => $account,
			'model' => $model,
			'dismissed' => $dismissed,
			'resetUrl' => $resetUrl,
			'createUrl' => $createUrl,
			'restoreUrl' => $restoreUrl,
		]);
	}

	public function actions(?ArmsModel $model): array
	{
		//зависимость действий от SMS-провайдера объявлена конфигом (§5);
		//проверяем по сырым params, не через реестр - реестр в момент
		//вызова ещё может строиться. Нет SMS = панель без действий
		if (!$this->smsConfigured()) return [];

		//длина по умолчанию из конфига инстанса (если задана)
		$passwordPrefill = ['length' => (int)($this->config['defaultLength'] ?? AdPasswordResetForm::MIN_LENGTH)];

		$actions = [
			static::ACTION => [
				'title' => 'Сбросить пароль в AD',
				'icon' => 'fas fa-key',
				'level' => static::LEVEL_PERSONAL,
				'form' => AdPasswordResetForm::class,
				'prefill' => $passwordPrefill,
				//кнопки живут внутри панели (по живому состоянию учётки),
				//в общем блоке кнопок интеграций не дублируются
				'showInPanel' => false,
			],
		];

		if (count($this->usersOus())) {
			$actions[static::ACTION_CREATE] = [
				'title' => 'Создать учётную запись в AD',
				'icon' => 'fas fa-user-plus',
				'level' => static::LEVEL_PERSONAL,
				'form' => AdCreateAccountForm::class,
				'prefill' => $passwordPrefill
					//предложение логина дешёвое (транслит, без похода в AD -
					//appliesTo/actions вызываются при рендере карточек);
					//занятость проверяет рендер формы
					+ ($model instanceof Users ? ['login' => static::suggestLogin($model)] : []),
				'showInPanel' => false,
			];
			if ($this->restoreConfigured()) {
				$actions[static::ACTION_RESTORE] = [
					'title' => 'Восстановить учётную запись в AD',
					'icon' => 'fas fa-user-check',
					'level' => static::LEVEL_PERSONAL,
					'form' => AdRestoreAccountForm::class,
					'prefill' => $passwordPrefill,
					'showInPanel' => false,
				];
			}
		}

		return $actions;
	}

	/**
	 * @param AdPasswordResetForm|AdCreateAccountForm|AdRestoreAccountForm|Model $form
	 */
	public function runAction(string $actionId, ?ArmsModel $model, Model $form, ?array $credentials): ActionResult
	{
		if (!($model instanceof Users)) {
			return ActionResult::error('Действие применимо только к сотруднику');
		}
		//L2+ гарантируется контроллером, но серверный вызов мог прийти без кредов
		if (empty($credentials['login']) || empty($credentials['password'])) {
			return ActionResult::error('Требуются учетные данные исполнителя в AD');
		}

		switch ($actionId) {
			case static::ACTION:
				return $this->runResetAction($model, $form, $credentials);
			case static::ACTION_CREATE:
				return $this->runCreateAction($model, $form, $credentials);
			case static::ACTION_RESTORE:
				return $this->runRestoreAction($model, $form, $credentials);
		}
		return parent::runAction($actionId, $model, $form, $credentials);
	}

	// ==================== сброс пароля ====================

	/**
	 * @param AdPasswordResetForm|Model $form
	 */
	protected function runResetAction(Users $model, Model $form, array $credentials): ActionResult
	{
		if (empty($model->Login)) {
			return ActionResult::error('Действие применимо только к сотруднику с логином AD');
		}

		$targetLogin = $this->binding($model);
		$unlock = (bool)$form->unlock;
		//пароль в журнал НЕ пишем - только характеристики действия
		$logParams = ['login' => $targetLogin, 'pronounceable' => (bool)$form->pronounceable,
			'length' => max(AdPasswordResetForm::MIN_LENGTH, (int)$form->length), 'unlock' => $unlock];

		$phone = $this->deliveryPhone($model);
		if ($phone === '') {
			return ActionResult::error('У сотрудника не заполнен мобильный/личный телефон - пароль некуда отправить', $logParams);
		}

		//шаг 0: НЕДЕСТРУКТИВНАЯ предпроверка ДО SMS - валидны ли креды
		//исполнителя и есть ли у него право сброса. Снимает сценарии
		//«SMS ушло, а пароль не сменился». Без записи в AD (bind + чтение).
		try {
			$this->ldapVerify($targetLogin, $credentials);
		} catch (\Throwable $e) {
			return ActionResult::error(
				'Сброс не выполнен (SMS не отправлено): '.$e->getMessage(),
				$logParams
			);
		}

		$password = $this->generatePassword($form);

		//шаг 1: SMS с паролем ДО записи в AD; неудача = останов
		$sms = $this->sendPasswordSms($model, $phone, $password, $targetLogin);
		if (!$sms->ok) {
			return ActionResult::error('Пароль НЕ изменён: не удалось отправить SMS ('.$sms->message.')', $logParams);
		}

		//шаг 2: запись пароля в AD от имени исполнителя (+ опц. разблокировка)
		try {
			$adInfo = $this->ldapResetPassword($targetLogin, $password, $unlock, $credentials);
		} catch (\Throwable $e) {
			Yii::warning("AD password reset for $targetLogin failed: ".$e->getMessage(), __METHOD__);
			$result = ActionResult::error(
				'SMS отправлено, но пароль в AD НЕ изменён: '.$e->getMessage()
				.'. Повторите сброс - пользователю придет новое SMS.',
				$logParams
			);
			$result->html = $this->renderReport($model, $phone, $targetLogin, $credentials['login'],
				$sms, null, $e->getMessage(), $unlock);
			return $result;
		}

		$result = ActionResult::success(
			"Пароль сброшен и отправлен по SMS на $phone"
			.($unlock ? ', учётка разблокирована' : '')
			.'. Пароль знает только пользователь.',
			$logParams
		);
		$result->html = $this->renderReport($model, $phone, $targetLogin, $credentials['login'],
			$sms, $adInfo, null, $unlock);
		return $result;
	}

	// ==================== создание учётки ====================

	/**
	 * @param AdCreateAccountForm|Model $form
	 */
	protected function runCreateAction(Users $model, Model $form, array $credentials): ActionResult
	{
		$login = mb_strtolower(trim((string)$form->login));
		$ou = trim((string)$form->ou);
		$groups = array_values(array_filter(array_map('trim', (array)$form->groups)));
		$logParams = ['login' => $login, 'ou' => $ou, 'groups' => $groups];

		if ($model->getIsArchived()) {
			return ActionResult::error('Сотрудник уволен в инвентаризации - учётка AD не создаётся', $logParams);
		}

		//выбранные контейнеры не должны выйти за настроенные корни (форма
		//шлёт DN текстом - серверная проверка обязательна); реальная граница
		//полномочий - делегированные права самого исполнителя в AD
		if (!$this->inUsersOus($ou)) {
			return ActionResult::error("Подразделение '$ou' вне разрешённых корней учёток ("
				.implode('; ', $this->usersOus()).')', $logParams);
		}
		$groupsOu = $this->groupsOu();
		if ($groupsOu) foreach ($groups as $groupDn) {
			if (!LdapService::dnIsUnder($groupDn, $groupsOu, true)) {
				return ActionResult::error("Группа '$groupDn' вне разрешённого корня групп ($groupsOu)", $logParams);
			}
		}

		$phone = $this->deliveryPhone($model);
		if ($phone === '') {
			return ActionResult::error('У сотрудника не заполнен мобильный/личный телефон - пароль некуда отправить', $logParams);
		}

		//шаг 0: НЕДЕСТРУКТИВНО ДО SMS - креды исполнителя, право создавать
		//в выбранном OU и пополнять выбранные группы, занятость логина
		try {
			$this->ldapVerifyCreate($ou, $groups, $credentials);
			if (!$this->ldapLoginIsFree($login)) {
				return ActionResult::error("Учётка $login уже существует в AD - создание не требуется", $logParams);
			}
		} catch (\Throwable $e) {
			return ActionResult::error(
				'Учётка не создана (SMS не отправлено): '.$e->getMessage(),
				$logParams
			);
		}

		$password = $this->generatePassword($form);

		//шаг 1: SMS с паролем ДО создания; неудача = останов
		$sms = $this->sendPasswordSms($model, $phone, $password, $login);
		if (!$sms->ok) {
			return ActionResult::error('Учётка НЕ создана: не удалось отправить SMS ('.$sms->message.')', $logParams);
		}

		//шаг 2: создание от имени исполнителя
		try {
			$adInfo = $this->ldapCreateAccount($this->accountAttributes($model, $login), $ou, $groups, $password, $credentials);
		} catch (\Throwable $e) {
			Yii::warning("AD account create for $login failed: ".$e->getMessage(), __METHOD__);
			$result = ActionResult::error(
				'SMS отправлено, но учётка в AD НЕ создана: '.$e->getMessage()
				.'. Повторите создание - пользователю придет новое SMS.',
				$logParams
			);
			$result->html = $this->renderCreateReport($model, $phone, $login, $credentials['login'],
				$sms, null, $e->getMessage(), null);
			return $result;
		}

		//логин - в карточку сотрудника: панель, сброс и восстановление
		//начнут видеть учётку (точечный save, без валидации всей карточки)
		$loginSaved = true;
		if ($model->Login !== $login) {
			$model->Login = $login;
			$loginSaved = $model->save(false, ['Login']);
		}

		if (!empty($adInfo['enable_error'])) {
			$result = ActionResult::error(
				"Учётка $login создана, пароль отправлен по SMS на $phone, но учётка НЕ включена: "
				.$adInfo['enable_error'],
				$logParams
			);
		} else {
			$result = ActionResult::success(
				"Учётка $login создана и включена, пароль отправлен по SMS на $phone"
				.(empty($adInfo['group_errors']) ? '' : ' (часть групп не добавлена - см. отчёт)')
				.'. Пароль знает только пользователь.',
				$logParams
			);
		}
		$result->html = $this->renderCreateReport($model, $phone, $login, $credentials['login'],
			$sms, $adInfo, null, $loginSaved);
		return $result;
	}

	/**
	 * Атрибуты создаваемой учётки из карточки сотрудника: ФИО «Фамилия Имя
	 * Отчество» раскладывается в sn/givenName, должность - в title.
	 * upnSuffix доопределит LdapService (account_suffix ldap-компонента).
	 */
	protected function accountAttributes(Users $model, string $login): array
	{
		$fio = trim((string)$model->Ename);
		$tokens = preg_split('/\s+/', $fio, -1, PREG_SPLIT_NO_EMPTY) ?: [];
		return [
			'samaccountname' => $login,
			'cn' => $fio !== '' ? $fio : $login,
			'displayname' => $fio !== '' ? $fio : null,
			'sn' => $tokens[0] ?? null,
			'givenname' => count($tokens) > 1 ? implode(' ', array_slice($tokens, 1)) : null,
			'title' => trim((string)$model->Doljnost) !== '' ? trim((string)$model->Doljnost) : null,
			'upnSuffix' => $this->config['upnSuffix'] ?? null,
		];
	}

	// ==================== восстановление учётки ====================

	/**
	 * @param AdRestoreAccountForm|Model $form
	 */
	protected function runRestoreAction(Users $model, Model $form, array $credentials): ActionResult
	{
		$targetLogin = $this->binding($model);
		if (is_null($targetLogin)) {
			return ActionResult::error('Действие применимо только к сотруднику с логином AD');
		}

		$ou = trim((string)$form->ou);
		$unlock = (bool)$form->unlock;
		$logParams = ['login' => $targetLogin, 'ou' => $ou, 'unlock' => $unlock];

		if ($model->getIsArchived()) {
			return ActionResult::error('Сотрудник уволен в инвентаризации - восстановление учётки недоступно', $logParams);
		}

		if (!$this->restoreConfigured()) {
			return ActionResult::error('Восстановление не настроено (пары корней ouPairs/usersOu+dismissedOu)', $logParams);
		}

		//состояние учётки перепроверяется на сервере (кнопка в панели -
		//лишь отражение того же условия в момент рендера)
		try {
			$account = $this->fetchAccount($targetLogin);
		} catch (\Throwable $e) {
			return ActionResult::error('AD недоступен: '.$e->getMessage(), $logParams);
		}
		if (!$account) {
			return ActionResult::error("Учётка $targetLogin не найдена в AD - восстанавливать нечего", $logParams);
		}
		if ($account['enabled']) {
			return ActionResult::error("Учётка $targetLogin включена - восстановление не требуется", $logParams);
		}
		$pair = $this->dismissedPairFor($account['dn']);
		if (!$pair) {
			return ActionResult::error(
				"Учётка $targetLogin отключена, но лежит не в контейнере уволенных - "
				.'она отключена другим способом, разберитесь вручную',
				$logParams
			);
		}
		//восстановление - строго в рамках своей пары увольнения
		if (!LdapService::dnIsUnder($ou, $pair['users'], true)) {
			return ActionResult::error(
				"Подразделение '$ou' вне корня учёток этой пары увольнения ({$pair['users']})",
				$logParams
			);
		}

		$phone = $this->deliveryPhone($model);
		if ($phone === '') {
			return ActionResult::error('У сотрудника не заполнен мобильный/личный телефон - пароль некуда отправить', $logParams);
		}

		//шаг 0: НЕДЕСТРУКТИВНАЯ предпроверка ДО SMS: креды исполнителя и
		//права на пароль И включение учётки
		try {
			$this->ldapVerifyManage($targetLogin, $credentials);
		} catch (\Throwable $e) {
			return ActionResult::error(
				'Восстановление не выполнено (SMS не отправлено): '.$e->getMessage(),
				$logParams
			);
		}

		$password = $this->generatePassword($form);

		//шаг 1: SMS с паролем ДО записи в AD; неудача = останов
		$sms = $this->sendPasswordSms($model, $phone, $password, $targetLogin);
		if (!$sms->ok) {
			return ActionResult::error('Учётка НЕ восстановлена: не удалось отправить SMS ('.$sms->message.')', $logParams);
		}

		//шаг 2: пароль + включение + переезд от имени исполнителя
		try {
			$adInfo = $this->ldapRestoreAccount($targetLogin, $ou, $password, $unlock, $credentials);
		} catch (\Throwable $e) {
			Yii::warning("AD account restore for $targetLogin failed: ".$e->getMessage(), __METHOD__);
			$result = ActionResult::error(
				'SMS отправлено, но учётка НЕ восстановлена: '.$e->getMessage()
				.'. Повторите восстановление - пользователю придет новое SMS.',
				$logParams
			);
			$result->html = $this->renderRestoreReport($model, $phone, $targetLogin, $credentials['login'],
				$sms, null, $e->getMessage(), $unlock);
			return $result;
		}

		if (empty($adInfo['move_error'])) {
			$result = ActionResult::success(
				"Учётка $targetLogin включена и возвращена в рабочее дерево, пароль отправлен по SMS на $phone"
				.'. Пароль знает только пользователь.',
				$logParams
			);
		} else {
			$result = ActionResult::error(
				"Учётка $targetLogin включена, пароль отправлен по SMS на $phone, "
				.'но НЕ перемещена: '.$adInfo['move_error'].'. Перенесите учётку вручную.',
				$logParams
			);
		}
		$result->html = $this->renderRestoreReport($model, $phone, $targetLogin, $credentials['login'],
			$sms, $adInfo, null, $unlock);
		return $result;
	}

	// ==================== общие шаги действий ====================

	/**
	 * Куда доставлять пароль: первый заполненный телефон сотрудника,
	 * те же поля, что SMS-провайдер считает мобильными (Mobile, затем
	 * private_phone) - единый источник правды
	 */
	protected function deliveryPhone(Users $model): string
	{
		foreach (SmsProvider::PHONE_ATTRIBUTES as $attribute) {
			$candidate = trim(ArrayHelper::explode(',', $model->$attribute ?? '')[0] ?? '');
			if ($candidate !== '') return $candidate;
		}
		return '';
	}

	/**
	 * Пароль по параметрам формы (тип и длина)
	 * @param AdPasswordResetForm|Model $form
	 */
	protected function generatePassword(Model $form): string
	{
		$length = max(AdPasswordResetForm::MIN_LENGTH, (int)$form->length);
		return $form->pronounceable
			? (new PronounceablePasswordGenerator($length))->generate()
			: $this->randomPassword($length);
	}

	/**
	 * Отправка пароля пользователю через SMS-провайдера (композиция §5,
	 * вложенный вызов через реестр - валидация и журнал как у proxy)
	 */
	protected function sendPasswordSms(Users $model, string $phone, string $password, string $login): ActionResult
	{
		$smsText = str_replace(
			['{password}', '{login}'],
			[$password, $login],
			$this->config['smsText'] ?? 'Ваш новый пароль: {password}'
		);
		return IntegrationsRegistry::runAction($this->smsProviderId(), 'send', $model,
			['phone' => $phone, 'text' => $smsText],
			null, $this->activeLogId);
	}

	/** id SMS-провайдера для отправки пароля (зависимость, §5) */
	protected function smsProviderId(): string
	{
		return $this->config['sms'] ?? 'sms';
	}

	/** настроен ли SMS-провайдер (проверка по сырым params, см. actions()) */
	protected function smsConfigured(): bool
	{
		return !empty(Yii::$app->params['integrations'][$this->smsProviderId()]);
	}

	/**
	 * Пары корней «рабочие учётки ↔ их уволенные» — строгое соответствие
	 * конфигу скрипта увольнения ($inventory2ad_sync в ad-usermanagement:
	 * u_OUDN/f_OUDN): увольнение переносит учётку из users-корня пары в
	 * dismissed-корень той же пары с сохранением подпути, восстановление
	 * зеркалит строго обратно — в рамках СВОЕЙ пары, без угадывания.
	 *
	 * Конфиг: 'ouPairs' => [['users' => DN, 'dismissed' => DN], ...];
	 * legacy-вариант 'usersOu'/'dismissedOu' (по строке) = одна пара.
	 * Пара без dismissed допустима: создание работает, восстановления нет.
	 *
	 * @return array [['users' => string, 'dismissed' => ?string], ...]
	 */
	protected function ouPairs(): array
	{
		$pairs = [];
		foreach ((array)($this->config['ouPairs'] ?? []) as $pair) {
			$users = trim((string)($pair['users'] ?? ''));
			if ($users === '') continue;
			$dismissed = trim((string)($pair['dismissed'] ?? ''));
			$pairs[] = ['users' => $users, 'dismissed' => $dismissed === '' ? null : $dismissed];
		}
		if (!count($pairs)) { //legacy-скаляры одной парой
			$users = trim((string)($this->config['usersOu'] ?? ''));
			$dismissed = trim((string)($this->config['dismissedOu'] ?? ''));
			if ($users !== '') {
				$pairs[] = ['users' => $users, 'dismissed' => $dismissed === '' ? null : $dismissed];
			}
		}
		return $pairs;
	}

	/**
	 * Корни рабочих учёток из пар (для дерева OU формы создания и проверок)
	 * @return string[]
	 */
	protected function usersOus(): array
	{
		return array_column($this->ouPairs(), 'users');
	}

	/** лежит ли DN под одним из корней рабочих учёток (или равен ему) */
	protected function inUsersOus(string $dn): bool
	{
		foreach ($this->usersOus() as $root) {
			if (LdapService::dnIsUnder($dn, $root, true)) return true;
		}
		return false;
	}

	/**
	 * Пара, в чей контейнер уволенных попадает DN (null = ни в чей —
	 * учётка отключена не увольнением)
	 */
	protected function dismissedPairFor(string $dn): ?array
	{
		foreach ($this->ouPairs() as $pair) {
			if ($pair['dismissed'] && LdapService::dnIsUnder($dn, $pair['dismissed'])) return $pair;
		}
		return null;
	}

	/** настроено ли восстановление (есть хотя бы одна пара с dismissed) */
	protected function restoreConfigured(): bool
	{
		foreach ($this->ouPairs() as $pair) {
			if ($pair['dismissed']) return true;
		}
		return false;
	}

	/** корень поиска групп для формы создания (null = весь каталог) */
	protected function groupsOu(): ?string
	{
		$ou = trim((string)($this->config['groupsOu'] ?? ''));
		return $ou === '' ? null : $ou;
	}

	/** настроено ли создание учёток (для appliesTo - дёшево, без сети) */
	protected function createConfigured(): bool
	{
		return $this->smsConfigured() && count($this->usersOus()) > 0;
	}

	// ==================== генерация логина ====================

	/**
	 * Кусок ФИО в допустимые для логина символы: транслит
	 * ({@see StringHelper::translit()}) + фильтр [a-z0-9._-] (всё
	 * нетранслитерируемое отбрасывается)
	 */
	protected static function loginSafe(string $text): string
	{
		return preg_replace('/[^a-z0-9._-]/', '', StringHelper::translit($text));
	}

	/**
	 * Предложение логина для сотрудника: существующий Login как есть
	 * (нормализованный), иначе по регламенту сквозных учёток —
	 * «фамилия.{первая буква имени}» транслитом из ФИО, но не более
	 * {@see LOGIN_MAX} знаков (ограничение SAP): не влезло — обрезка с
	 * конца ({@see fitLogin()}), «Попандополо Евстафий» -> popandopolo,
	 * «Череззаборногузадерищенко Иван» -> cherezzaborn.
	 *
	 * Занятость в AD здесь НЕ проверяется (метод дешёвый, вызывается при
	 * рендере карточек) - свободный вариант с номером однофамильца
	 * подбирает рендер формы ({@see pickFreeLogin()}).
	 */
	public static function suggestLogin(Users $model): string
	{
		if (!empty($model->Login)) return mb_strtolower(trim($model->Login));

		$tokens = preg_split('/\s+/', trim((string)$model->Ename), -1, PREG_SPLIT_NO_EMPTY) ?: [];
		if (empty($tokens)) return '';
		$surname = static::loginSafe($tokens[0]);
		if ($surname === '') return '';
		$initial = isset($tokens[1]) ? static::loginSafe(mb_substr($tokens[1], 0, 1)) : '';

		return static::fitLogin($initial === '' ? $surname : $surname.'.'.$initial);
	}

	/**
	 * Вписать логин в лимит по регламенту: обрезка с конца до $max
	 * символов, оставшаяся крайней разделительная точка обрезается
	 * ОБЯЗАТЕЛЬНО («popandopolo.e» -> «popandopolo.» -> «popandopolo»)
	 */
	protected static function fitLogin(string $login, int $max = self::LOGIN_MAX): string
	{
		return rtrim(substr($login, 0, max(0, $max)), '.');
	}

	/**
	 * Свободный вариант занятого логина по регламенту коллизий:
	 * «фамилия.и#», где # - номер однофамильца (с 2); суффикс обязан
	 * влезть в лимит - база ужимается под него ({@see fitLogin()}):
	 * smirnov.a -> smirnov.a2, popandopolo -> popandopolo7 (7-й),
	 * cherezzaborn -> cherezzabo15 (15-й).
	 * @return string|null null = не подобрался (все заняты)
	 */
	protected function pickFreeLogin(string $login): ?string
	{
		for ($n = 2; $n <= 99; $n++) {
			$suffix = (string)$n;
			$candidate = static::fitLogin($login, static::LOGIN_MAX - strlen($suffix)).$suffix;
			if ($this->ldapLoginIsFree($candidate)) return $candidate;
		}
		return null;
	}

	// ==================== случайный пароль ====================

	/**
	 * Полностью случайный пароль заданной длины: гарантированно есть все
	 * классы (верхний/нижний регистр, цифра, спецсимвол — AD complexity),
	 * без визуально неоднозначных символов
	 */
	public function randomPassword(int $length): string
	{
		$length = max(AdPasswordResetForm::MIN_LENGTH, $length);
		$sets = [static::RND_UPPER, static::RND_LOWER, static::RND_DIGIT, static::RND_SPECIAL];
		$all = implode('', $sets);

		$chars = [];
		foreach ($sets as $set) { //по одному из каждого класса
			$chars[] = $set[random_int(0, strlen($set) - 1)];
		}
		while (count($chars) < $length) {
			$chars[] = $all[random_int(0, strlen($all) - 1)];
		}
		//перемешиваем криптостойко (shuffle не CSPRNG)
		for ($i = count($chars) - 1; $i > 0; $i--) {
			$j = random_int(0, $i);
			[$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
		}
		return implode('', $chars);
	}

	// ==================== отчёты в модалку ====================

	/**
	 * Развёрнутый отчёт о выполнении сброса для модалки: что сделано на
	 * каждом шаге, что ответил SMS-шлюз и чем подтверждена смена пароля в
	 * AD. Пароль в отчёт не попадает.
	 *
	 * @param ActionResult $sms итог шага отправки SMS
	 * @param array|null $adInfo итог записи в AD (null = шаг не выполнен)
	 * @param string|null $adError текст ошибки AD (если шаг провалился)
	 */
	protected function renderReport(?ArmsModel $model, string $phone, string $targetLogin,
		string $execLogin, ActionResult $sms, ?array $adInfo, ?string $adError, bool $unlock): string
	{
		$e = [Html::class, 'encode'];

		$rows = [];
		$rows[] = ['Учётная запись', $e($targetLogin).($adInfo['dn'] ?? null ? ' <small class="text-secondary">'.$e($adInfo['dn']).'</small>' : '')];
		$rows[] = ['Исполнитель в AD', $e($execLogin)];
		$rows[] = ['Проверка прав', static::okBadge(true).' <small class="text-secondary">креды верны, право на сброс есть</small>'];
		$rows[] = $this->smsReportRow($sms, $phone);

		if ($adInfo) {
			$rows[] = ['Смена пароля в AD', static::okBadge(true)
				.'<br><small class="text-secondary">отметка смены пароля (pwdLastSet): '
				.$e($this->reportTime($adInfo['pwd_last_set_before'])).' → <b>'.$e($this->reportTime($adInfo['pwd_last_set_after'])).'</b></small>'];
			if ($unlock) $rows[] = ['Разблокировка', static::okBadge(true)];
		} else {
			$rows[] = ['Смена пароля в AD', static::okBadge(false)
				.'<br><small class="text-danger">'.$e($adError).'</small>'];
		}

		return $this->reportTable($rows)
			.'<p class="text-secondary mb-0"><small>Пароль отправлен только пользователю по SMS '
			.'и нигде не сохраняется. Если SMS не дошло — повторите сброс, придёт новый пароль. '
			.'Панель AD в карточке покажет новое состояние после обновления страницы.</small></p>';
	}

	/**
	 * Отчёт о создании учётки: предпроверка, SMS, создание, включение,
	 * группы, логин в карточке. Пароль в отчёт не попадает.
	 *
	 * @param array|null $adInfo итог создания ({@see LdapService::createAccount()}),
	 *   null = шаг не выполнен
	 * @param bool|null $loginSaved записан ли логин в карточку (null = до шага не дошло)
	 */
	protected function renderCreateReport(?ArmsModel $model, string $phone, string $login,
		string $execLogin, ActionResult $sms, ?array $adInfo, ?string $adError, ?bool $loginSaved): string
	{
		$e = [Html::class, 'encode'];

		$rows = [];
		$rows[] = ['Учётная запись', $e($login).($adInfo['dn'] ?? null ? ' <small class="text-secondary">'.$e($adInfo['dn']).'</small>' : '')];
		$rows[] = ['Исполнитель в AD', $e($execLogin)];
		$rows[] = ['Проверка прав', static::okBadge(true).' <small class="text-secondary">креды верны, право создать есть, логин свободен</small>'];
		$rows[] = $this->smsReportRow($sms, $phone);

		if ($adInfo) {
			$rows[] = ['Создание учётки', static::okBadge(true)];
			$rows[] = ['Включение', empty($adInfo['enable_error'])
				? static::okBadge((bool)$adInfo['enabled'])
				: static::okBadge(false).'<br><small class="text-danger">'.$e($adInfo['enable_error']).'</small>'];
			$groupsCell = count($adInfo['groups'] ?? [])
				? static::okBadge(true).' '.$e(implode(', ', $adInfo['groups']))
				: '<span class="text-secondary">не выбраны</span>';
			foreach ($adInfo['group_errors'] ?? [] as $groupError) {
				$groupsCell .= '<br><small class="text-danger">'.$e($groupError).'</small>';
			}
			$rows[] = ['Группы', $groupsCell];
			if (!is_null($loginSaved)) {
				$rows[] = ['Логин в карточке сотрудника', $loginSaved
					? static::okBadge(true).' <small class="text-secondary">записан '.$e($login).'</small>'
					: static::okBadge(false).'<br><small class="text-danger">не удалось сохранить - впишите логин вручную</small>'];
			}
		} else {
			$rows[] = ['Создание учётки', static::okBadge(false)
				.'<br><small class="text-danger">'.$e($adError).'</small>'];
		}

		return $this->reportTable($rows)
			.'<p class="text-secondary mb-0"><small>Пароль отправлен только пользователю по SMS '
			.'и нигде не сохраняется. Панель AD в карточке покажет учётку после обновления страницы.</small></p>';
	}

	/**
	 * Отчёт о восстановлении учётки: предпроверка, SMS, пароль, включение,
	 * переезд. Пароль в отчёт не попадает.
	 *
	 * @param array|null $adInfo итог восстановления
	 *   ({@see LdapService::restoreAccount()}), null = шаг не выполнен
	 */
	protected function renderRestoreReport(?ArmsModel $model, string $phone, string $targetLogin,
		string $execLogin, ActionResult $sms, ?array $adInfo, ?string $adError, bool $unlock): string
	{
		$e = [Html::class, 'encode'];

		$rows = [];
		$rows[] = ['Учётная запись', $e($targetLogin).($adInfo['dn_after'] ?? null ? ' <small class="text-secondary">'.$e($adInfo['dn_after']).'</small>' : '')];
		$rows[] = ['Исполнитель в AD', $e($execLogin)];
		$rows[] = ['Проверка прав', static::okBadge(true).' <small class="text-secondary">креды верны, права на пароль и включение есть</small>'];
		$rows[] = $this->smsReportRow($sms, $phone);

		if ($adInfo) {
			$rows[] = ['Смена пароля в AD', static::okBadge(true)
				.'<br><small class="text-secondary">отметка смены пароля (pwdLastSet): '
				.$e($this->reportTime($adInfo['pwd_last_set_before'])).' → <b>'.$e($this->reportTime($adInfo['pwd_last_set_after'])).'</b></small>'];
			$rows[] = ['Включение', static::okBadge((bool)$adInfo['enabled'])];
			if ($unlock) $rows[] = ['Разблокировка', static::okBadge(true)];
			$rows[] = ['Перемещение', empty($adInfo['move_error'])
				? static::okBadge(true).'<br><small class="text-secondary">'
					.$e($adInfo['dn_before']).' → <b>'.$e($adInfo['dn_after']).'</b></small>'
				: static::okBadge(false).'<br><small class="text-danger">'.$e($adInfo['move_error'])
					.' - перенесите учётку вручную</small>'];
		} else {
			$rows[] = ['Восстановление', static::okBadge(false)
				.'<br><small class="text-danger">'.$e($adError).'</small>'];
		}

		return $this->reportTable($rows)
			.'<p class="text-secondary mb-0"><small>Пароль отправлен только пользователю по SMS '
			.'и нигде не сохраняется. Если SMS не дошло — сбросьте пароль повторно. '
			.'Панель AD в карточке покажет новое состояние после обновления страницы.</small></p>';
	}

	/** значок «выполнено/не выполнено» для отчётов */
	protected static function okBadge(bool $good): string
	{
		return $good
			? '<span class="badge bg-success">выполнено</span>'
			: '<span class="badge bg-danger">не выполнено</span>';
	}

	/** метка времени в отчёте */
	protected function reportTime($ts): string
	{
		return $ts ? Yii::$app->formatter->asDatetime($ts, 'php:d.m.Y H:i:s') : '—';
	}

	/** строка отчёта об SMS-шаге */
	protected function smsReportRow(ActionResult $sms, string $phone): array
	{
		return ['Отправка SMS', static::okBadge($sms->ok).' на '.Html::encode($phone)
			.'<br><small class="text-secondary">'.Html::encode($sms->message).'</small>'];
	}

	/** таблица отчёта из пар [метка, HTML-значение] */
	protected function reportTable(array $rows): string
	{
		$html = '<table class="table table-sm w-auto">';
		foreach ($rows as [$label, $value]) {
			$html .= '<tr><td class="text-secondary pe-3 align-top">'.$label.'</td><td>'.$value.'</td></tr>';
		}
		return $html.'</table>';
	}

	// ==================== формы действий ====================

	public function renderActionForm(string $actionId, Model $form, $activeForm): string
	{
		switch ($actionId) {
			case static::ACTION_CREATE:
				return $this->renderCreateForm($form, $activeForm);
			case static::ACTION_RESTORE:
				return $this->renderRestoreForm($form, $activeForm);
		}

		//сброс пароля: тип/длина/разблокировка. Ручного ввода пароля нет -
		//он генерируется и не показывается админу
		return $this->renderPasswordFields($form, $activeForm, true)
			.'<p class="text-secondary">Будет сгенерирован пароль и отправлен пользователю '
			.'по SMS. Администратор пароль не видит.</p>';
	}

	/**
	 * Форма создания: логин (предложенный вариант перепроверяется на
	 * занятость), дерево OU, мультиселект групп, параметры пароля.
	 * Списки читаются из AD сервисной учёткой в момент открытия формы
	 * (форма открывается через proxy за RBAC; недоступность AD - штатный
	 * исход с предупреждением, сабмит всё равно упрётся в предпроверку).
	 * @param AdCreateAccountForm|Model $form
	 */
	protected function renderCreateForm(Model $form, $activeForm): string
	{
		$warning = '';
		$ouItems = [];
		$groupItems = [];
		try {
			$ouItems = $this->ouSelectItems();
			$groupItems = $this->groupSelectItems();

			//занятость предложенного логина проверяем при открытии формы,
			//а не после сабмита - и сразу предлагаем свободный вариант
			$login = mb_strtolower(trim((string)$form->login));
			if ($login !== '' && !$this->ldapLoginIsFree($login)) {
				$free = $this->pickFreeLogin($login);
				if ($free) {
					$form->login = $free;
					$warning = "Логин $login занят - предложен свободный вариант $free.";
				} else {
					$warning = "Логин $login занят - укажите другой.";
				}
			}

			//предвыбор групп из конфига - пока в форме ничего не выбрано
			if (empty($form->groups)) $form->groups = $this->defaultGroupDns($groupItems);
		} catch (\Throwable $e) {
			Yii::warning('AD create form data failed: '.$e->getMessage(), __METHOD__);
			$warning = 'AD недоступен, списки не загружены: '.$e->getMessage();
		}

		$html = $warning === '' ? ''
			: '<div class="alert alert-warning py-1">'.Html::encode($warning).'</div>';
		$html .= (string)$activeForm->field($form, 'login')->textInput(['maxlength' => static::LOGIN_MAX]);
		$html .= (string)$activeForm->field($form, 'ou')
			->dropDownList($ouItems, ['prompt' => '- выберите подразделение -']);
		//группы - select2: список большой, без поиска неудобно (ajax не
		//нужен - данные уже загружены); dropdownParent обязателен в
		//модалке, см. IntegrationProvider::$modalParent
		$html .= (string)FieldsHelper::Select2Field($activeForm, $form, 'groups', [
			'data' => $groupItems,
			'options' => [
				'placeholder' => 'Начните набирать для поиска групп',
				'multiple' => true,
			],
			'pluginOptions' => array_filter([
				'multiple' => true,
				'dropdownParent' => $this->modalParent,
			]),
		]);
		$html .= $this->renderPasswordFields($form, $activeForm, false);
		$html .= '<p class="text-secondary">Учётная запись будет создана и включена, пароль '
			.'сгенерирован и отправлен пользователю по SMS. Администратор пароль не видит.</p>';
		return $html;
	}

	/**
	 * Форма восстановления: целевое OU (предзаполнено зеркалом пути
	 * увольнения через prefill кнопки в панели) и параметры пароля.
	 * @param AdRestoreAccountForm|Model $form
	 */
	protected function renderRestoreForm(Model $form, $activeForm): string
	{
		$warning = '';
		$ouItems = [];
		try {
			$ouItems = $this->ouSelectItems();
			if ($form->ou !== '' && !isset($ouItems[$form->ou])) {
				//зеркальное OU не нашлось в рабочем дереве (переименовали/
				//удалили) - пусть исполнитель выберет вручную
				$warning = 'Исходное подразделение учётки не найдено в рабочем дереве - выберите, куда её вернуть.';
				$form->ou = '';
			}
		} catch (\Throwable $e) {
			Yii::warning('AD restore form data failed: '.$e->getMessage(), __METHOD__);
			$warning = 'AD недоступен, список подразделений не загружен: '.$e->getMessage();
		}

		$html = $warning === '' ? ''
			: '<div class="alert alert-warning py-1">'.Html::encode($warning).'</div>';
		$html .= (string)$activeForm->field($form, 'ou')
			->dropDownList($ouItems, ['prompt' => '- выберите подразделение -']);
		$html .= $this->renderPasswordFields($form, $activeForm, true);
		$html .= '<p class="text-secondary">Учётная запись будет включена, перемещена в выбранное '
			.'подразделение, пароль сброшен и отправлен пользователю по SMS. '
			.'Администратор пароль не видит.</p>';
		return $html;
	}

	/**
	 * Общие поля параметров пароля (тип/длина, опц. разблокировка)
	 * @param AdPasswordResetForm|Model $form
	 */
	protected function renderPasswordFields(Model $form, $activeForm, bool $withUnlock): string
	{
		$html = '<div class="row">';
		$html .= '<div class="col-6">';
		$html .= (string)$activeForm->field($form, 'length')->textInput([
			'type' => 'number',
			'min' => AdPasswordResetForm::MIN_LENGTH,
			'max' => AdPasswordResetForm::MAX_LENGTH,
		]);
		$html .= '</div>';
		$html .= '<div class="col-6">';
		$html .= (string)$activeForm->field($form, 'pronounceable')->checkbox();
		if ($withUnlock) $html .= (string)$activeForm->field($form, 'unlock')->checkbox();
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/** пункты селекта OU: dn => имя с отступом по глубине дерева */
	protected function ouSelectItems(): array
	{
		$items = [];
		foreach ($this->ldapOuList() as $ou) {
			$items[$ou['dn']] = str_repeat('• ', $ou['depth']).$ou['name'];
		}
		return $items;
	}

	/** пункты мультиселекта групп: dn => имя */
	protected function groupSelectItems(): array
	{
		$items = [];
		foreach ($this->ldapGroupList() as $group) {
			$items[$group['dn']] = $group['name'];
		}
		return $items;
	}

	/**
	 * DN предвыбранных групп из конфига defaultGroups (имена или DN,
	 * регистр не важен) среди реально существующих
	 * @param array $groupItems [dn => name] из groupSelectItems()
	 */
	protected function defaultGroupDns(array $groupItems): array
	{
		$wanted = array_map('mb_strtolower', array_map('trim', (array)($this->config['defaultGroups'] ?? [])));
		if (empty($wanted)) return [];
		$dns = [];
		foreach ($groupItems as $dn => $name) {
			if (in_array(mb_strtolower($name), $wanted, true)
				|| in_array(mb_strtolower($dn), $wanted, true)) $dns[] = $dn;
		}
		return $dns;
	}

	// ==================== внешние вызовы (подменяются в тестах) ====================

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

	/**
	 * НЕДЕСТРУКТИВНАЯ предпроверка кредов и прав исполнителя на сброс
	 * (шаг 0). Делегирует LdapService. Вынесено в отдельный метод: тесты
	 * подменяют его, не трогая LDAP.
	 * @throws \Throwable неверные креды / нет прав / цель не найдена / DC недоступен
	 */
	protected function ldapVerify(string $targetLogin, array $credentials): void
	{
		Yii::$app->ldap->verifyResetPermission(
			$targetLogin,
			$credentials['login'],
			$credentials['password']
		);
	}

	/**
	 * Запись пароля в AD от имени исполнителя. Делегирует LdapService,
	 * который открывает отдельное соединение под личными кредами
	 * исполнителя (общее соединение приложения под сервисной учёткой не
	 * трогается) и меняет пароль (требуется LDAPS/TLS — наш конфиг такой).
	 *
	 * Вынесено в отдельный метод: тесты подменяют его, не трогая LDAP.
	 *
	 * @throws \Throwable при ошибке бинда/записи (нет прав, политика паролей...)
	 */
	protected function ldapResetPassword(string $targetLogin, string $password,
		bool $unlock, array $credentials): array
	{
		return Yii::$app->ldap->resetPassword(
			$targetLogin,
			$password,
			$unlock,
			$credentials['login'],
			$credentials['password']
		);
	}

	/**
	 * НЕДЕСТРУКТИВНАЯ предпроверка кредов и прав исполнителя на создание
	 * учётки в OU и пополнение групп (шаг 0 создания). Тесты подменяют.
	 * @throws \Throwable
	 */
	protected function ldapVerifyCreate(string $ouDn, array $groupDns, array $credentials): void
	{
		Yii::$app->ldap->verifyCreatePermission($ouDn, $groupDns,
			$credentials['login'], $credentials['password']);
	}

	/**
	 * Свободен ли логин в AD (сервисной учёткой). Тесты подменяют.
	 * @throws \Throwable при недоступности LDAP
	 */
	protected function ldapLoginIsFree(string $login): bool
	{
		return Yii::$app->ldap->loginIsFree($login);
	}

	/**
	 * Создание учётки от имени исполнителя. Тесты подменяют.
	 * @return array см. {@see \app\components\ldap\LdapService::createAccount()}
	 * @throws \Throwable
	 */
	protected function ldapCreateAccount(array $attrs, string $ouDn, array $groupDns,
		string $password, array $credentials): array
	{
		return Yii::$app->ldap->createAccount($attrs, $ouDn, $groupDns, $password,
			$credentials['login'], $credentials['password']);
	}

	/**
	 * НЕДЕСТРУКТИВНАЯ предпроверка кредов и прав исполнителя на
	 * восстановление: пароль + включение (шаг 0). Тесты подменяют.
	 * @throws \Throwable
	 */
	protected function ldapVerifyManage(string $targetLogin, array $credentials): void
	{
		Yii::$app->ldap->verifyWriteAttributes($targetLogin, [
			'unicodepwd' => 'сбрасывать пароль этого пользователя',
			'useraccountcontrol' => 'включать/отключать эту учётную запись',
		], $credentials['login'], $credentials['password']);
	}

	/**
	 * Восстановление учётки от имени исполнителя. Тесты подменяют.
	 * @return array см. {@see \app\components\ldap\LdapService::restoreAccount()}
	 * @throws \Throwable
	 */
	protected function ldapRestoreAccount(string $targetLogin, string $newParentDn,
		string $password, bool $unlock, array $credentials): array
	{
		return Yii::$app->ldap->restoreAccount($targetLogin, $newParentDn, $password, $unlock,
			$credentials['login'], $credentials['password']);
	}

	/**
	 * Деревья OU под всеми корнями usersOu для селекта формы (сервисной
	 * учёткой); каждый корень - своё поддерево с depth 0. Тесты подменяют.
	 * @return array см. {@see \app\components\ldap\LdapService::ouList()}
	 * @throws \Throwable при недоступности LDAP
	 */
	protected function ldapOuList(): array
	{
		$items = [];
		foreach ($this->usersOus() as $root) {
			$items = array_merge($items, Yii::$app->ldap->ouList($root));
		}
		return $items;
	}

	/**
	 * Группы под groupsOu для мультиселекта формы (сервисной учёткой).
	 * Тесты подменяют.
	 * @return array см. {@see \app\components\ldap\LdapService::groupList()}
	 * @throws \Throwable при недоступности LDAP
	 */
	protected function ldapGroupList(): array
	{
		return Yii::$app->ldap->groupList($this->groupsOu());
	}
}
