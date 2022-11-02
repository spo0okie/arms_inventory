<?php

namespace app\models;

use app\helpers\QueryHelper;
use Yii;
use yii\helpers\Html;

/**
 * This is the model class for table "arms".
 *
 * @property int $id Идентификатор
 * @property string $num Инвентарный номер
 * @property string $hostname
 * @property string $name
 * @property string $sname
 * @property string $inv_num Бухгалтерский инвентарный номер
 * @property int $comp_id Основная ОС рабочего места
 * @property string $model Модель системного блока
 * @property int $model_id Модель оборудования
 * @property string $sn Серийный номер
 * @property string $hw Аппаратное обеспечение
 * @property string $mac MAC адреса
 * @property string $specs Спецификация оборудования (опц)
 * @property int $state_id Статус
 * @property int $places_id Помещение
 * @property int $user_id Пользователь
 * @property int $responsible_id Ответственный
 * @property int $head_id Руководитель отдела
 * @property int $it_staff_id Сотрудник ИТ
 * @property int $techsCount Количество техники
 * @property int $voipPhonesCount Количество телефонов
 * @property string $user_name Пользователь машины
 * @property string $user_login Логин пользователя
 * @property string $department Структурное подразделение
 * @property string $department_head Руководитель структурного подразделения
 * @property string $responsible_person Ответственное лицо
 * @property string $comment Комментарий
 * @property string $updated_at Время изменения
 * @property string $stateName Статус
 * @property string $history история
 * @property boolean $is_server является сервером
 * @property array $contracts_ids ссылки на Документы
 * @property boolean $archived Признак списанного АРМ
 *
 * @property Users $head
 * @property Users $responsible
 * @property Users $user
 * @property Places $place
 * @property Users $itStaff
 * @property Comps $comp
 * @property Comps $hwComp
 * @property Comps[] $comps
 * @property Comps[] $sortedComps
 * @property Comps[] $hwComps
 * @property Comps[] $vmComps
 * @property Techs[] $techs
 * @property TechStates $state
 * @property Techs[] $voipPhones
 * @property Techs[] $ups
 * @property Techs[] $monitors
 * @property TechModels $techModel
 * @property Contracts[] $contracts
 * @property LicItems[] $licItems
 * @property LicKeys[] $licKeys
 * @property LicGroups[] $licGroups
 * @property HwList $hwList
 * @property MaterialsUsages[] $materialsUsages
 */
class Arms extends ArmsModel
{
	public static $title='АРМ';
	public static $titles='АРМы';
    private $hwList_obj=null;
	private $voipPhones_cache=null;
	private $ups_cache=null;
	private $monitors_cache=null;
	private $hwComp_cache=null;  //тот комп с которого вытаскивать железо (если основная ОС - виртуальная)
	private $hwComps_cache=null; //для методов вытащить только поставленные на железо
	private $vmComps_cache=null; //и виртуальные ОС пригодится кэш (наверно)
	private $state_cache=null;
	//private $hw=null;
    private static $num_str_pad=6; //количество знаков в цифровой части номера

	/**
	 * В списке поведений прикручиваем many-to-many contracts
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'contracts_ids' => 'contracts',
					'lic_items_ids' => 'licItems',
					'lic_keys_ids' => 'licKeys',
					'lic_groups_ids' => 'licGroups',
				]
			]
		];
	}

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'arms';
    }
	
	/**
	 * @inheritdoc
	 */
	public function attributeData()
	{
		return [
			'id' => 'Идентификатор',
			'num' => [
				'Инвентарный номер',
				'indexLabel'=>'Инв. номер',
				'hint' => 'Внутренний инвентарный номер АРМ в службе ИТ.',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'state_id' => [
				'Статус',
				'indexHint' => 'Статус этого АРМ<br>'.
					'Можно выбрать несколько из выпадающего списка <br>'.
					'Позиции выбираются кликом'
			],
			'inv_num' => [
				'Бухг. инв. номер',
				'hint' => 'Бухгалтерский инвентарный / номенклатурный номер.',
			],
			'inv_sn' => [
				'label'=>'Бух/SN',
				'indexHint' => 'Серийный и бухгалтерский инвентарный/номенклатурный номера через запятую<br>'.
					'Искать можно по обоим номерам сразу<br/>'.QueryHelper::$stringSearchHint,
			],
			'sn' => [
				'label'=>'Серийный номер',
				'hint' => 'Серийный номер системного блока / ноутбука',
			],
			'mac' => [
				'label'=>'MAC адреса',
				'hint' => 'MAC адреса сетевых интерфейсов АРМ<br>'.
					'При заполнении адресов можно будет найти все ОС с такими адресами и привязать к АРМ',
			],
			'comp_id' => [
				'label'=>'Основная ОС',
				'indexLabel'=>'ОС',
				'hint' => 'Какую ОС отображать в паспорте',
				'indexHint' => 'Поиск ведется <b>только по основной</b> операционной системе.<br>'.
					'Найти АРМ по неосновной ОС можно через '.Html::a('список ОС',['/comps/index']).
					'<br/>'.QueryHelper::$stringSearchHint,
			],
			'model_id' => [
				'label'=>'Модель ПК',
				'hint' => 'Модель системного блока / ноутбука / сервера.  Если нужная модель отсутствует в списке, то нужно сначала завести в ее в соотв. категории оборудования',
				'indexHint' => 'Модель системного блока / ноутбука / сервера<br>'.
					'Производитель в таблице не выводится, но при поиске учитывается'.
					'<br/>'.QueryHelper::$stringSearchHint,
			],
			'model_name'=>['alias'=>'model_id'],
			'comp_hw' => [
				'label'=>'Комплектация',
				'indexHint' => 'Строка оборудования обнаруженного <b>в основной ОС</b><br>'.
					'Чтобы увидеть оборудование в отформатированном виде - наведите мышку на строку'.
					'<br/>'.QueryHelper::$stringSearchHint,
			],
			'specs' => [
				'label'=>'Тех. спецификация',
				'hint' => 'Спецификация оборудования в случае, если модель оборудования не полностью определяет комплектацию каждого отдельного экземпляра',
			],
			'is_server' => [
				'label'=>'Сервер',
				'hint' => 'Это оборудование формирует сервер, на котором выполняются какие-то сервисы (будет отмечено другим оформлением, возможно повесить сервисы)',
			],
			'it_staff_id' => [
				'label'=>'Сотрудник Дирекции ИТ',
				'hint' => 'Сотрудник службы ИТ, который отвечает за это рабочее место/сервер, если явно не указан другой ответственный',
			],
			'user_id' => [
				'label'=>'Пользователь',
				'hint' => 'Кто работает за этим АРМ',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'places_id' => [
				'label'=>'Помещение',
				'hint' => 'Помещение, куда установлен АРМ',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'departments_id' => [
				'label'=>'Подразделение',
				'hint' => 'Подразделение, за которым закреплен АРМ',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'responsible_id' => [
				'label'=>'Ответственный',
				'hint' => 'Если указан, то ответственность за установленное ПО будет нести указанное ответственное лицо. В таком случае в паспорте появится дополнительный пункт, в котором ответственное лицо должно расписаться.',
			],
			'head_id' => [
				'label'=>'Руководитель отдела',
				'hint' => 'Руководитель отдела сотрудника работающего на АРМ',
			],
			'department_head' => 'Руководитель стр. подразделения',
			'responsible_person' => 'Ответственное лицо',
			'comment' => [
				'label'=>'Примечание',
				'hint' => 'Краткое пояснение по этому АРМ',
			],
			'updated_at' => [
				'label'=>'Время изменения',
			],
			'contracts_ids' => [
				'label'=>'Привязанные документы',
				'hint' => 'Счета, накладные, фотографии серийных номеров и т.п.',
			],
			'lic_items_ids' => [
				'label'=>'Закупки лицензий',
				'hint' => 'Если на это рабочее место закупали лицензии, добавьте их здесь. (не нужно добавлять те закупки, которые входят в группы добавленные ниже)',
			],
			'lic_groups_ids' => [
				'label'=>'Группы лицензий',
				'hint' => 'Если на это рабочее место нужно распределить лицензию из группы закупок, добавьте группу здесь. (не нужно добавлять те группы, в которые входят закупки добавленные выше)',
			],
			'attach' => [
				'label' => 'Привязки',
				//'indexLabel' => 'Прив.',
				'indexHint' => 'Привязанные к АРМ документы и лицензии',
			],
			'history' => [
				'label'=>'Записная книжка',
				'hint' => 'Все важные и не очень заметки и примечания по жизненному циклу этого АРМ',
			],
		];
	}

	
	/**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['num'], 'required'],
            [['comp_id','places_id','model_id','state_id','departments_id'], 'integer'],
	        [['contracts_ids','lic_items_ids','lic_groups_ids'], 'each', 'rule'=>['integer']],
            [['user_id', 'responsible_id', 'head_id', 'it_staff_id'], 'integer'],
            [['updated_at','history','hw','specs'], 'safe'],
	        [['is_server'],'boolean'],
            [['num'], 'string', 'max' => 16],
	        [['model', 'inv_num', 'sn', 'comment'], 'string', 'max' => 128],
	        [['user_id', 'responsible_id', 'head_id', 'it_staff_id'], 'filter', 'filter' => function ($value) {
		        //заменяем пустые значения табельных номеров на NULL
		        return strlen($value)?$value:null;
	        }],
	        ['num', function ($attribute, $params, $validator) {
                if (count(explode('-',$this->$attribute))!==2) {
                    $this->addError($attribute, 'Инвентарный номер должен быть в формате "ФИЛ-НОМЕР", где ФИЛ - префикс филиала, НОМЕР - целочисленный номер уникальный в рамках филиала.');
                }
            }],
            ['num', 'filter', 'filter' => function ($value) {
                // выполняем определенные действия с переменной, возвращаем преобразованную переменную
                $tokens=explode('-',$value);
                $prefix=mb_strtoupper($tokens[0],'utf-8');
                $num=(int)$tokens[1];
                $num=str_pad((string)$num,static::$num_str_pad,'0',STR_PAD_LEFT);
                return $prefix.'-'.$num;
            }],
            ['num', function ($attribute, $params, $validator) {
                $same=static::findOne([$attribute=>$this->$attribute]);
                if (is_object($same)&&($same->id != $this->id)) {
                    $pref=explode('-',$this->$attribute)[0];
                    $next=static::fetchNextNum($pref);
                    $this->addError($attribute, "Инвентарный номер {$this->$attribute} уже занят, следующий свободный номер с префиксом $pref - $next");
                }
            }],
            ['num', 'unique'],
            [['head_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['head_id' => 'id']],
            [['responsible_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['responsible_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['it_staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['it_staff_id' => 'id']],
	        [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::className(), 'targetAttribute' => ['places_id' => 'id']],
	        [['departments_id'], 'exist', 'skipOnError' => true, 'targetClass' => Departments::className(), 'targetAttribute' => ['departments_id' => 'id']],
	        [['state_id'], 'exist', 'skipOnError' => true, 'targetClass' => TechStates::className(), 'targetAttribute' => ['state_id' => 'id']],
	        [['model_id'], 'exist', 'skipOnError' => true, 'targetClass' => TechModels::className(), 'targetAttribute' => ['model_id' => 'id']],

        ];
    }

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getItStaff()
    {
        return $this->hasOne(Users::className(), ['id' => 'it_staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHead()
    {
        return $this->hasOne(Users::className(), ['id' => 'head_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResponsible()
    {
        return $this->hasOne(Users::className(), ['id' => 'responsible_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(Users::className(), ['id' => 'user_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::className(), ['id' => 'places_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getDepartment()
	{
		return $this->hasOne(Departments::className(), ['id' => 'departments_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechModel()
	{
		return $this->hasOne(TechModels::className(), ['id' => 'model_id'])->from(['arms_models'=>TechModels::tableName()]);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getComp()
	{
		return $this->hasOne(Comps::className(), ['id' => 'comp_id']);
	}

	public function getState()
	{

		return $this->hasOne(TechStates::className(),['id' => 'state_id'])->from(['arms_states'=>TechStates::tableName()]);
		//if (!is_null($this->state_cache)) return $this->state_cache;
		//return $this->state_cache=TechStates::findOne($this->state_id);
	}


	/**
	 * Возвращает все ОС привязанные к этому АРМ
	 * @return \yii\db\ActiveQuery
	 */
	public function getComps()
	{
		//if (!is_null($this->comps_cache)) return $this->comps_cache;
		//return $this->comps_cache=
		return $this->hasMany(Comps::className(), ['arm_id' => 'id'])
			->from(['arms_comps'=>Comps::tableName()])
			->orderBy([
				'arms_comps.ignore_hw'=>SORT_ASC,
				'arms_comps.name'=>SORT_ASC
			]);
	}
	
	public function getSortedComps()
	{
		$comps=$this->comps;
		if ($comps[0]->id!=$this->comp_id) {
			foreach ($comps as $idx=>$comp)
				if ($comp->id == $this->comp_id)
					unset($comps[$idx]);
				
			array_unshift($comps,$this->comp);
		}
		return $comps;
	}
	
	public function buildHwAndVms() {
		if (!is_null($this->hwComp_cache)) return;
		$this->hwComps_cache=[];
		$this->vmComps_cache=[];
		$this->hwComp_cache=$this->comp;
		if (count($this->comps)) foreach ($this->comps as $comp) {
			if ($comp->ignore_hw)
				$this->vmComps_cache[]=$comp;
			else {
				if (!is_object($this->hwComp_cache) || ($this->hwComp_cache->ignore_hw)) $this->hwComp_cache=$comp;
				$this->hwComps_cache[]=$comp;
			}
		}
		
	}

	
	/**
	 * Возвращает все не виртуальные ОС привязанные к этому АРМ
	 * @return \yii\db\ActiveQuery
	 */
	public function getHwComps()
	{
		$this->buildHwAndVms();
		return $this->hwComps_cache;
	}
	
	
	/**
	 * Возвращает тот комп, с которого снимать железо АРМ
	 * @return \yii\db\ActiveQuery
	 */
	public function getHwComp()
	{
		$this->buildHwAndVms();
		return $this->hwComp_cache;
	}
	
	
	/**
	 * Возвращает все виртуальные ОС привязанные к этому АРМ
	 * @return \yii\db\ActiveQuery
	 */
	public function getVmComps()
	{
		$this->buildHwAndVms();
		return $this->vmComps_cache;
	}
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechs()
	{
		//if (!is_null($this->techs_cache)) return $this->techs_cache;
		//return $this->techs_cache=$this->hasMany(Techs::className(), ['arms_id' => 'id'])->from(['arms_techs'=>Techs::tableName()]);
		return $this->hasMany(Techs::className(), ['arms_id' => 'id'])->from(['arms_techs'=>Techs::tableName()]);
	}

	/**
	 * @return array
	 */
	public function getVoipPhones()
	{
		if (!is_null($this->voipPhones_cache)) return $this->voipPhones_cache;
		$this->voipPhones_cache=[];
		foreach ($this->techs as $tech) if ($tech->isVoipPhone) $this->voipPhones_cache[]=$tech;
		return $this->voipPhones_cache;
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMaterialsUsages()
	{
		return $this->hasMany(MaterialsUsages::className(), ['arms_id' => 'id']);
	}
	
	/**
	 * @return array
	 */
	public function getUps()
	{
		if (!is_null($this->ups_cache)) return $this->ups_cache;
		$this->ups_cache=[];
		foreach ($this->techs as $tech) if ($tech->isUps) $this->ups_cache[]=$tech;
		return $this->ups_cache;
	}
	
	/**
	 * @return array
	 */
	public function getMonitors()
	{
		if (!is_null($this->monitors_cache)) return $this->monitors_cache;
		$this->monitors_cache=[];
		foreach ($this->techs as $tech) if ($tech->isMonitor) $this->monitors_cache[]=$tech;
		return $this->monitors_cache;
	}
	
	public function getArchived()
	{
		return is_object($this->state)?$this->state->archived:false;
	}
	
	/**
	 * Возвращает набор документов
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::className(), ['id' => 'contracts_id'])->from(['arms_contracts'=>Contracts::tableName()])
			->viaTable('{{%contracts_in_arms}}', ['arms_id' => 'id']);
	}
	
	/**
	 * Возвращает набор документов
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::className(), ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_arms}}', ['arms_id' => 'id']);
	}

	/**
	 * Возвращает набор документов
	 */
	public function getLicKeys()
	{
		return $this->hasMany(LicKeys::className(), ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_arms}}', ['arms_id' => 'id']);
	}

	/**
	 * Возвращает набор документов
	 */
	public function getLicGroups()
	{
		return $this->hasMany(LicGroups::className(), ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_arms}}', ['arms_id' => 'id']);
	}

	/**
	 *
	 */
	public function getName()
	{
		if (!is_null($this->comp_id)) {
			$comp=Comps::findOne($this->comp_id);
			if (is_object($comp)) return $comp->domainName;
			return 'основная ОС не найдена в БД';
		}
		return 'Не назначено';
	}

	public function getStateName()
	{
		if (is_object($this->state)) return $this->state->name;
		return '';
	}

	/**
	 *
	 */
	public function getUserName()
	{
		if (!is_null($user=$this->user)) {
			if (is_object($user)) return $user->Ename;
			return 'Пользователь потерян';
		}
		return 'Нет пользователя';
	}


	/**
	 *
	 */
	public function getSname()
	{
		$tokens=[$this->num];
		if (($hostname=$this->hostname)!=='Не назначено') $tokens[]=$hostname;
		if (($username=$this->userName)!=='Нет пользователя') $tokens[]=$username;
		return implode(' / ',$tokens);
	}


	/**
    *
    */
	public function getHostname()
	{
		if (is_object($comp=$this->comp)) {
			$names=explode('\\',$comp->domainName);
			if (count($names)>1) unset($names[0]);
			return strtolower(implode(' ',$names));
		}
		return 'Не назначено';
	}

    /**
     * Возвращает все оборудование в виде HwList
     */
    public function getHwList()
    {
        if (!is_null($this->hwList_obj)) return $this->hwList_obj;
        $this->hwList_obj = new HwList();
        $this->hwList_obj->loadJSON($this->hw);
        if (is_object($this->hwComp))
        	$this->hwList_obj->loadFound($this->hwComp->hwList);
        return $this->hwList_obj;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
	    //error_log('savin');
        if (parent::beforeSave($insert)) {

            if (!is_null($this->hwList_obj)) {
	            $this->hw=$this->hwList->onlySaved()->saveJSON();
	            //error_log(print_r($this->hw,true));
            	
            } else {
	            //error_log('null hw');
            }
	
			$this->mac=\app\helpers\MacsHelper::fixList($this->mac);
	
			//если ОС которая была назначена основной удалена или сменила АРМ
            if (!is_object($this->comp) || $this->comp->arm_id != $this->id)
            	$this->comp_id = null; //удаляем основную ОС
            
			//если основной ОС нет, но есть привязанные, то выбираем первую из привязанных как основную
			if (is_null($this->comp_id) && is_array($comps=$this->comps) && count($comps)) {
				foreach ($comps as $comp) {
					$this->comp_id = $comp->id;
					break;
				}
			}
			
            return true;
        } else {
	        //error_log('uh oh');
        }
        return false;
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
            $subidx = (int)$tokens['1'] + 1;
            return $subidx;
        } else return 0;
    }


    /**
     * Возвращает следующие инвентарные номера с известными префиксами
     * @return array следующие номера
     */
    public static function fetchNextNums() {
    	$query=new \yii\db\Query();

        $last=$query->select(['MAX(num) as num','SUBSTR(num,1,INSTR(num,"-")) as prfx'])
	        ->from('arms')
	        ->groupBy('prfx')
            ->orderBy('num')
            ->all();

        if (!is_array($last)or!count($last)) return [];

        $items=[];
        foreach ($last as $item) {
	        $tokens = explode('-', $item['num']);
	        $num = (int)$tokens['1'] + 1;
	        $num=str_pad((string)$num,static::$num_str_pad,'0',STR_PAD_LEFT);
	        $items[$item['prfx']]=$tokens[0].'-'.$num;
        }
        return $items;
    }

	public static function fetchNames(){
		$list= static::find()->joinWith('user')->joinWith('comp')->joinWith('comp.domain')
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}

	public function getTechsCount(){
		return count($this->techs);
	}

	public function getVoipPhonesCount(){
		return count($this->voipPhones);
	}
	
	public function getUpdatedRenderClass(){
		if (is_object($this->comp)) {
			return $this->comp->updatedRenderClass;
		} else return '';
	}

}
