<?php

namespace app\models;

use app\components\Forms\ArmsForm;
use Yii;

/**
 * This is the model class for table "ports".
 *
 * @property int $id
 * @property int $techs_id
 * @property string  $name
 * @property string  $comment
 * @property int     $link_techs_id
 * @property int     $link_ports_id
 * @property string  sname
 * @property string  deviceName
 * @property string  fullName
 *
 * @property Ports   $linkPort
 * @property Techs   $linkTech
 * @property Techs   $tech
 */
class Ports extends ArmsModel
{
	public $link_arms_id;
	public $link_techs_id;
	
	public static $port_prefix='Порт ';
	public static $tech_postfix=': ';
	public static $null_port='0';
	
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
            [['techs_id', 'link_techs_id'], 'integer'],
			[['link_ports_id'],function ($attribute, $params, $validator) {
				if (
					!empty($this->link_ports_id)
				&&
					!is_numeric($this->link_ports_id)
				&&
					strpos($this->link_ports_id,'create:')!==0
				) {
					$this->addError($attribute, "Неверный порт устройства");
				}
			}],
			[['techs_id', 'link_techs_id','link_ports_id','name'], 'default','value'=>null],
			[['name'], 'unique', 'skipOnError' => true, 'skipOnEmpty'=>false, 'targetAttribute'=>['name','arms_id','techs_id'],'message'=>'Такой порт на этому устройстве уже объявлен'],
			[['name'], 'string', 'max' => 32],
            [['comment'], 'string', 'max' => 255],
            [['link_techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['link_techs_id' => 'id']],
			[['techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['techs_id' => 'id']],
        ];
    }
	
	public $linksSchema=[
		'techs_id'=>[Techs::class,'ports_ids'],
		'link_ports_id'=>[Ports::class,'link_ports_id'],
	];
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'techs_id' => [
				'На устройстве',
				'hint' => 'На каком устройстве расположен порт',
				'placeholder' => 'Выберите устройство',
			],
			'name' => [
				'Наименование',
				'hint' => 'Номер или маркировка порта (1/24/Combo 1/iLO/Console/Management)',
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Детали по порту / соединению до удаленного устройства',
			],
			'link_techs_id' => [
				'Подсоединенное устройство',
				'hint' => 'Подсоединенное устройство',
				'placeholder' => 'Выберите устройство',
			],
			'link_ports_id' => [
				'Порт на устройстве',
				'hint' => 'Если оставить пустым, то будет объявлено соединение с устройством, без указания конкретного порта',
				'placeholder' => 'Укажите порт на устройстве',
			],
		];
	}
	
	
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinkPort()
    {
        return $this->hasOne(Ports::className(), ['id' => 'link_ports_id'])
			->from(['port_linked_ports'=>Ports::tableName()]);
    }
    
	
	public function getTemplateComment()
	{
		if (is_object($this->tech))
			return $this->tech->getModelPortComment($this->name);
		
		return null;
	}
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getLinkTech()
	{
		return $this->hasOne(Techs::className(), ['id' => 'techs_id'])
			->via('linkPort');
	}
	
	/**
     * @return \yii\db\ActiveQuery
    public function getPort()
    {
        return $this->hasOne(Ports::className(), ['link_ports_id' => 'id'])
			->from(['port_parent_ports'=>Ports::tableName()]);
    }
	 */

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
		return $this->name?$this->name:static::$null_port;
	}
	
	/**
	 * Name of port device
	 * @return string
	 */
	public function getDeviceName() {
		if ($this->techs_id) return $this->tech->num;
		return 'NO DEVICE (ERR)';
	}
	
	/**
	 * Name of device-port
	 * @return string
	 */
	public function getFullName($reverse=false)
	{
		return $reverse?
			(static::$port_prefix.$this->sname.static::$tech_postfix.$this->deviceName):
			($this->deviceName.static::$tech_postfix.static::$port_prefix.$this->sname);
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
			
			//https://wiki.reviakin.net/инвентаризация:dev:ports
			//error_log('before_save '.$this->id.' -> '.$this->link_ports_id);
			
			$tpl=$this->getTemplateComment();
			$reversePort=null;
			if (!is_numeric($this->link_ports_id)) {
				//если нам нужно создать порт - создаем
				if (strlen($this->link_ports_id) && strpos($this->link_ports_id,'create:')===0) {
					$reversePort=new Ports();
					$tokens=explode(':',$this->link_ports_id);
					//нужно создать новый порт с именем
					if (strlen($tokens[1]))		$reversePort->name=$tokens[1];
					//привязываем оборудование
					if ($this->link_techs_id)	$reversePort->techs_id=$this->link_techs_id;
				
				} elseif ($this->link_techs_id) {
					//порт не передан числом (ID) и не тестовая директива создания - считаем что тогда NULL
					//если при этом указано оборудование/АРМ - значит надо привязаться к порту NULL на этом оборудовании
					//ищем такой порт
					$reversePort=Ports::find()
						->where(['and',
							['techs_id'=>$this->link_techs_id],
							['name'=>null]
						])
						->one();
					
					//если не нашли-создаем
					if (!is_object($reversePort)) {
						$reversePort=new Ports();
						$reversePort->techs_id=$this->link_techs_id;
					}
					
				} elseif (!strlen(trim($this->comment)) || $this->comment==$tpl) {
					//мы вообще ни к чему не привязываемся и у нас даже комментария нет
					if (!$insert) {
						//если это обновление (не вставка)
						$this->delete();
						//ну и такое мы не сохраняем. Привязок нет, комментариев нет. А что сохранять то?
						return false;
						//почему удаляем только на обновлении?
						//потому что возможен сценарий когда мы создаем одновременно 2 порта.
						//новый ссылается на новый (директива create)
						//тогда после create нам нужно его сохранить и получить ID,
						//но на момент сохранения у него не будет ничего
						//такой вроде бы пустой, но нужный порт надо сохранять
					}
				}
			}
			
			if (is_object($reversePort)) {
				$reversePort->save();
				$reversePort->refresh();
				$this->link_ports_id=$reversePort->id;
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
				$changedAttributes['link_ports_id']!=(int)$this->link_ports_id
				&&
				is_object($oldPort=Ports::findOne($changedAttributes['link_ports_id']))
				&&
				$oldPort->link_ports_id==$this->id //тут может быть момент что тот с кем мы раньше были связаны уже от нас отвязался
			){
				//если получилось загрузить порт с которым были связаны
				//отвязываемся
				$oldPort->link_ports_id=null;
				if (empty($oldPort->name) && empty($oldPort->comment))
					$oldPort->delete();
				else
					$oldPort->save(false);
			}
		}
		
		//error_log($this->id.'->'.$this->link_ports_id);
		//if (!is_object($newPort=$this->linkPort)) error_log($this->id.'->'.$this->link_ports_id.' - not an obj');
		//else error_log($this->id.'->'.$this->link_ports_id.'->'.$this->linkPort->link_ports_id);
		//также это может означать что теперь мы привязаны к новому порту, который не привязан к нам
		if (
			!empty($this->link_ports_id)
			&&
			is_object($newPort=$this->linkPort)
			&&
			$newPort->link_ports_id!=$this->id
		){
			//error_log('reversing '.$newPort->id.'->'.$this->id);
			//если получилось загрузить порт с которым стали связаны
			//и который не связан с нами - связываем
			$newPort->link_ports_id=$this->id;
			$newPort->save(false);
			//$newPort->refresh();
		} //else error_log('no reverse link for '.$this->id);
	}
}