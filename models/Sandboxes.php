<?php

namespace app\models;

use voskobovich\linker\LinkerBehavior;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "sandboxes".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $notepad
 * @property int|null $network_accessible
 * @property string|null $links
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string sname
 * @property Comps $comps
 */
class Sandboxes extends ArmsModel
{

public static $title='Песочница';
public static $titles='Песочницы';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sandboxes';
    }

	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'comps_ids'=>'comps'
				]
			]
		];
	}


/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'notepad', 'network_accessible', 'links', 'updated_at', 'updated_by'], 'default', 'value' => null],
            [['notepad', 'links'], 'string'],
            [['network_accessible','archived'], 'integer'],
			[['name'], 'string', 'max' => 64],
			[['suffix'], 'string', 'max' => 12],
			[['updated_at'], 'safe'],
            [['updated_by'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return array_merge(parent::attributeData(),[
			'name' => [
				'Название',
				'hint'=>'Название изолированного окружения',
			],
			'suffix' => [
				'Суффикс',
				'hint'=>'Суффикс будет выводится после имен ВМ для отличия клонов в песочнице от продуктивных ВМ',
			],
			'network_accessible' => [
				'Доступно по сети',
				'hint'=>'Есть ли сетевая связность с этим окружением',
			],
        ]);
    }
	
	/**
	 * @return ActiveQuery
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['sandbox_id' => 'id']);
	}
	
	
}