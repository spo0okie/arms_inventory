<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use app\models\traits\AbsencesModelCalcFieldsTrait;
use yii\db\ActiveQuery;

/**
 * Отсутствие сотрудника (отпуск, больничный, командировка и т.п.),
 * см. plans/ARMS-absences-spec.md.
 *
 * ARMS выступает хабом-агрегатором: отсутствия принимаются из нескольких
 * источников (sap|c1|manual) с раздельным ключом идемпотентности по источнику
 * и отдаются наружу (SAPsync → Bitrix). Тип отсутствия хранится в нормализованном
 * виде — код совпадает со словарём XML_ID в Bitrix, чтобы выгрузка отображала тип
 * один-в-один без второго словаря. Организация не хранится: она однозначно
 * определяется сотрудником ([[Users]]::org_id).
 *
 * @property int $id
 * @property int $user_id Сотрудник (трудоустройство)
 * @property string $type Нормализованный тип отсутствия
 * @property string $date_from Начало периода
 * @property string $date_to Конец периода
 * @property string|null $comment Текст отсутствия из источника
 * @property string $source Источник записи (sap|c1|manual)
 * @property string|null $external_id Натуральный ключ записи в источнике
 * @property string|null $updated_at Дата последнего изменения
 * @property string|null $updated_by Автор последних изменений
 *
 * @property string $name Человекочитаемая подпись (calc, нет колонки name)
 * @property Users $user
 */
class Absences extends ArmsModel
{
	use AbsencesModelCalcFieldsTrait;

	public static $title = 'Отсутствие';
	public static $titles = 'Отсутствия';

	/**
	 * Нормализованные типы отсутствия. Ключ — стабильный строковый код, совпадающий
	 * со словарём XML_ID типов отсутствия в Bitrix (значение в скобках — ID списка
	 * Bitrix): VACATION(8), ASSIGNMENT(9), LEAVESICK(10), LEAVEMATERNITY(11),
	 * LEAVEUNPAYED(12), UNKNOWN(13), OTHER(14), PERSONAL(293).
	 * @var string[]
	 */
	public static $types = [
		'VACATION' => 'отпуск ежегодный',
		'ASSIGNMENT' => 'командировка',
		'LEAVESICK' => 'больничный',
		'LEAVEMATERNITY' => 'отпуск декретный',
		'LEAVEUNPAYED' => 'отгул / за свой счёт',
		'UNKNOWN' => 'прогул',
		'OTHER' => 'другое',
		'PERSONAL' => 'персональный календарь',
	];

	/**
	 * Источники записи. Маппинг кодов конкретного источника в нормализованный тип
	 * остаётся на стороне приёма (SAPsync): ARMS хранит уже нормализованные данные.
	 * @var string[]
	 */
	public static $sources = [
		'sap' => 'SAP',
		'c1' => '1С',
		'manual' => 'ручной ввод',
	];

	public static function modelDescription(): string
	{
		return 'Отсутствия сотрудников (отпуска, больничные, командировки). ARMS — хаб-агрегатор: '
			. 'принимает отсутствия из нескольких источников (SAP, 1С, ручной ввод) с раздельным ключом '
			. 'идемпотентности по источнику и отдаёт их наружу через REST. '
			. 'Синхронизация из источника не затирает записи, введённые вручную или пришедшие из другого источника.';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'absences';
	}

	public $linksSchema = [
		'user_id' => Users::class,
	];

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['user_id', 'type', 'date_from', 'date_to', 'source'], 'required'],
			[['user_id'], 'integer'],
			[['comment', 'external_id'], 'default', 'value' => null],
			[['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
			[['type'], 'string', 'max' => 16],
			[['type'], 'in', 'range' => array_keys(static::$types)],
			[['source'], 'string', 'max' => 8],
			[['source'], 'in', 'range' => array_keys(static::$sources)],
			[['comment'], 'string', 'max' => 255],
			[['external_id'], 'string', 'max' => 64],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(), [
			'user_id' => [
				'Сотрудник',
				'hint' => 'Конкретное трудоустройство сотрудника (users.id), к которому относится отсутствие',
				'placeholder' => 'Сотрудник',
				'join' => ['user'],
				'typeClass' => \app\types\LinkType::class,
			],
			'type' => [
				'Тип',
				'hint' => 'Нормализованный тип отсутствия. Код совпадает со словарём XML_ID типов '
					. 'отсутствия в Bitrix, чтобы выгрузка отображала тип один-в-один без второго словаря',
				'placeholder' => 'Тип отсутствия',
				'fieldList' => static::$types,
				'typeClass' => \app\types\ChoiceType::class,
			],
			'date_from' => [
				'С',
				'hint' => 'Дата начала периода отсутствия',
				'typeClass' => \app\types\DateType::class,
			],
			'date_to' => [
				'По',
				'hint' => 'Дата окончания периода отсутствия (включительно)',
				'typeClass' => \app\types\DateType::class,
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Текст отсутствия из источника (для SAP — поле Abstext)',
				'typeClass' => \app\types\StringType::class,
			],
			'source' => [
				'Источник',
				'hint' => 'Откуда пришла запись: sap / c1 / manual. Синхронизация из источника '
					. 'обновляет только свои записи, не трогая ручной ввод и другие источники',
				'placeholder' => 'Источник записи',
				'fieldList' => static::$sources,
				'typeClass' => \app\types\ChoiceType::class,
			],
			'external_id' => [
				'Внешний ключ',
				'hint' => 'Натуральный ключ записи в источнике для идемпотентного upsert '
					. '(для SAP: Pernr-Awart-Begda-Endda). Для ручного ввода — пусто',
				'placeholder' => 'нет (ручной ввод)',
				'typeClass' => \app\types\StringType::class,
			],
		]);
	}

	/**
	 * Сотрудник (трудоустройство), к которому относится отсутствие
	 * @return ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(Users::class, ['id' => 'user_id']);
	}
}
