<?php

namespace app\models;

use Yii;
use yii\web\JsExpression;

/**
 * This is the model class for table "contracts".
 *
 * !ВНИМАНИЕ! в модели используется свзь типа потомок родитель
 * в случае интенсивоного использования таких связей количество запросов к БД
 * будет расти невероятным образом, поэтмоу по-хорошему надо добавить в таблицу
 * ключит для хранения NESTED TREE!
 *
 * @property int $id id
 * @property int $parent_id Родительский договор
 * @property int $state_id ID статуса документа
 * @property int $currency_id Валюта
 * @property int $total Сумма
 * @property int $charge Налог
 * @property bool $isPaid Оплачен
 * @property bool $isUnpaid Не оплачен
 * @property bool $is_successor Замещает родительский договор
 * @property string $date Дата документа
 * @property string $datePart Часть имени документа с датами (дата или период начало-конец)
 * @property string $end_date Дата окончания действия документа
 * @property string $name Название документа
 * @property string $sname Полное наименование документа
 * @property string $selfSname Полное наименование документа
 * @property string $sAttach строка с иконками приложений
 * @property string $comment Комментарий
 * @property string $partnersNames имена партнеров (ч/з запятую)
 * @property array $partners_ids массив ссылок на контрагентов в договоре
 * @property array $arms_ids массив ссылок на контрагентов в договоре
 * @property array $techs_ids массив ссылок на контрагентов в договоре
 * @property array $lics_ids массив ссылок на контрагентов в договоре
 * @property array $partners массив объектов контрагентов в договоре
 * @property array $scans массив сканов документов в договоре
 * @property array $scans_ids массив ссылок на сканы в договоре
 *
 * @property Contracts $parent
 * @property Currency $currency
 * @property Contracts $successor
 * @property Contracts[] $successors
 * @property Contracts[] $successorsChain
 * @property Contracts[] $successorsRecursive
 * @property Contracts $predecessor
 * @property Contracts $firstPredecessor
 * @property Contracts[] $childs
 * @property Contracts[] $chainChildren
 * @property Contracts[] $childrenRecursive
 * @property Contracts[] $allChildren
 * @property Arms[] $arms
 * @property Materials[] $materials
 * @property Techs[] $techs
 * @property LicItems[] $licItems
 * @property Arms[] $armsChain
 * @property Techs[] $techsChain
 * @property LicItems[] $licsChain
 * @property OrgPhones[] $orgPhones
 * @property OrgPhones[] $phonesChain
 * @property OrgInets[] $orgInets
 * @property OrgInets[] $inetsChain
 * @property Services[] $services
 * @property Services[] $servicesChain
 */
class Contracts extends \yii\db\ActiveRecord
{
	
	public static $title='Документы';
	public static $titles='Документы';
	public static $noPartnerSuffix='Внутр. документ';
	
	public static $dictionary=[
		'contract'=>['договор'],
		'invoice'=>['счет']
	];


	public $scanFile;

	private $partners_cache=null;
	private $successors_chain_cache=null;
	private $techs_chain_cache=null;
	private $arms_chain_cache=null;
	private $inets_chain_cache=null;
	private $phones_chain_cache=null;
	private $lics_chain_cache=null;
	private $services_chain_cache=null;
	private $recursive_children_cache=null;
	private $all_children_cache=null;
	private $first_predecessor_cache=null;
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contracts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
			[['currency_id'],'default','value'=>1],
	        [['lics_ids','partners_ids','arms_ids','techs_ids'], 'each', 'rule'=>['integer']],
	        //[['scanFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, pdf, gif', 'maxSize' => 1024*1024*30],
	        [['parent_id','state_id','currency_id'], 'integer'],
	        [['total','charge'], 'number'],
	        [['is_successor'], 'boolean'],
            [['comment'], 'string'],
            [['name','date','end_date'], 'string', 'max' => 128],
	        [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contracts::className(), 'targetAttribute' => ['parent' => 'id']],
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
					'partners_ids' => 'partners',
					'lics_ids' => 'licItems',
					'arms_ids' => 'arms',
					'techs_ids' => 'techs',
					'materials_ids' => 'materials'
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
			'id' => 'ID',
			'parent' => 'Основной документ',
			'parent_id' => 'Основной документ',
			'currency_id' => 'Валюта',
			'is_successor' => 'Заменяет основной документ',
			'name' => 'Название документа',
			'scanFile' => 'Скан документа',
			'date' => 'Дата',
			'total' => 'Сумма',
			'charge' => 'в т.ч. НДС',
			'end_date' => 'Окончание',
			'partners_ids' => 'Контрагенты',
			'lics_ids' => 'Лицензии',
			'techs_ids' => 'Оборудование',
			'partnersNames' => 'Контрагент(ы)',
			'arms_ids' => 'АРМы',
			'scans_ids' => 'Сканы',
			'state_id' => 'Статус',
			'comment' => 'Комментарий',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'parent' => 'Если этот документ дополняет основной',
			'parent_id' => 'Если этот документ дополняет основной',
			'currency_id' => 'Ед. изм. суммы',
			'name' => 'Полное название документа как в нем написано (Счет № / договор № / накладная / и т.д.).',
			'date' => 'Дата документа / начало действия, если есть срок действия',
			'total' => 'Если по документу предполагается оплата какойто суммы, то ее надо вписать сюда',
			'end_date' => 'Если документ имеет срок действия - то до какой даты',
			'partners_ids' => 'Если отсутствуют, значит документ внутренний',
			'arms_ids' => 'С какими рабочими местами связан документ (если связан)',
			'techs_ids' => 'С каким оборудованием связан документ (если связан)',
			'state_id' => 'Для удобства контроля процессов оплаты',
			'scans_ids' => 'Отсканированная версия документа',
		];
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::className(), ['id' => 'currency_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Contracts::className(), ['id' => 'parent_id']);
	}
	
	/**
	 * ищет одного наследника (один уровень наследования + самый молодой)
	 * @return \yii\db\ActiveQuery
	 */
	public function getSuccessor()
	{
		return $this->hasOne(Contracts::className(), ['parent_id' => 'id'])
			->andWhere(['is_successor'=>true])
			->orderBy(['date'=>SORT_DESC]);
	}

	/**
	 * Ищет всех непосредственных наследников (один уровень наследования)
	 * @return \yii\db\ActiveQuery
	 */
	public function getSuccessors()
	{
		return $this->hasMany(Contracts::className(), ['parent_id' => 'id'])
			->andWhere(['is_successor'=>true])
			->orderBy(['date'=>SORT_DESC]);
	}

	/**
	 * Ищет всех наследников
	 * @return \app\models\Contracts[]
	 */
	public function getSuccessorsRecursive()
	{
		//собираем непосредственных наследников
		$successors=$this->successors;
		//перебираем их
		foreach ($successors as $successor) {
			//если у них тоже есть наследники - добавляем
			$subSuccessors=$successor->getSuccessorsRecursive();
			if (count($subSuccessors)) $successors=array_merge($successors,$subSuccessors);
		}
		return $successors;
	}

	/**
	 * Предшественник документа (если есть)
	 * @return \app\models\Contracts
	 */
	public function getPredecessor()
	{
		if ($this->is_successor) return $this->parent;
		return null;
	}
	
	/**
	 * Первый предшественник документа
	 * (тот, который актуален на текущий момент, а все остальные устарели)
	 * @return \app\models\Contracts
	 */
	public function getFirstPredecessor()
	{
		//cache
		if (!is_null($this->first_predecessor_cache)) return $this->first_predecessor_cache;
		//---
		$this->first_predecessor_cache=$this;
		while (is_object($predecessor=$this->first_predecessor_cache->predecessor)) {
			$this->first_predecessor_cache=$predecessor;
		}
		return $this->first_predecessor_cache;
	}
	
	
	/**
	 * Цепь всех наследников
	 * не привязанных к документу других документов, а по сути версий договоров заменяющих друг друга в разные периоды времени
	 * отсортированная по дате
	 * начиная с самого последнего, даже если этот документ не крайний наследник
	 * @return \app\models\Contracts[]
	 */
	public function getSuccessorsChain()
	{
		//CACHE
		if (!is_null($this->successors_chain_cache)) return $this->successors_chain_cache;
		//---
		
		//ищем действующего наследника
		$root=$this->firstPredecessor;

		//составляем цепочку из всех потомков и их потомков
		$chain=$root->getSuccessorsRecursive();

		//и себя
		$chain[]=$root;

		//надо отсортировать по дате
		usort($chain,function($a,$b){if ($a->date==$b->date) return 0; return ($a->date<$b->date)?-1:1;});
		//кэшируем
		return $this->successors_chain_cache=$chain;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getChilds()
	{
		return $this->hasMany(Contracts::className(), ['parent_id' => 'id'])
			->andWhere(['is_successor'=>false]);
	}
	
	public function getChildrenRecursive()
	{
		if (!is_null($this->recursive_children_cache))
			return $this->recursive_children_cache;
		
		$this->recursive_children_cache=[];
		foreach ($this->childs as $child) {
			$this->recursive_children_cache[]=$child;
			if (count($recursive=$child->childrenRecursive))
				$this->recursive_children_cache=array_merge($this->recursive_children_cache,$recursive);
		}
		return $this->recursive_children_cache;
	}
	
	/**
	 * Возвращает список всех потомков этого документа и его наследников (новых версий этого же документа)
	 * только первый уровень
	 * @return \yii\db\ActiveQuery
	 */
	public function getChainChildren()
	{
		$chain=$this->successorsChain;
		$children=[];
		foreach ($chain as $item) $children=array_merge($children,$item->childs);
		return $children;
	}
	
	public function getAllChildren()
	{
		if (!is_null($this->all_children_cache))
			return $this->all_children_cache; //CACHE
		//----
		
		$docs=[];
		$firstLevel=$this->chainChildren;
		foreach ($firstLevel as $child) {
			$docs[]=$child;
			if (count($secondLevel=$child->allChildren))
				$docs=array_merge($docs,$secondLevel);
		}
		return $this->all_children_cache=$docs;
	}
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrgInets()
	{
		return $this->hasMany(OrgInet::className(), ['contracts_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrgPhones()
	{
		return $this->hasMany(OrgPhones::className(), ['contracts_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getServices()
	{
		return $this->hasMany(Services::className(), ['id'=>'services_id'])
			->viaTable('contracts_in_services',['contracts_id' => 'id']);
	}
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @return string
	 */
	public function getPartnersNames()
	{
		if (is_array($partners=$this->partners)&&count($partners)) {
			$names=[];
			foreach ($partners as $partner) $names[]=$partner->sname;
			return implode(',',$names);
		} else {
			return static::$noPartnerSuffix;
		}
	}

	public function getDatePart()
	{
		if (strlen($this->date)) {
			if (strlen($this->end_date))
				return $this->date.' - '.$this->end_date;
			else
				return $this->date;
		} else
		return 'нет даты';
	}

	public function getSelfSname()
	{
		//var_dump($this->date);
		$date=strtotime($this->date);
		mb_regex_encoding('utf8');
		$name=mb_eregi_replace('сч(ё|е)т(-оферта)?( *на *оплату)? *№ *','Счёт № ',$this->name,'i');
		$name=mb_eregi_replace('(от *)?('.date('d.m.(Y|y)',$date).'|'.date('(Y|y).m.d',$date).') *(г(ода)?)?\.?\s*\-\s*','',$name,'i');
		return $name;
	}
	
	public function getSname()
	{
		return $this->datePart.' - '.$this->selfSname.' - '.$this->partnersNames;
	}
	
	public function getSAttach()
	{
		$attaches='';
		if (count($this->childs)) $attaches.='<span class="fas fa-paperclip" title="Привязаны документы: '.(count($this->childs)).'шт"></span>';
		if (count($this->arms)) $attaches.='<span class="fas fa-desktop" title="Привязаны АРМы: '.(count($this->arms)).'шт"></span>';
		if (count($this->techs)) $attaches.='<span class="fas fa-print" title="Привязана техника: '.(count($this->techs)).'шт"></span>';
		if (count($this->licItems)) $attaches.='<span class="fas fa-award" title="Привязаны лицензии: '.(count($this->licItems)).'шт"></span>';
		if (count($this->services)) $attaches.='<span class="fas fa-cog" title="Привязаны услуги: '.(count($this->services)).'шт"></span>';
		return $attaches;
	}


	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getState()
	{
		return $this->hasOne(ContractsStates::className(), ['id' => 'state_id']);
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
		return static::hasMany(Scans::className(), ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор связанных Армов
	 */
	public function getArms()
	{
		return static::hasMany(Arms::className(), ['id' => 'arms_id'])
			->viaTable('{{%contracts_in_arms}}', ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор связанных Армов
	 */
	public function getMaterials()
	{
		return static::hasMany(Materials::className(), ['id' => 'materials_id'])
			->viaTable('{{%contracts_in_materials}}', ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор связанной техники
	 */
	public function getTechs()
	{
		return static::hasMany(Techs::className(), ['id' => 'techs_id'])
			->viaTable('{{%contracts_in_techs}}', ['contracts_id' => 'id']);
	}

	public function getLicItems()
	{
		return static::hasMany(LicItems::className(), ['id' => 'lics_id'])
			->viaTable('{{%contracts_in_lics}}', ['contracts_id' => 'id']);
	}

	/**
	 * набор всех армов привязанных к цепочке документов
	 * @return \app\models\Arms[]
	 */
	public function getArmsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->arms_chain_cache)) return $this->arms_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->arms as $arm)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$arm->id] = $arm;
		}
		//кэшируем результат
		return $this->arms_chain_cache=$chain;
	}
	
	/**
	 * набор всей техники привязанной к цепочке документов
	 * @return null|\yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getTechsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->techs_chain_cache)) return $this->techs_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) $chain[]=$item->id;

		/*{
			//в них все армы
			foreach ($item->techs as $tech)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$tech->id] = $tech;
		}
		//кэшируем результат*/

		return static::hasMany(\app\models\Techs::className(),['id' => 'techs_id'])
			->viaTable('{{%contracts_in_techs}}', ['contracts_id' => $chain]);
	}

	/**
	 * набор всех вводов интернет привязанных к цепочке документов
	 * @return \app\models\OrgInet[]
	 */
	public function getInetsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->inets_chain_cache)) return $this->inets_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->orgInets as $inet)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$inet->id] = $inet;
		}
		//кэшируем результат
		return $this->inets_chain_cache=$chain;
	}

	/**
	 * набор всех телефонов привязанных к цепочке документов
	 * @return \app\models\OrgPhones[]
	 */
	public function getPhonesChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->phones_chain_cache)) return $this->phones_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->orgPhones as $phone)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$phone->id] = $phone;
		}
		//кэшируем результат
		return $this->phones_chain_cache=$chain;
	}
	
	/**
	 * набор всех лицензий привязанных к цепочке документов
	 * @return \app\models\LicItems[]
	 */
	public function getLicsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->lics_chain_cache)) return $this->lics_chain_cache;
		
		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->licItems as $lic)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$lic->id] = $lic;
		}
		//кэшируем результат
		return $this->lics_chain_cache=$chain;
	}
	
	/**
	 * набор всех лицензий привязанных к цепочке документов
	 * @return \app\models\Services[]
	 */
	public function getServicesChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->services_chain_cache)) return $this->services_chain_cache;
		
		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->services as $service)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$service->id] = $service;
		}
		//кэшируем результат
		return $this->services_chain_cache=$chain;
	}
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getPartners()
	{
		//if (!is_null($this->partners_cache)) return $this->partners_cache;

		//return $this->partners_cache=Partners::fetchByField('contracts_id',$this->contracts_id)
		return $this->partners_cache=static::hasMany(Partners::className(), ['id' => 'partners_id'])
			->viaTable('{{%partners_in_contracts}}', ['contracts_id' => 'id']);
	}

	public static function fetchNames(){
		$list= static::find()->joinWith('partners')->viaTable('{{%partners_in_contracts}}', ['contracts_id' => 'id'])
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}


	/**
	 * Следующий id
	 * @return integer
	 */
	public static function fetchNextId() {
		return static::find()->max("id")+1;
	}

	public static function fetchArmsHint($ids,$form='') {

		if (!is_array($ids))
			$ids=explode(',',$ids);
		$hint='Выберите АРМ';
		if (!count($ids)) return $hint;
		$arms=\app\models\Arms::find()
			->joinWith('contracts')
			->where(['arms_contracts.id'=>$ids])
			->all();
		if (!count($arms)) return $hint;

		$hintTtip='В подсказке приведены АРМы из привязанных документов'.(strlen($form)?' (кликабельно)':'');
		$hint="<span title=\"$hintTtip\">Подсказка</span>: ";
		foreach ($arms as $arm) {
			/**
			 * @var $arm Arms
			 */
			$js=strlen($form)?(
				"onclick=\"$('#$form-arms_id,#$form-arms_ids').val({$arm->id}).trigger('change');\""
			):'';
			$ttip=\yii\helpers\Url::to(['/arms/ttip','id'=>$arm->id]);
			$hint.="<span class='href' qtip_ajxhrf='$ttip' $js>{$arm->num}</span> ";
		}
		return $hint;
	}
	
	public static function fetchParentHint($ids,$form='') {
		
		if (!is_array($ids))
			$ids=explode(',',$ids);
		
		$hint='Выберите договор / документ-основание';
		
		if (!count($ids)) return $hint;
		
		$possibleParents=static::find()
			->innerJoin('{{%partners_in_contracts}}', '`contracts_id` = `contracts`.`id`')
			->where(['partners_id'=>$ids,])
			->andWhere(['like','name',Contracts::$dictionary['contract']])
			->orderBy(['date'=>SORT_DESC])
			->limit(6)
			->all();
			//->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql;
		
		if (!count($possibleParents)) return $hint;
			//return var_dump($possibleParents);//
		
		$hintTtip='Быстрый выбор договора как основного документа';
		$hint="<span title=\"$hintTtip\">Выбрать договор</span>: ";
		foreach ($possibleParents as $doc) {
			/**
			 * @var $doc Contracts
			 */
			$js=strlen($form)?(
			"onclick=\"$('#$form-parent_id').val({$doc->id}).trigger('change');\""
			):'';
			$ttip=\yii\helpers\Url::to(['/contracts/ttip','id'=>$doc->id]);
			$hint.="<br /><span class='href' qtip_ajxhrf='$ttip' $js>{$doc->selfSname}</span>";
		}
	
		return $hint;
	}
	
	
	function getIsUnpaid() {
		return ContractsStates::isUnpaid($this->state_id);
	}

	function getIsPaid() {
		return ContractsStates::isPaid($this->state_id);
	}
	
	/**
	 * Код для подстановки НДС
	 * @param $model
	 * @param $total
	 * @param $charge
	 * @return string
	 */
	public static function chargeCalcHtml($model,$total,$charge) {
		//строка подсчета НДС в поле $charge из значения в поле $total в ActiveForm
		return <<<HTML
		<span class="href" onclick="$('#{$model}-{$charge}').val(($('#{$model}-{$total}').val()/1.2*0.2).toFixed(2))">20%</span>
		/
		<span class="href" onclick="$('#{$model}-{$charge}').val('')">нет</span>
HTML;
	}
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			
			if ($this->is_successor) {
				//Если этот документ заменяет предыдущую версию договора,
				//то мы его пришиваем именно к последней версии, чтобы не было
				//двух документов заменяющих один (это нарушает логику)
				$this->parent_id=$this->firstPredecessor->id;
			}
		}
		return true;
	}
	
}
