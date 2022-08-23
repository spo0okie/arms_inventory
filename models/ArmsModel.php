<?php

namespace app\models;

use app\components\DynaGridWidget;
use Yii;

/**
 * This is the model class for table "arms".
 *
 * @property int $id Идентификатор
 * @property string $name Имя экземпляра
 * @property string $sname Имя для поиска
 * @property string $comment Комментарий
 * @property string $history история
 * @property string $updatedAt Время обновления
 */
class ArmsModel extends \yii\db\ActiveRecord
{
	public static $title='Объект';
	public static $titles='Объекты';
	
	public const searchableOrHint='<br><i>HINT: Можно искать несколько вариантов, разделив их вертикальной</i> <b>|</b> <i>чертой</i>';
	
	
	private $attributeLabelsCache=null;
	/**
	 * Массив описания полей
	 */
	public function attributeData()
	{
		return [
			'id' => [
				'Идентификатор',
			],
			'comment' => [
				'Примечание',
				'hint' => 'Краткое пояснение по этому АРМ',
			],
			'history' => [
				'Записная книжка',
				'hint' => 'Все важные и не очень заметки и примечания по жизненному циклу этого АРМ',
			],
			'updated_at' => [
				'Время изменения',
			],
		];
	}

	
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
    	if (is_null($this->attributeLabelsCache)) {
			$this->attributeLabelsCache=[];
			foreach ($this->attributeData() as $key=>$data) {
				if (is_array($data)) {
					if (isset($data[0]))
						$this->attributeLabelsCache[$key]=$data[0];
					elseif (isset($data['label']))
						$this->attributeLabelsCache[$key]=$data['label'];
				} else $this->attributeLabelsCache[$key]=$data;
			}
		}
        return $this->attributeLabelsCache;
    }

	/**
	 * @inheritdoc
	 */
	public function attributeHints()
	{
		$hints=[];
		foreach ($this->attributeData() as $key=>$data)
			if (is_array($data) && isset($data['hint']))
				$hints[$key]=$data['hint'];
		return $hints;
	}
	
	/**
	 * Возвращает наименование атрибута для формы поиска
	 * @param $attribute
	 * @return string
	 */
	public function getAttributeIndexLabel($attribute)
	{
		$data=$this->attributeData();
		if (isset($data[$attribute])) {
			$item=$data[$attribute];
			if (is_array($item) && isset($item['indexLabel']))
				return $item['indexLabel'];
		}
		return $this->getAttributeLabel($attribute);
	}
	
	
	/**
	 * Возвращает описание атрибута для формы поиска
	 * @param $attribute
	 * @return string
	 */
	public function getAttributeIndexHint($attribute)
	{
		$data=$this->attributeData();
		if (isset($data[$attribute])) {
			$item=$data[$attribute];
			if (isset($item['indexHint']))
				return str_replace(
					'{same}',
					$this->getAttributeHint($attribute),
					$item['indexHint']
				);
		}
		return null;
	}
	
	
	public static function fetchNames(){
		$list= static::find()
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}

	public function getSname(){
		return $this->name;
	}
}
