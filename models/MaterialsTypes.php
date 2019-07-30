<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "materials_types".
 *
 * @property int $id id
 * @property string $code Код
 * @property string $name Название
 * @property string $units Ед. изм
 * @property string $comment Комментарий
 */
class MaterialsTypes extends \yii\db\ActiveRecord
{

	public static $title='Категории материалов';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name', 'units'], 'required'],
            [['comment'], 'string'],
            [['code'], 'string', 'max' => 12],
            [['name'], 'string', 'max' => 128],
            [['units'], 'string', 'max' => 16],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'id',
			'code' => 'Код',
			'name' => 'Название',
			'units' => 'Ед. изм',
			'comment' => 'Комментарий',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'id',
			'code' => 'Может использоваться впоследствии для отдельных обработчиков событий и генерации CSS классов в формах просмотра и отчетах',
			'name' => 'Название категории ЗиП и материалов',
			'units' => 'Единицы измерения материалов этой категории (штуки/метры/килограммы)',
			'comment' => 'Все что нужно знать про эту категорию материалов сверх уже внесенной информации',
		];
	}

	public static function fetchNames(){
		$list= static::find()->orderBy('name')
			//->joinWith('place')
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
