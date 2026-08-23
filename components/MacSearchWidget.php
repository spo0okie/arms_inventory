<?php

namespace app\components;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\MacSearchProvider;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use app\models\Places;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Иконка поиска рядом с MAC-адресом (issue #218): в тултипе — куда этот
 * адрес можно поискать.
 *
 * - среди оборудования и среди ОС — обычные списки с фильтром по MAC
 *   (у них уже есть сортировка, колонки, персонализация и экспорт, своей
 *   страницы поиска заводить незачем);
 * - на портах коммутаторов — если включена интеграция
 *   {@see MacSearchProvider} и у пользователя есть право её смотреть;
 *   открывается в модалке через штатный proxy интеграций, поэтому опрос
 *   железа происходит по явному клику, а не при открытии карточки.
 *
 * Иконку рисует тип атрибута ({@see \app\types\MacsType}) — значит, она
 * появляется везде, где показан MAC любой модели, и ни одна карточка про
 * это не знает. В списках иконка не нужна: там ту же задачу решает фильтр
 * колонки, а иконка в каждой строке — шум.
 */
class MacSearchWidget extends Widget
{
	/** @var ArmsModel|null объект, которому принадлежит адрес */
	public ?ArmsModel $model = null;

	/** @var string адрес: 12 hex-символов */
	public string $mac = '';

	public function run()
	{
		$hex = preg_replace('/[^0-9a-f]/', '', mb_strtolower($this->mac));
		if (strlen($hex) !== 12) return '';    //частичный адрес/диапазон не ищем

		//подписи фиксированные: $titles моделей именительного падежа
		//(«Оборудование», «Операционные системы») в эту фразу не встают
		$items = [
			Html::a('Искать среди оборудования', ['/techs/index', 'TechsSearch[mac]' => $hex]),
			Html::a('Искать среди операционных систем', ['/comps/index', 'CompsSearch[mac]' => $hex]),
		];

		foreach (static::portsItems($this->model, $hex) as $label => $url) {
			$items[] = Html::a($label, $url, [
				'class' => 'open-in-modal-form',
				//модалка ставит свой заголовок из title ссылки, пока грузится
				//ответ (в самом ответе заголовок приезжает в h1)
				'title' => 'Поиск на портах коммутаторов',
				'data-pjax' => 0,
			]);
		}

		return ' '.Html::tag('span', '<i class="fas fa-search"></i>', [
			'class' => 'mac-search-icon text-secondary',
			'qtip_pin' => 1,
			'qtip_side' => 'top,bottom,right,left',
			'qtip_theme' => 'tooltipster-shadow tooltipster-shadow-infobox',
			'qtip_ttip' => '<div class="card"><div class="card-header">Поиск по MAC</div>'
				.'<div class="card-body"><p class="card-text mb-0">'
				.implode('<br />', $items)
				.'</p></div></div>',
		]);
	}

	/**
	 * Пункты опроса коммутаторов для этого адреса: подпись => URL (пусто —
	 * интеграция выключена, не настроена, нет прав или адрес не привязан к
	 * сохранённому объекту).
	 *
	 * Пунктов два, когда у объекта известна площадка: искать обычно нужно
	 * там, где он стоит, но коммутатор могли перевезти — тогда нужен опрос по
	 * всем площадкам (дольше и тяжелее, поэтому отдельным пунктом, а не
	 * умолчанием). Площадку называем в подписи, чтобы было видно, где ищем.
	 *
	 * Ходим штатным proxy интеграций: он уже проверяет доступ как «просмотр»,
	 * кэширует ответ и умеет отдавать заглушку недоступной внешней ИС.
	 */
	public static function portsItems(?ArmsModel $model, string $hex): array
	{
		if (!is_object($model) || $model->isNewRecord || !$model->id) return [];

		$provider = static::provider($model);
		if (!$provider) return [];

		$url = static function ($scope) use ($provider, $model, $hex) {
			return Url::to([
				'/integrations/panel',
				'provider' => $provider->id,
				'panel' => MacSearchProvider::PANEL,
				'class' => StringHelper::class2Id(get_class($model)),
				'id' => $model->id,
				'mac' => $hex,
				'scope' => $scope,
			]);
		};

		$items = [];
		$site = static::site($model);
		if ($site) {
			$items['Искать на портах коммутаторов: '.$site->name] = $url(MacSearchProvider::SCOPE_PLACE);
		}
		$items[$site ? 'То же по всем площадкам' : 'Искать на портах коммутаторов']
			= $url(MacSearchProvider::SCOPE_ALL);

		return $items;
	}

	/** Провайдер поиска MAC, применимый к этому объекту и видимый пользователю */
	protected static function provider(ArmsModel $model): ?MacSearchProvider
	{
		foreach (IntegrationsRegistry::providers() as $provider) {
			if (!$provider instanceof MacSearchProvider) continue;
			if (!IntegrationsRegistry::userCanView($provider)) return null;
			if (!$provider->appliesTo($model)) return null;
			return $provider;
		}
		return null;
	}

	/**
	 * Площадка объекта (корень ветки помещений) или null. Кэшируется на
	 * запрос: у объекта может быть несколько адресов, а иконка рисуется
	 * у каждого.
	 */
	protected static function site(ArmsModel $model): ?Places
	{
		static $cache = [];
		$key = get_class($model).'/'.$model->id;
		if (array_key_exists($key, $cache)) return $cache[$key];

		$id = MacSearchProvider::siteOf($model);
		return $cache[$key] = $id ? Places::findOne($id) : null;
	}
}
