<?php

namespace app\models;


use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "materials_types".
 *
 * @property int $id id
 * @property int $scans_id титульная картинка
 * @property string $code Код
 * @property string $name Название
 * @property string $units Ед. изм
 * @property string $comment Комментарий
 * @property Materials[] $materials
 * @property Scans[] $scans
 */
class MaterialsTypes extends ArmsModel
{
	
	public static $title='Категория материалов';
	public static $titles='Категории материалов';
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
	public function attributeData()
	{
		return [
			'id' => [
				'id',
			],
			'code' => [
				'Код',
				'hint' => 'Может использоваться впоследствии для отдельных обработчиков событий и генерации CSS классов в формах просмотра и отчетах',
			],
			'name' => [
				'Название',
				'hint' => 'Название категории ЗиП и материалов',
			],
			'rest' => [
				'Остаток',
				'indexHint' => 'Суммарный остаток всех материалов этого типа',
			],
			'units' => [
				'Ед. изм',
				'hint' => 'Единицы измерения материалов этой категории (штуки/метры/килограммы)',
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Все что нужно знать про эту категорию материалов сверх уже внесенной информации',
			],
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getMaterials()
	{
		return $this->hasMany(Materials::className(), ['type_id' => 'id']);
	}
	
	public function getRest()
	{
		$rest=0;
		
		foreach ($this->materials as $material)
			$rest+=$material->rest;
		
		return $rest;
	}
	

	public static function fetchNames(){
		$list= static::find()->orderBy('name')
			//->joinWith('place')
			//->select(['id','name'])
			->all();
		return ArrayHelper::map($list, 'id', 'name');
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getScans()
	{
		/** @var Scans $scans */
		$scans=Scans::find()->where(['material_models_id' => $this->id ])->all();
		$scans_sorted=[];
		foreach ($scans as $scan) if($scan->id == $this->scans_id) $scans_sorted[]=$scan;
		foreach ($scans as $scan) if($scan->id != $this->scans_id) $scans_sorted[]=$scan;
		return $scans_sorted;
	}
	
	
	
}
