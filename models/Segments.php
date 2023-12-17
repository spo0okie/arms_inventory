<?php

namespace app\models;

use app\helpers\ArrayHelper;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "segments".
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $description
 * @property string $history
 * @property string $links
 * @property Networks $networks
 * @property Services $services
 */
class Segments extends ArmsModel
{
	
	static $titles='Сегменты инфраструктуры';
	static $title='Сегмент инфраструктуры';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'segments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['name'], 'string', 'max' => 32],
            [['code','description'], 'string', 'max' => 255],
			[['history','links'], 'safe'],
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'id' => 'ID',
			'code' => [
				'Код CSS',
				'hint' => 'Название класса CSS для раскраски.',
			],
			'name' => [
				'Название',
				'hint' => 'Понятное человеку название',
			],
			'description' => [
				'Короткое описание',
				'hint' => 'Короткое описание сегмента, выводится в общем списке',
			],
			'history' => [
				'Подробное описание',
				'hint' => 'Подробное описание, чтобы увидеть надо будет открыть описание сегмента',
			],
		]);
	}
	
	/**
	 * @return ActiveQuery|Segments
	 */
	public function getNetworks()
	{
		return $this->hasMany(Networks::class, ['segments_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery|Segments
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['segment_id' => 'id']);
	}
	
	
	
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->orderBy(['name'=>SORT_ASC])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
	public function reverseLinks()
	{
		return [
			$this->networks,
			$this->services
		];
	}
	
}
