<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\helpers\ColorHelper;
use app\models\base\ArmsModel;

/**
 * Цветовой маркер — объект раскраски элементов справочников (issue #141).
 *
 * Заменяет ручную CSS-раскраску (классы по code/имени) выбираемым объектом:
 * фон — обязательный универсальный канал (читается на любом фоне: общий,
 * тёмная шапка, тултип, карточки, ячейки IPAM, смешение композитов);
 * рамка — необязательный уточняющий канал (семантика DMZ-пунктиров),
 * отбрасывается там, где её негде рендерить (уголок unit-status, полоски);
 * цвет текста — автоконтраст к фону либо явное переопределение.
 *
 * @property int $id
 * @property string $name Название маркера
 * @property string $color Цвет фона в HEX
 * @property string $text_color Цвет текста (NULL = автоконтраст)
 * @property string $border_color Цвет рамки (NULL = без рамки)
 * @property string $border_style Стиль рамки: solid/dashed
 * @property string $comment Пояснение
 * @property bool $archived Признак архивирования
 * @property string $updated_at Дата последнего изменения
 * @property string $updated_by Автор последних изменений
 *
 * @property string $textColor Эффективный цвет текста (учитывает автоконтраст)
 * @property string $styleVars Инлайн CSS-переменные маркера (--marker-*)
 */
class Markers extends ArmsModel
{
	public static $title = 'Маркер';
	public static $titles = 'Маркеры';

	/** @var string[] допустимые стили рамки */
	public static $borderStyles = [
		'solid' => 'сплошная',
		'dashed' => 'пунктирная',
	];

	public static function modelDescription(): string
	{
		return 'Цветовые маркеры для раскраски объектов справочников (состояния, сегменты, домены, типы и т.п.): '
			.'цвет фона, автоконтрастный или заданный цвет текста, опциональная рамка. '
			.'Один маркер можно назначить нескольким объектам — цветовая семантика остаётся единой.';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'markers';
	}

	public $linksSchema = [
		'tech_states_ids' =>		[TechStates::class, 'marker_id'],
		'segments_ids' =>			[Segments::class, 'marker_id'],
		'net_domains_ids' =>		[NetDomains::class, 'marker_id'],
		'tech_types_ids' =>			[TechTypes::class, 'marker_id'],
		'contracts_states_ids' =>	[ContractsStates::class, 'marker_id'],
	];

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['name', 'color'], 'required'],
			[['name'], 'trim'],
			[['name'], 'string', 'max' => 64],
			[['name'], 'unique'],
			[['color', 'text_color', 'border_color'], 'string', 'max' => 7],
			[
				['color', 'text_color', 'border_color'], 'match',
				'pattern' => '/^#[0-9A-Fa-f]{6}$/',
				'message' => 'Цвет должен быть в формате HEX (#RRGGBB)',
			],
			[['border_style'], 'string', 'max' => 8],
			[['border_style'], 'in', 'range' => array_keys(static::$borderStyles)],
			[['comment'], 'string', 'max' => 255],
			[['archived'], 'boolean'],
			[['archived'], 'default', 'value' => 0],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(), [
			'name' => [
				'Название',
				'hint' => 'Смысловое название маркера (например «Опасная зона», «В работе»). '
					.'Видно только при настройке — объекты показывают собственные имена в цветах маркера',
			],
			'color' => [
				'Цвет фона',
				'hint' => 'Основной канал маркера: фон элемента. '
					.'Смысл маркера должен читаться по одному фону — рамка не везде рендерится',
				'typeClass' => \app\types\ColorType::class,
			],
			'text_color' => [
				'Цвет текста',
				'hint' => 'Если не задан — подбирается автоматически (чёрный/белый) по яркости фона. '
					.'Задавайте только когда нужен особый цвет текста',
				'placeholder' => 'автоконтраст',
				'typeClass' => \app\types\ColorType::class,
			],
			'border_color' => [
				'Цвет рамки',
				'hint' => 'Необязательный уточняющий канал (например пунктир DMZ). '
					.'Там, где рамку рендерить негде (уголок статуса, полоски смешения), она отбрасывается',
				'placeholder' => 'без рамки',
				'typeClass' => \app\types\ColorType::class,
			],
			'border_style' => [
				'Стиль рамки',
				'hint' => 'Сплошная или пунктирная. Имеет смысл только при заданном цвете рамки',
				'placeholder' => 'сплошная',
				'fieldList' => static::$borderStyles,
				'typeClass' => \app\types\ChoiceType::class,
			],
			'comment' => [
				'Пояснение',
				'hint' => 'Когда применяется этот маркер',
			],
		]);
	}

	/**
	 * Эффективный цвет текста: явно заданный или автоконтраст к фону
	 * @return string
	 */
	public function getTextColor()
	{
		if (ColorHelper::isValidHex($this->text_color)) return $this->text_color;
		if (ColorHelper::isValidHex($this->color)) return ColorHelper::contrastColor($this->color);
		return '#000000';
	}

	/**
	 * Инлайн CSS-переменные маркера для элемента (потребляет web/css/markers.css).
	 * Рамка собирается в одну переменную целиком, чтобы CSS мог использовать
	 * fallback var(--marker-border, none) при её отсутствии.
	 * @return string
	 */
	public function getStyleVars()
	{
		if (!ColorHelper::isValidHex($this->color)) return '';
		$vars = "--marker-bg:{$this->color};--marker-fg:{$this->textColor}";
		if (ColorHelper::isValidHex($this->border_color)) {
			$style = isset(static::$borderStyles[$this->border_style]) ? $this->border_style : 'solid';
			$vars .= ";--marker-border:1.2pt {$style} {$this->border_color}";
		}
		return $vars;
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechStates()
	{
		return $this->hasMany(TechStates::class, ['marker_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSegments()
	{
		return $this->hasMany(Segments::class, ['marker_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getNetDomains()
	{
		return $this->hasMany(NetDomains::class, ['marker_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechTypes()
	{
		return $this->hasMany(TechTypes::class, ['marker_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getContractsStates()
	{
		return $this->hasMany(ContractsStates::class, ['marker_id' => 'id']);
	}

	/**
	 * Объекты, использующие этот маркер (защита от удаления используемого маркера)
	 */
	public function reverseLinks()
	{
		return [
			$this->techStates,
			$this->segments,
			$this->netDomains,
			$this->techTypes,
			$this->contractsStates,
		];
	}

	/**
	 * Список маркеров для селекторов форм
	 * @return array
	 */
	public static function fetchNames()
	{
		$list = static::find()
			->select(['id', 'name'])
			->orderBy(['name' => SORT_ASC])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

	/**
	 * Данные для select2 с вариантами сразу в целевой раскраске:
	 * [id=>name] + HTML-атрибуты option'ов (data-marker-style с CSS-переменными,
	 * их читает formatSelect2MarkerItem в select2hints.js)
	 * @return array [data, optionAttrs]
	 */
	public static function fetchSelectData()
	{
		$data = [];
		$optionAttrs = [];
		foreach (static::find()->orderBy(['name' => SORT_ASC])->all() as $marker) {
			$data[$marker->id] = $marker->name;
			if (strlen($vars = $marker->styleVars)) {
				$optionAttrs[$marker->id] = ['data-marker-style' => $vars];
			}
		}
		return [$data, $optionAttrs];
	}
}
