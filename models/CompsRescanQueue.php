<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;

/**
 * This is the model class for table "comps_rescan_queue".
 *
 * @property int $id
 * @property int $comps_id
 * @property int $soft_id
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string sname
 */
class CompsRescanQueue extends ArmsModel
{

	public static $title='Запланированный рескан';
	public static $titles='Запланированные ресканы';

	public static function modelDescription(): string
	{
		return 'Служебная очередь повторного распознавания ПО: задания на повторный '
			.'разбор отпечатка софта ОС (например после правки выражений продукта).';
	}

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'comps_rescan_queue';
    }
	
	public $linksSchema=[
		'comps_id' =>			Comps::class,
		'soft_id' =>			Soft::class,
	];
	
	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at', 'updated_by'], 'default', 'value' => null],
            [['comps_id', 'soft_id'], 'required'],
            [['comps_id', 'soft_id'], 'integer'],
            [['updated_at'], 'safe'],
            [['updated_by'], 'string', 'max' => 32],
		];
    }

    /**
     * {@inheritdoc}
     */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'comps_id' => [
				'OS/VM',
				'hint' => 'Операционная система, для которой запланирован повторный разбор отпечатка',
				'indexHint' => 'Операционная система для сканирования',
			],
			'soft_id' => [
				'ПО',
				'hint' => 'Программный продукт, из-за которого запланирован рескан',
				'indexHint' => 'Программное обеспечение, которое инициировало повторное сканирование',
			],
			'updated_by' => [
				'Обновил',
				'hint' => 'Кто создал задание (заполняется автоматически)',
			],
        ]);
    }
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getSoft()
	{
		return $this->hasOne(Soft::class, ['id' => 'soft_id']);
	}

}