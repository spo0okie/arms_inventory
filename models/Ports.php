<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ports".
 *
 * @property int $id
 * @property int $techs_id
 * @property string $name
 * @property string $comment
 * @property int $link_techs_id
 * @property int $link_arms_id
 * @property int $link_ports_id
 * @property string sname
 *
 * @property Arms $linkArm
 * @property Ports $linkPort
 * @property Techs $linkTech
 * @property Techs $tech
 */
class Ports extends \yii\db\ActiveRecord
{
	
	public static $port_prefix='Порт ';
	public static $tech_postfix=': ';
	
	public static $title='Сетевой порт';
	public static $titles='Сетевые порты';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ports';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['techs_id'], 'required'],
            [['techs_id', 'link_techs_id', 'link_arms_id'], 'integer'],
			[['link_ports_id'],function ($attribute, $params, $validator) {
				if (!is_numeric($this->$attribute)) {
					if (
						!(strlen($this->$attribute)>strlen('create:1@1'))
						&&
						!(substr($this->$attribute,0,strlen('create:'))=='create:')
					)
						$this->addError($attribute, "Неверный порт устройства");
				}
			}],
	
			[['name'], 'string', 'max' => 32],
            [['comment'], 'string', 'max' => 255],
            [['link_arms_id'], 'exist', 'skipOnError' => true, 'targetClass' => Arms::className(), 'targetAttribute' => ['link_arms_id' => 'id']],
            //[['link_ports_id'], 'exist', 'skipOnError' => true, 'targetClass' => Ports::className(), 'targetAttribute' => ['link_ports_id' => 'id']],
            [['link_techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['link_techs_id' => 'id']],
            [['techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['techs_id' => 'id']],
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'techs_id' => 'На устройстве',
			'name' => 'Наименование',
			'comment' => 'Комментарий',
			'link_arms_id' => 'Подсоединенный АРМ',
			'link_techs_id' => 'Подсоединенное устройство',
			'link_ports_id' => 'Порт на устройстве',
		];
	}
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'techs_id' => 'На каком устройстве расположен порт',
			'name' => 'Номер или маркировка порта (1/24/Combo 1/iLO/Console/Management)',
			'comment' => 'Детали по порту / соединению до удаленного устройства',
			'link_arms_id' => 'Подсоединенный АРМ',
			'link_techs_id' => 'Подсоединенное устройство',
			'link_ports_id' => 'Если оставить пустым, то будет объявлено соединение с устройством, без указания конкретного порта',
		];
	}
	
	
	/**
     * @return \yii\db\ActiveQuery
     */
    public function getLinkArm()
    {
        return $this->hasOne(Arms::className(), ['id' => 'link_arms_id'])
			->from(['port_linked_arms'=>Arms::tableName()]);;;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinkPort()
    {
        return $this->hasOne(Ports::className(), ['id' => 'link_ports_id'])
			->from(['port_linked_ports'=>Ports::tableName()]);;;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPort()
    {
        return $this->hasOne(Ports::className(), ['link_ports_id' => 'id'])
			->from(['port_parent_ports'=>Ports::tableName()]);;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinkTech()
    {
        return $this->hasOne(Techs::className(), ['id' => 'link_techs_id'])
			->from(['port_linked_techs'=>Techs::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTech()
    {
        return $this->hasOne(Techs::className(), ['id' => 'techs_id']);
    }

	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return $this->name;
	}


	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
			->orderBy(['name'=>SORT_ASC])
            ->all();
        if (!is_array($list)) $list=[];
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }
	
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			
			if (!empty($this->link_ports_id)) {
				$this->link_arms_id=null;
				$this->link_techs_id=null;
				
				if (
					(strlen($this->link_ports_id)>strlen('create:1@1'))
					&&
					(substr($this->link_ports_id,0,strlen('create:'))=='create:')
				) {
					$tokens=explode(':',$this->link_ports_id);
					$subTokens=explode('@',$tokens[1]);
					$newPort=new Ports();
					$newPort->link_ports_id=$this->id;
					$newPort->name=$subTokens[0];
					$newPort->techs_id=$subTokens[1];
					$newPort->save();
					$newPort->refresh();
					$this->link_ports_id=$newPort->id;
				}
				
			} elseif (!empty($this->link_techs_id)) {
				$this->link_ports_id=null;
				$this->link_arms_id=null;
			} elseif (!empty($this->link_arms_id)) {
				$this->link_ports_id=null;
				$this->link_techs_id=null;
			}
			return true;
		}
		return false;
	}
	
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		//если изменился порт
		if (isset($changedAttributes['link_ports_id'])) {
			//значит ранее были были привязаны к другому порту а теперь нет.
			if (
				!empty($changedAttributes['link_ports_id'])
				&&
				is_object($oldPort=Ports::findOne($changedAttributes['link_ports_id']))
			){
				//если получилось загрузить порт с которым были связаны
				//отвязываемся
				$oldPort->link_ports_id=null;
				$oldPort->save();
			}

		}

		if (
			!empty($this->link_ports_id)
		
			//&&
			//is_object($this->linkPort)
		){
			//если новый порт не связан с этим - связываем
			if ($this->linkPort->link_ports_id!=$this->id) {
				$this->linkPort->link_ports_id=$this->id;
				$this->linkPort->save();
			}
		}
		
	}
}