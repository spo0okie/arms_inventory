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

	/** TTL кэша ячейки грида по умолчанию, сек (короче панельного:
	 * ячейки батчатся, но список открывают чаще карточки) */
	const DEFAULT_CELL_TTL = 30;

	/** нижняя граница TTL ячейки: F5-долбёжка списка не должна долбить
	 * внешнюю ИС даже при ttl=0 в конфиге */
	const MIN_CELL_TTL = 15;

	/** уровни действий (docs/dev/integrations.md) */
	const LEVEL_NORMAL = 'L2';		//от сервисной учетки, RBAC + журнал
	const LEVEL_PERSONAL = 'L2+';	//именное: личные креды внешней ИС на один запрос

	/** @var string id провайдера (ключ в params['integrations']), задает реестр */
	public string $id = '';

	/** @var array конфиг из params['integrations'][<id>], задает реестр */
	public array $config = [];

	/**
	 * @var bool панель рисуется в компактном режиме (вложенный список -
	 * ОС внутри АРМ в карточке сотрудника). Ставит proxy-контроллер по
	 * параметру запроса, во view приходит как $compact (см. renderView()).
	 * Кэш панели ведётся отдельно на каждый режим.
	 */
	public bool $compact = false;

	/**
	 * @var string|null селектор модалки, в которой рендерится форма
	 * действия (обычно '#modal_form_loader'); ставит view action-form
	 * перед вызовом renderActionForm(). Нужен виджетам с выпадающими
	 * списками (select2 dropdownParent): дропдаун, прикреплённый к body,
	 * не работает внутри bootstrap-модалки (фокус-ловушка). null = форма
	 * рендерится не в модалке.
	 */
	public ?string $modalParent = null;

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
	 * Колонки провайдера для гридов сущности (списочный режим, §5 «Колонки в списках»).
	 * Дёшево, без внешних вызовов: вызывается при построении каждого
	 * грида. $modelClass может быть search-наследником — проверять через
	 * is_a($modelClass, Comps::class, true), а не строгим сравнением.
	 * @return array [columnId => [
	 *   'title' => string, // заголовок колонки (по умолчанию getTitle())
	 *   'hint'  => string, // подсказка к заголовку (опционально)
	 *   'ttl'   => int,    // свежесть кэша ячейки, сек (см. cellTtl())
	 * ]]
	 */
	public function gridColumns(string $modelClass): array
	{
		return [];
	}

	/**
	 * Наполнить ячейки колонки для пачки моделей — ЕДИНСТВЕННОЕ место
	 * внешних вызовов списочного режима: одна пачка = один поход во
	 * внешнюю ИС (батч обязателен, построчных фолбэков через
	 * renderPanel() ядро не делает). Вызывается только из proxy
	 * ({@see CellsBatch}); модели уже отфильтрованы по appliesTo(),
	 * непустой binding() и непротухшему кэшу.
	 *
	 * Отсутствие id модели в ответе = пустая ячейка (не кэшируется).
	 * Исключение ловит ядро (заглушка «недоступно», кэш не трогается).
	 *
	 * @param ArmsModel[] $models
	 * @return array [model_id => html]
	 * @throws NotSupportedException
	 */
	public function renderCells(string $columnId, array $models): array
	{
		throw new NotSupportedException("Provider {$this->id} has no grid columns");
	}

	/**
	 * Ячейка применимой, но не привязанной строки. Рендерится ядром при
	 * выводе грида (внешние вызовы запрещены — как appliesTo/binding).
	 */
	public function renderUnboundCell(string $columnId, ArmsModel $model): string
	{
		return '<span class="text-secondary opacity-75" title="нет привязки">&mdash;</span>';
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
	 * (components/integrations/providers/views/<id>/<view>.php).
	 *
	 * Во view всегда приходит $compact - панель рисуется во вложенном
	 * списке (ОС внутри АРМ) и должна быть плотнее; провайдеру для этого
	 * ничего делать не нужно.
	 */
	public function renderView(string $view, array $params = []): string
	{
		return Yii::$app->view->render(
			'@app/components/integrations/providers/views/'.$this->id.'/'.$view,
			array_merge(['compact' => $this->compact], $params)
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

	/**
	 * TTL кэша ячейки грида, сек (колонка > конфиг cellTtl > 30),
	 * но не ниже MIN_CELL_TTL: «обновлять всегда» для списков не
	 * предусмотрено — F5 списка не должен долбить внешнюю ИС
	 */
	public function cellTtl(string $columnId, string $modelClass): int
	{
		$descriptor = $this->gridColumns($modelClass)[$columnId] ?? [];
		$ttl = (int)($descriptor['ttl'] ?? $this->config['cellTtl'] ?? static::DEFAULT_CELL_TTL);
		return max($ttl, static::MIN_CELL_TTL);
	}
}
