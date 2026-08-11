<?php

namespace app\components\integrations;

use app\models\base\ArmsModel;
use Yii;
use yii\base\Model;
use yii\base\NotSupportedException;

/**
 * Базовый класс провайдера интеграции с внешней ИС.
 *
 * Контракт зафиксирован в docs/dev/integrations.md:
 * провайдер описывает применимость к объектам ARMS, привязку к внешней ИС,
 * панели чтения (L1) и действия (L2/L2+). Вся обвязка (реестр, proxy,
 * виджеты, кэш, RBAC, журнал) — общая, см. {@see IntegrationsRegistry},
 * {@see \app\controllers\IntegrationsController}.
 *
 * Требования:
 * - транспорт любой (HTTP, LDAP, ...), но все внешние вызовы — только на
 *   сервере и только из renderPanel()/runAction();
 * - appliesTo()/binding() обязаны быть дешёвыми (вызываются при рендере
 *   карточек), внешние запросы в них запрещены;
 * - таймауты обязательны: `$this->timeout()` применять ко всем сетевым
 *   операциям;
 * - недоступность внешней ИС — штатный исход (исключение поймает ядро).
 */
abstract class IntegrationProvider
{
	/** таймаут сетевых операций по умолчанию, сек */
	const DEFAULT_TIMEOUT = 5;

	/** уровни действий (docs/dev/integrations.md) */
	const LEVEL_NORMAL = 'L2';		//от сервисной учетки, RBAC + журнал
	const LEVEL_PERSONAL = 'L2+';	//именное: личные креды внешней ИС на один запрос

	/** @var string id провайдера (ключ в params['integrations']), задает реестр */
	public string $id = '';

	/** @var array конфиг из params['integrations'][<id>], задает реестр */
	public array $config = [];

	/**
	 * @var int|null id записи журнала ТЕКУЩЕГО выполняемого действия;
	 * заполняется реестром на время runAction(). Составные действия
	 * передают его как parentLogId вложенных вызовов
	 * IntegrationsRegistry::runAction() (композиция, §2.2 контракта)
	 */
	public ?int $activeLogId = null;

	/** Название для заголовков панелей/прав/журнала */
	abstract public function getTitle(): string;

	/** Достаточно ли конфига для работы. false => провайдер невидим */
	abstract public function isConfigured(): bool;

	/**
	 * Применим ли провайдер к объекту. Решает сам провайдер: по классу
	 * модели, вычисляемым признакам (isVoipPhone), атрибутам (Login)
	 * и/или собственному конфигу (список сетей).
	 */
	abstract public function appliesTo(ArmsModel $model): bool;

	/**
	 * Ключ привязки объекта во внешней ИС (для кэша и запросов):
	 * атрибут модели (IP, номер, Login) или явная привязка из
	 * external_links (путь внутри JSON провайдер выбирает сам).
	 * null = объект применим, но не привязан.
	 */
	abstract public function binding(ArmsModel $model): ?string;

	/**
	 * Панели чтения (L1) для карточки объекта.
	 * @return array [panelId => [
	 *   'title' => string,
	 *   'ttl' => int, // свежесть кэша, сек (default 60)
	 * ]]
	 */
	public function panels(ArmsModel $model): array
	{
		return [];
	}

	/**
	 * Рендер панели: сходить во внешнюю ИС, вернуть готовый HTML.
	 * Вызывается только из proxy-контроллера (ajax), никогда при рендере
	 * страницы. Исключения ловит ядро («панель недоступна»).
	 * @throws NotSupportedException
	 */
	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		throw new NotSupportedException("Provider {$this->id} has no panels");
	}

	/**
	 * Действия (L2/L2+) для объекта ($model=null - standalone контекст).
	 * @return array [actionId => [
	 *   'title' => string,
	 *   'icon'  => string,       // fa-класс для кнопок
	 *   'level' => self::LEVEL_*,// L2+ => ядро запросит личные креды
	 *   'form'  => string,       // класс Model формы параметров ('' = без формы)
	 *   'standalone' => bool,    // default false; true = доступно без объекта
	 *   'showInPanel' => bool,   // default true; false = кнопку в блок
	 *                            //   интеграций не выводить (действие
	 *                            //   доступно только у атрибута/по URL)
	 * ]]
	 */
	public function actions(?ArmsModel $model): array
	{
		return [];
	}

	/**
	 * Действия, привязанные к отображению конкретного атрибута (иконка SMS
	 * рядом с номером). Подмножество actions() с предзаполнением формы.
	 * $value — конкретное отображаемое значение (атрибут может содержать
	 * несколько значений через запятую), по умолчанию значение атрибута.
	 * @return array [actionId => [...как в actions(), плюс
	 *   'prefill' => array  // атрибуты формы из значения]]
	 */
	public function attributeActions(ArmsModel $model, string $attribute, $value = null): array
	{
		return [];
	}

	/**
	 * Выполнить действие. Вызывается только через
	 * {@see IntegrationsRegistry::runActionForm()} (журналирование там).
	 * @param string $actionId id действия
	 * @param ArmsModel|null $model объект (null для standalone)
	 * @param Model $form провалидированная форма параметров
	 * @param array|null $credentials только для L2+: ['login'=>,'password'=>]
	 *   на один вызов; не сохранять и не журналировать
	 * @throws NotSupportedException
	 */
	public function runAction(string $actionId, ?ArmsModel $model, Model $form, ?array $credentials): ActionResult
	{
		throw new NotSupportedException("Provider {$this->id} has no actions");
	}

	/**
	 * Рендер полей формы действия (внутрь ActiveForm, собранного ядром).
	 * По умолчанию — все активные атрибуты формы подряд; провайдер может
	 * переопределить и отрендерить свой view-файл ({@see renderView()}).
	 * @param string $actionId id действия
	 * @param Model $form модель формы
	 * @param \yii\bootstrap5\ActiveForm $activeForm форма ядра
	 * @return string HTML полей
	 */
	public function renderActionForm(string $actionId, Model $form, $activeForm): string
	{
		$html = '';
		foreach ($form->activeAttributes() as $attribute) {
			$html .= $activeForm->field($form, $attribute);
		}
		return $html;
	}

	/**
	 * Рендер view-файла провайдера
	 * (components/integrations/providers/views/<id>/<view>.php)
	 */
	public function renderView(string $view, array $params = []): string
	{
		return Yii::$app->view->render(
			'@app/components/integrations/providers/views/'.$this->id.'/'.$view,
			$params
		);
	}

	/** Таймаут сетевых операций, сек (конфиг или общий дефолт) */
	public function timeout(): int
	{
		return (int)($this->config['timeout'] ?? static::DEFAULT_TIMEOUT);
	}

	/** TTL кэша панели, сек (панель > конфиг > 60) */
	public function panelTtl(string $panelId, ArmsModel $model): int
	{
		$panel = $this->panels($model)[$panelId] ?? [];
		return (int)($panel['ttl'] ?? $this->config['cacheTtl'] ?? 60);
	}
}
