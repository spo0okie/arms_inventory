<?php

namespace app\models;

use Yii;
use yii\validators\IpValidator;

/**
 * This is the model class for table "techs".
 *
 * @property int $id Идентификатор
 * @property string $num Инвентарный номер
 * @property string $inv_num Бухгалтерский инвентарный номер
 * @property int $model_id Модель оборудования
 * @property string $sn Серийный номер
 * @property string $specs Тех. спецификация
 * @property int $arms_id Рабочее место
 * @property int $places_id Помещение
 * @property int $state_id Состояние
 * @property string $user_id Пользователь
 * @property string $it_staff_id Сотрудник службы ИТ
 * @property string $ip IP адрес
 * @property string $mac MAC адрес
 * @property string $formattedMac MAC адрес с двоеточиями
 * @property string $url Ссылка
 * @property string $comment Комментарий
 * @property string $stateName статус
 * @property bool $isVoipPhone Является IP телефоном
 * @property bool $isUps Является UPS
 * @property bool $isPc Является ПК
 * @property bool $isMonitor Является Монитором
 * @property bool $archived Списано
 * @property array $contracts_ids Список документов
 * @property array $netIps_ids Список IP
 * @property array $portsList
 * @property array $ddPortsList
 *
 * @property Users $user
 * @property Users $itStaff
 * @property Arms $arm
 * @property Places $place
 * @property Places $effectivePlace
 * @property Places $origPlace
 * @property TechStates $state
 * @property TechModels $model
 * @property TechTypes $type
 * @property Contracts[] $contracts
 * @property Services[] $services
 * @property Ports[] $ports
 * @property MaterialsUsages[] $materialsUsages
 * @property NetIps[] $netIps
 * @property Segments[] $segments
 * @property Acls[] $acls
 *
 * @property int $scans_id Картинка - предпросмотр
 * @property Scans[] $scans
 * @property Scans $preview
 
 */

class Techs extends \yii\db\ActiveRecord
{

	private static $num_str_pad=4; //количество знаков в цифровой части номера
	private $model_cache=null;
	private $state_cache=null;
	private $type_cache=null;

	public static $title='Оборудование';

	public static $descr='Оргтехника, сетевое оборудование, сервера и вообще все, что является предметом ответственности ИТ службы, имеет материальное представление, но не является АРМом.';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'techs';
    }
    
    public function extraFields()
	{
		return [
			'site' //площадка - помещение верхнего уровня относительно помещения где размещено оборудование
		];
	}
	

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['model_id'], 'required'],
	        [['model_id', 'arms_id', 'places_id', 'state_id', 'scans_id'], 'integer'],
	        [['contracts_ids'], 'each', 'rule'=>['integer']],
	        [['url', 'comment'], 'string'],
	        [['history','specs'], 'safe'],
	        [['num', 'user_id', 'it_staff_id'], 'string', 'max' => 16],
			[['inv_num', 'sn'], 'string', 'max' => 128],
			[['ip', 'mac'], 'string', 'max' => 255],
			['ip', function ($attribute, $params, $validator) {
				\app\models\NetIps::validateInput($this,$attribute);
			}],
			['ip', 'filter', 'filter' => function ($value) {
				return \app\models\NetIps::filterInput($value);
			}],
			//['ip', 'ip','ipv6'=>false,'subnet'=>null],
	        [['user_id', 'it_staff_id'], 'filter', 'filter' => function ($value) {
        	    //заменяем пустые значения табельных номеров на NULL
		        return strlen($value)?$value:null;
	        }],
	        ['num', function ($attribute, $params, $validator) {
		        if (count(explode('-',$this->$attribute))!==3) {
			        $this->addError($attribute, 'Инвентарный номер должен быть в формате "ФИЛ-ТИП-НОМЕР", где ФИЛ - префикс филиала, ТИП - тип оборудования, НОМЕР - целочисленный номер уникальный в рамках филиала.');
		        }
	        }],
	        ['num', 'filter', 'filter' => function ($value) {
		        // выполняем определенные действия с переменной, возвращаем преобразованную переменную
		        $tokens=explode('-',$value);
		        $num=(int)$tokens[count($tokens)-1];
		        $tokens[count($tokens)-1]=str_pad((string)$num,static::$num_str_pad,'0',STR_PAD_LEFT);
		        return mb_strtoupper(implode('-',$tokens));
	        }],
	        ['num', function ($attribute, $params, $validator) {
		        $same=static::findOne([$attribute=>$this->$attribute]);
		        if (is_object($same)&&($same->id != $this->id)) {
			        $tok =explode('-',$this->$attribute);
			        if (count($tok)) unset($tok[count($tok)-1]);
			        $pref=implode('-',$tok);
			        $next=static::fetchNextNum($pref);
			        $this->addError($attribute, "Инвентарный номер {$this->$attribute} уже занят, следующий свободный номер с префиксом $pref - $next");
		        }
	        }],
	        ['num', 'unique'],
	        [['state_id'], 'exist', 'skipOnError' => true, 'targetClass' => TechStates::className(), 'targetAttribute' => ['state_id' => 'id']],
	        [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['it_staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['it_staff_id' => 'id']],
            [['arms_id'], 'exist', 'skipOnError' => true, 'targetClass' => Arms::className(), 'targetAttribute' => ['arms_id' => 'id']],
            [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::className(), 'targetAttribute' => ['places_id' => 'id']],
        ];
    }

	/**
	 * В списке поведений прикручиваем many-to-many контрагентов
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'contracts_ids' => 'contracts',
					'services_ids' => 'services',
					'netIps_ids' => 'netIps',
				]
			]
		];
	}


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор',
            'num' => 'Инвентарный номер',
            'inv_num' => 'Бухг. номер',
	        'model_id' => 'Модель оборудования',
	        'model' => 'Модель оборудования',
            'sn' => 'Серийный номер',
			'specs' => 'Тех. спецификация',
	        'state_id' => 'Состояние',
	        'state' => 'Статус',
	        'attach' => 'Док.',
	        'arms_id' => 'Рабочее место',
	        'places_id' => 'Помещение',
	        'place' => 'Помещение',
	        'user_id' => 'Пользователь',
	        'user' => 'Пользователь',
	        'contracts_ids' => 'Связанные документы',
            'it_staff_id' => 'Сотрудник службы ИТ',
	        'ip' => 'IP адреса',
	        'mac' => 'MAC адреса',
            'url' => 'Ссылки',
	        'comment' => 'Примечание',
	        'history' => 'Записная книжка',
        ];
    }


	/**
	 * @inheritdoc
	 */
	public function attributeHints()
	{
		return [
			'num' => 'Внутренний инвентарный номер в службе ИТ.',
			'inv_num' => 'Бухгалтерский инвентарный / номенклатурный номер.',
			'model_id' => 'Модель устанавливаемого оборудования. Если нужная модель отустствует в списке, то нужно сначала завести в ее в соотв. категории оборудования',
			'sn' => 'Серийный номер оборудования. Если явно нет, то MAC/IMEI/и т.п. чтобы можно было однозначно идентифицировать оборудование',
			'state_id' => 'Состояние, в котором в данный момент находится оборудование',
			'arms_id' => 'Рабочее место, к которому прикреплено оборудование',
			'user_id' => 'Сотрудник которому установлено',
			'specs' => 'Спецификация оборудования в случае, если модель оборудования не полностью определяет комплектацию каждого отдельного экземпляра',
			'places_id' => 'Помещение, куда установлено',
			'it_staff_id' => 'Сотрудник службы ИТ, который отвечает за обслуживание оборудования',
			'head_id' => 'Руководитель отдела сотрудника которому установлено',
			'contracts_ids' => 'Счета, накладные, фотографии серийных номеров и т.п.',
			'url' => \app\components\UrlListWidget::$hint,
			'comment' => 'Краткое пояснение по этому оборудованию',
			'history' => 'Все важные и не очень заметки и примечания по жизненному циклу этого АРМ',
			'ip' => 'По одному в строке',
			'mac' => 'По одному в строке',
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getState()
	{
		if (!is_null($this->state_cache)) return $this->state_cache;
		return $this->state_cache=$this->hasOne(TechStates::className(), ['id' => 'state_id']);
	}

	public function getStateName()
	{
		if (is_object($this->state)) return $this->state->name;
		return '';
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getScans()
	{
		$scans=Scans::find()->where(['techs_id' => $this->id ])->all();
		$scans_sorted=[];
		foreach ($scans as $scan) if($scan->id == $this->scans_id) $scans_sorted[]=$scan;
		foreach ($scans as $scan) if($scan->id != $this->scans_id) $scans_sorted[]=$scan;
		return $scans_sorted;
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getPreview()
	{
		//ищем собственную картинку
		if ($this->scans_id && is_object($scan=Scans::find()->where(['id' => $this->scans_id ])->one())) return $scan;

		//ищем картинку от модели
		if (is_object($this->model)) return $this->model->preview;

		//сдаемся
		return null;
	}
	
	
	/**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
	    if (is_object($this->arm)) return $this->arm->user;
        return $this->techUser;
    }

	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechUser()
	{
		return $this->hasOne(Users::className(), ['id' => 'user_id'])->from(['users_techs'=>Users::tableName()]);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getArmUser()
	{
		return $this->arm->user;
	}
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getItStaff()
	{
		if (is_object($this->arm))  return $this->arm->itStaff;
		return $this->hasOne(Users::className(), ['id' => 'it_staff_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getModel()
	{
		//if (isset($this))
		return $this->model_cache=$this->hasOne(TechModels::className(), ['id' => 'model_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAttachModel()
	{
		return $this->model_cache=$this->hasOne(TechModels::className(), ['id' => 'model_id'])
			->from(['attached_tech_models'=>TechModels::tableName()]);

	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMaterialsUsages()
	{
		return $this->hasMany(MaterialsUsages::className(), ['techs_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPorts()
	{
		return $this->hasMany(Ports::className(), ['techs_id' => 'id']);
	}
	
	/**
	 * @return TechTypes
	 */
	public function getType()
	{
		if (!is_null($this->type_cache)) return $this->type_cache;
		return $this->type_cache=$this->model->type;
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getArm()
    {
        return $this->hasOne(Arms::className(), ['id' => 'arms_id']);
    }

	/**
	 * @return \app\models\Places;
	 */
	public function getEffectivePlace()
	{
		return (is_object($this->arm)) ?$this->arm->place:$this->place;
		//return $this->hasOne(Places::className(), ['id' => 'places_id']);
	}

	public function getSite()
	{
		if (!is_object($place=$this->effectivePlace)) return null;
		return $place->top;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::className(), ['id' => 'places_id'])
			->from(['places_techs'=>Places::tableName()]);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrigPlace()
	{
		//if ($this->arms_id) return $this->arm->place;
		return $this->hasOne(Places::className(), ['id' => 'places_id'])
			->from(['places_techs'=>Techs::tableName()]);
	}

	public function getIsVoipPhone() {
		return \app\models\TechModels::getIsPhone($this->model_id);
    }
	
	public function getIsUps() {
		return \app\models\TechModels::getIsUps($this->model_id);
	}
	
	public function getIsMonitor() {
		return \app\models\TechModels::getIsMonitor($this->model_id);
	}
	
	public function getArchived()
	{
		return is_object($this->state)?$this->state->archived:false;
	}
	
	public static function formatMacs($raw) {
		
		$macs=explode("\n",$raw);
		
		foreach ($macs as $k=>$mac) {
			$rawMac=preg_replace('/[^0-9A-F]/', '', mb_strtoupper($mac));
			$macTokens=[];
			for ($i=0;$i<mb_strlen($rawMac);$i++){
				if (!isset($macTokens[(int)($i/2)])) $macTokens[(int)($i/2)]='';
				$macTokens[(int)($i/2)].=mb_substr($rawMac,$i,1);
			}
			$macs[$k]=implode(':',$macTokens);
		}
		
		return implode("\n",$macs);
	}
	
	public function getFormattedMac() {
		
		return static::formatMacs($this->mac);
	}
	
	/**
	 * Имя для поиска
	 * @return string
	 */
	public function getSname()
	{
		return $this->num.' ('.$this->type->name.' '.$this->model->name.')'.
			(
				!is_null($this->places_id)?
					(' в '.\app\models\Places::fetchFullName($this->places_id))
					:''
			);
	}
	
	
	
	/**
	 * Возвращает IP адреса
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::className(), ['id' => 'ips_id'])->from(['techs_ip'=>NetIps::tableName()])
			->viaTable('{{%ips_in_techs}}', ['techs_id' => 'id']);
	}
	
	
	public function getSegments() {
		$segments=[];
		foreach ($this->netIps as $ip)
			if (is_object($ip)){
				if (is_object($segment=$ip->segment))
					$segments[$segment->id]=$segment;
			}
		return $segments;
	}
	
	
	/**
	 * Возвращает первый свободный инвентарный номер с заданным инв. номером
	 * @param $idx текущий инв. номер
	 * @return integer номер следующей позиции
	 */
	public static function fetchNextNum($idx) {
		$last=static::find()->where(['like','num',$idx])->orderBy(['num'=>SORT_DESC])->one();
		if (is_object($last)) {
			$tokens = explode('-', $last->num);
			$subidx = (int)$tokens[count($tokens)-1] + 1;
		} else $subidx=1;
		$num=str_pad((string)$subidx,static::$num_str_pad,'0',STR_PAD_LEFT);
		return $idx.'-'.$num;

	}

	/**
	 * Формирует префикс инвентарного номера оборудования на основании типа и места установки
	 * @param $model_id integer модель оборудования
	 * @param $place_id integer помещение
	 * @param $arm_id integer АРМ
	 * @return string префикс инвентарного номера
	 */
	public static function genInvPrefix($model_id,$place_id,$arm_id)
	{
		$tokens=[];

		if ($arm_id) {
			//если есть АРМ - то место установки там где АРМ
			$arm=\app\models\Arms::findOne($arm_id);
			if (is_object($arm)) $place=$arm->place;
		} elseif ($place_id) {
			//иначе там где место установки
			$place=\app\models\Places::findOne($place_id);
		} else $place=null;
		if (is_object($place)) {
			//если нашли место установки, то ищем там префикс
			$place_token=$place->prefTree;
			//если есть, то добавляем
			if (strlen($place_token)) $tokens[]=$place_token;
		}
		//ищем модель оборудования
		$model=\app\models\TechModels::findOne($model_id);
		if (is_object($model)) {
			//если все ок то берем префикс типа оборудования
			$tech_token=$model->type->prefix;
			//если он не пустой то добавляем
			if (strlen($tech_token)) $tokens[]=$tech_token;
		}

		//или цепочка из префиксов или пусто
		return count($tokens)?implode('-',$tokens):'';
	}
	
	/**
	 * Возвращает набор документов
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_techs}}', ['techs_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы
	 */
	public function getServices()
	{
		return $this->hasMany(Services::className(), ['id' => 'service_id'])
			->viaTable('{{%techs_in_services}}', ['tech_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::className(), ['techs_id' => 'id']);
	}
	
	
	/**
	 * Возвращает массив портов (фактически объявленных или потенциально возможных исходя из модели оборудования)
	 */
	public function getPortsList()
	{
		//Порты которые должны быть у этой модели оборудования
		$model_ports=[];

		//Порты которые объявлены в БД конкретно для этого устройства
		$custom_ports=$this->ports;
		if (!is_array($custom_ports)) $custom_ports=[];
		
		//если корректно пришита модель оборудования и у модели есть набор портов
		if (is_object($this->model) && count($this->model->portsList)) {
			//перебираем распарсеные порты
			foreach ($this->model->portsList as $port_name=>$port_comment) {
				//ищем есть ли порт-объект к этому порту
				$port_link=null;
				foreach ($custom_ports as $i=>$custom_port) if ($custom_port->name == $port_name) {
					$port_link=$custom_port;
					unset($custom_ports[$i]); //убираем обработанный порт из пула существующих для этого устройства
				}
				$model_ports[$port_name]=compact('port_name','port_comment','port_link');
			}
		}
		
		//если какие-то порты не ушли через список выше - добавляем их в конец
		foreach ($custom_ports as $port) {
			$model_ports[$port->name]=[
				'port_name'=>$port->name,
				'port_comment'=>$port->comment,
				'port_link'=>$port
			];
		}
		
		return $model_ports;
	}
	
	public function getDdPortsList()
	{
		$out=[];
		foreach ($this->portsList as $name=>$port) {
			$out[]=[
				'id'=>is_object($port['port_link'])?$port['port_link']->id:"create:$name",
				'name'=>$name
			];
		}
		return $out;
	}
	
	/**
	 * Возвращает комментарий порта из шаблона модели
	 */
	public function getModelPortComment($port)
	{
		if (is_object($this->model))
			return $this->model->getPortComment($port);
		else
			return null;
	}
	
	
	public static function fetchNames(){
		$list= static::find()
			->joinWith(['model','model.type','origPlace','arm','arm.place'])
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			//fix: https://github.com/spo0okie/arms_inventory/issues/15
			//если привязаны к АРМ,
			if (is_object($this->arm)) {
				//то отвязываемся от собственных помещения и пользователя
				//т.к. теперь мы косвенно привязаны к помещению и пользователю АРМ
				$this->places_id=$this->arm->places_id;
				$this->user_id=$this->arm->user_id;
				$this->it_staff_id=$this->arm->it_staff_id;
			}
			
			$this->mac=\app\helpers\MacsHelper::fixList($this->mac);
			
			/* взаимодействие с NetIPs */
			$this->netIps_ids=NetIps::fetchIpIds($this->ip);
			
			//грузим старые значения записи
			$old=static::findOne($this->id);
			if (!is_null($old)) {
				//находим все IP адреса которые от этой ОС отвалились
				$removed = array_diff($old->netIps_ids, $this->netIps_ids);
				//если есть отвязанные от это ос адреса
				if (count($removed)) foreach ($removed as $id) {
					//если он есть в БД
					if (is_object($ip=NetIps::findOne($id))) $ip->detachTech($this->id);
				}
			}
			return true;
		}
		return false;
	}
	
	
	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) {
			return false;
		}
		
		//отрываем IP от удаляемого компа
		foreach ($this->netIps as $ip) {
			$ip->detachTech($this->id);
		}
		
		return true;
	}
	
}
