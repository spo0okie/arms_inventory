<?php

namespace app\models;

use app\helpers\QueryHelper;
use voskobovich\linker\LinkerBehavior;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "contracts".
 *
 * !ВНИМАНИЕ! в модели используется связь типа потомок родитель
 * в случае интенсивного использования таких связей количество запросов к БД
 * будет расти невероятным образом, поэтому по-хорошему надо добавить в таблицу
 * ключи для хранения NESTED TREE!
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
 * @property string $stateName Статус
 * @property array $partners_ids массив ссылок на контрагентов в договоре
 * @property array $arms_ids массив ссылок на контрагентов в договоре
 * @property array $techs_ids массив ссылок на контрагентов в договоре
 * @property array $lics_ids массив ссылок на контрагентов в договоре
 * @property array $scans массив сканов документов в договоре
 * @property array $scans_ids массив ссылок на сканы в договоре
 * @property array $users_ids массив пользователей в договоре
 * @property string $pay_id идентификатор оплаты (в реестре оплат)
 * @property integer $techs_delivery количество поставляемого оборудования
 * @property integer $lics_delivery количество поставляемых лицензий
 * @property integer $materials_delivery количество поставляемых материалов
 * @property integer $techsCount количество поставленного оборудования
 * @property integer $licsCount количество поставленных лицензий
 * @property integer $materialsCount количество поставленных материалов
 * @property integer $undeliveredTechsCount количество поставленного оборудования
 * @property integer $undeliveredLicsCount количество поставленных лицензий
 * @property integer $undeliveredMaterialsCount количество поставленных материалов
 * @property integer $deliveryState состояние доставки
 * @property string[] $undeliveredDescription описание что недопоставлено
 *
 * @property Contracts   $parent
 * @property Currency    $currency
 * @property Contracts   $successor
 * @property Contracts[] $successors
 * @property Contracts[] $successorsChain
 * @property Contracts[] $successorsRecursive
 * @property Contracts   $predecessor
 * @property Contracts   $firstPredecessor
 * @property Contracts[] $contracts
 * @property Contracts[] $children
 * @property Contracts[] $chainChildren
 * @property Contracts[] $childrenRecursive
 * @property Contracts[] $allChildren
 * @property Materials[] $materials
 * @property Techs[]     $techs
 * @property LicItems[]  $licItems
 * @property Techs[]     $techsChain
 * @property LicItems[]  $licsChain
 * @property OrgPhones[] $orgPhones
 * @property OrgPhones[] $phonesChain
 * @property OrgInet[]	 $orgInets
 * @property Partners[]  $partners массив объектов контрагентов в договоре
 * @property Services[]  $services
 * @property Services[]  $servicesChain
 * @property Users[]     $users
 * @property ContractsStates $state Статус
 */
class Contracts extends ArmsModel
{
	
	public static $title='Документы';
	public static $titles='Документы';
	public static $noPartnerSuffix='Внутр. документ';
	
	public static $dictionary=[
		'contract'=>['договор'],
		'invoice'=>['счет']
	];
	


	const DELIVERY_NONE=0;
	const DELIVERY_PAYMENT_WAIT=10;
	const DELIVERY_INCOMPLETE=20;
	const DELIVERY_COMPLETE=100;
	
	public $scanFile;

	private $successors_chain_cache=null;
	private $techs_chain_cache=null;
	private $inets_cache=null;
	private $phones_cache=null;
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
	
	
	public $linksSchema=[
		'state_id' => 		[ContractsStates::class,'contracts_ids'],
		'currency_id' => 	Currency::class,
		'parent_id' =>		[Contracts::class,'children_ids'],
		'children_ids' =>	[Contracts::class,'parent_id'],
		'partners_ids' =>	[Partners::class,'contracts_ids'],
		'lics_ids' =>		[LicItems::class,'contracts_ids','loader'=>'licItems'],
		'techs_ids' =>		[Techs::class,'contracts_ids'],
		'services_ids' =>	[Services::class,'contracts_ids'],
		'materials_ids' =>	[Materials::class,'contracts_ids'],
		'users_ids' =>		[Users::class,'contracts_ids'],
	];
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
			[['currency_id'],'default','value'=>1],
	        [['lics_ids','partners_ids','techs_ids','services_ids','users_ids'], 'each', 'rule'=>['integer']],
	        //[['scanFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, pdf, gif', 'maxSize' => 1024*1024*30],
	        [['parent_id','state_id','currency_id','techs_delivery','materials_delivery','lics_delivery'], 'integer'],
	        [['total','charge'], 'number'],
	        [['is_successor'], 'boolean'],
            [['comment','pay_id'], 'string'],
            [['name','date','end_date'], 'string', 'max' => 128],
	        [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contracts::class, 'targetAttribute' => ['parent' => 'id']],
			[['parent_id'],	'validateRecursiveLink', 'params'=>['getLink' => 'parent']],
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
				'class' => LinkerBehavior::class,
				'relations' => [
					'partners_ids' => 'partners',
					'lics_ids' => 'licItems',
					'techs_ids' => 'techs',
					'services_ids' => 'services',
					'materials_ids' => 'materials',
					'users_ids' => 'users',
					'children_ids' => 'children', //one-2-many
				]
			]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'attach' => [
				'Связи',
				'indexHint' => 'Привязанные к документу объекты',
				'join'=>['children','techs','materials','licItems','services'],
			],
			'charge' => 'в т.ч. НДС',
			'comment' => [
				'Комментарий',
				'hint' => 'Для счетов желательно записывать историю и логистику закупки:<br>'
					.'Записи лучше проставлять рекурсивно, т.к. <b>первая строка комментария выводится в списке рядом со статусом</b><br>'
					.'(для быстрого уточнения текущего этапа закупки/логистики)<br>'
					.'2020-02-12 получено участке в Усинске, получил Сидоров С.С.<br>'
					.'2020-02-02 отправлено на участок в Усинск, отправлял Петров П.П.<br>'
					.'2020-02-01 получено на склад в Москве, получал Иванов И.И.<br>'
					.'2020-01-20 оборудование отправлено, трек 12345678<br>'
					.'2020-01-17 оплачен<br>'
					.'2020-01-13 согласован<br>'
					.'2020-01-10 подан на согласование<br>'
					.'<br>'
					.'Также можно указывать дополнительную информацию для облегчения поиска документа<br>'
					.'т.к. поиск по имени документа включает в себя и поиск по комментариям<br>'
					.'<div class="alert alert-info" role="alert">'
					.'Если не нужно пояснение статуса - оставь первую строку комментария пустой<br>'
					.'</div>'
			],
			'currency' => ['alias' => 'currency_id'],
			'currency_id' => [
				'Валюта',
				'hint' => 'В какой валюте обозначены сумма и НДС в документе'
			],
			'date' => [
				'Дата',
				'hint' => 'Дата документа<br>'
					.'или начало действия, если есть срок действия документа',
			],
			'deliveryStatus' => [
				'Статус поставки',
				'indexLabel' => '<i class="fas fa-truck"></i>',
				'indexHint' => 'Статус поставки: все ли ожидаемые материалы,'
					.'<br>лицензии, оборудования по этому документу поступили'
					.'<br>(привязаны к документу)',
				'join'=>['techs','materials','licItems'],
			],
			'end_date' => [
				'Окончание',
				'hint' => 'Если документ имеет срок действия, то до какой даты - указываем тут',
			],
			'float_charge' => [
				'(float)НДС',
				'indexHint' => 'НДС в виде числа без разделителей и валюты<br />'
					.'(удобно для выгрузки в Excel, т.к. нормально суммируется в выгрузке)',
				'visible' => false,
			],
			'float_total' => [
				'(float)Сумма',
				'indexHint' => 'Сумма документа в виде числа без разделителей и валюты<br />'
					.'(удобно для выгрузки в Excel, т.к. нормально суммируется в выгрузке)',
				'visible' => false,
			],
			'fullname' => ['alias' => 'name'],
			'id' => 'ID',
			'is_successor' => [
				'Заменяет основной документ',
				'hint' => 'Если флажок стоит, значит этот документ не дополняет, а заменяет основной.<br>'
					.'Например, доп. соглашение к договору с новыми тарифами, заменяет предыдущее доп. соглашение'
			],
			'licsCount' => [
				'Привязано лицензий',
				'indexLabel' => '<span class="fas fa-award"></span>',
				'indexHint' => 'Сколько лицензий поступило по этому документу'
					.'<br>(привязано к документу)',
				'join'=>['licItems'],
			],
			'lics_ids' => [
				'Лицензии',
				'hint' => 'С какими закупками лицензий связан документ (если связан)',
				'placeholder'=>'Выберите закупки лицензий связанные с документом',
				'join'=>['licItems'],
			],
			'lics_delivery' => [
				'Пост. лиц.',
				'hint' => 'Должно быть поставлено лицензий'
					.'<br>В документе будет показываться статус ожидается поставка,'
					.'<br>если количество привязанных к документу лицензий отличается от указанного здесь'
			],
			'materialsCount' => [
				'Привязано материалов',
				'indexLabel' => '<i class="fas fa-box-open"></i>',
				'indexHint' => 'Сколько материалов поступило по этому документу'
					.'<br>(привязано к документу)',
				'join'=>['materials'],
			],
			'materials_delivery' => [
				'Пост. матер.',
				'hint' => 'Должно быть поставлено материалов.'
					.'<br>В документе будет показываться статус ожидается поставка,'
					.'<br>если количество привязанных к документу материалов отличается от указанного здесь'
			],
			'name' => [
				'Название документа',
				'hint' => 'Полное название документа как в нем написано (Счет № / договор № / накладная / и т.д.).<br>'
					.(isset(Yii::$app->params['contractsPayDocFormat'])?Yii::$app->params['contractsPayDocFormat']:'')
					.'В имени документа <b>не надо указывать:</b><ul>'
					.'<li>Дату</li>'
					.'<li>Контрагента</li>'
					.'</ul>они указывается в отдельных полях',
				'indexHint' => 'Можно искать по имени, дате, названию контрагента и комментарию к документу<br />'
					.QueryHelper::$stringSearchHint,
				'join'=>['children','techs','materials','licItems','services','partners','users'],
			],
			'partners' => ['alias' => 'partners_ids'],
			'partners_ids' => [
				'Контрагенты',
				'hint' => 'Если отсутствуют, значит документ внутренний',
				'join'=>['partners'],
			],
			'parent_id' => [
				'Основной документ',
				'hint' => 'Если этот документ не самостоятелен, а дополняет основной<br>'
					.'Для актов/УПД/накладных тут надо указать счет<br>'
					.'Для счетов и доп.соглашений имеет смысл указывать договор',
				'placeholder' => 'Основной документ не назначен',
				'join'=>['parent'],
			],
			'pay_id' => [
				Yii::$app->params['docs.pay_id.name'],
				'hint' => 'Заявка на платеж,'
					.'<br>Идентификатор заявки на оплату в реестре оплат казначейства'
			],
			'scanFile' => 'Скан документа',
			'scans_ids' => [
				'Сканы',
				'hint' => 'Отсканированная версия документа',
				'join'=>['scans'],
			],
			'services_ids' => [
				'Сервисы',
				'hint' => 'С какими сервисами связан документ (если связан)',
				'placeholder'=>'Выберите сервисы связанные с документом',
				'join'=>['services'],
			],
			'state_id' => [
				'Статус',
				'hint' => 'Для удобства контроля процессов оплаты',
				'placeholder' => 'Выберите статус документа'
			],
			'techsCount' => [
				'Привязано оборудования',
				'indexLabel' => '<i class="fas fa-print"></i>',
				'indexHint' => 'Сколько оборудования поступило по этому документу'
					.'<br>(привязано к документу)',
				'join'=>['techs'],
			],
			'techs_delivery' => [
				'Пост. оборуд.',
				'hint' => 'Должно быть поставлено оборудования.'
					.'<br>В документе будет показываться статус ожидается поставка,'
					.'<br>если количество привязанного к документу оборудования отличается от указанного здесь'
			],
			'techs_ids' => [
				'Оборудование',
				'hint' => 'С каким оборудованием связан документ (если связан)',
				'placeholder'=>'Выберите оборудование связанное с документом',
				'join'=>['techs'],
			],
			'total' => [
				'Сумма',
				'hint' => 'Если по документу предполагается оплата какой-то суммы, то ее надо вписать сюда<br>'
					.'Не нужно вписывать сумму в документы которые не предполагают оплаты<br>'
					.'Вместо этого надо привязывать платежные документы:<ul>'
					.'<li>к договору нужно прикреплять счета</li>'
					.'<li>к счетам закрывающие документы</li>'
					.'</ul>Сумма при этом должна проставляться только в счетах',
				'indexHint' => 'Сумма документа<br />'.QueryHelper::$numberSearchHint
			],
			'users' => ['alias' => 'users_ids'],
			'users_ids' => [
				'Пользователи',
				'hint' => 'С какими пользователями связан документ (если связан)',
				'placeholder'=>'Укажите пользователей связанных с документом',
				'join'=>['users'],
			],
		];
	}
	
	/** @var string[] Какие колонки выводить по умолчанию */
	public static $defaultColumns= [
		'name',
		'state_id',
		'total',
		'charge',
		'attach',
	];
	
	public function reverseLinks()
	{
		return [
			$this->contracts,
			$this->techs,
			$this->materials,
			$this->services,
			$this->licItems,
			$this->users,
			//$this->scans, //сканы удаляются при удалении документа
		];
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::class, ['id' => 'currency_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Contracts::class, ['id' => 'parent_id']);
	}
	
	/**
	 * ищет одного наследника (один уровень наследования + самый молодой)
	 * @return ActiveQuery
	 */
	public function getSuccessor()
	{
		return $this->hasOne(Contracts::class, ['parent_id' => 'id'])
			->from(['contract_successor'=>self::tableName()])
			->onCondition(['contract_successor.is_successor'=>true])
			->orderBy(['contract_successor.date'=>SORT_DESC]);
	}
	
	/**
	 * ищет одного наследника (один уровень наследования + самый молодой)
	 * @return ActiveQuery
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::class, ['parent_id' => 'id']);
	}
	
	/**
	 * Ищет всех непосредственных наследников (один уровень наследования)
	 * @return ActiveQuery
	 */
	public function getSuccessors()
	{
		return $this->hasMany(Contracts::class, ['parent_id' => 'id'])
			->andWhere(['is_successor'=>true])
			->orderBy(['date'=>SORT_DESC]);
	}

	/**
	 * Ищет всех наследников
	 * @return Contracts[]
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
	 * @return Contracts
	 */
	public function getPredecessor()
	{
		if ($this->is_successor) return $this->parent;
		return null;
	}
	
	/**
	 * Первый предшественник документа
	 * (тот, который актуален на текущий момент, а все остальные устарели)
	 * @return Contracts
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
	 * @return Contracts[]
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
		
		//и себя последним
		$chain[]=$root;

		//надо отсортировать по дате
		usort($chain,function($a,$b){
			if ($a->date==$b->date) {
				if ($a->id==$b->id)
				return 0;
				return ($a->id<$b->id)?-1:1;
			}
			return ($a->date<$b->date)?-1:1;
		});
		

		
		//кэшируем (root принудительно вставляем первым, т.к. бывает что документ наследник идет той же датой)
		return $this->successors_chain_cache=$chain;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getChildren()
	{
		return $this->hasMany(Contracts::class, ['parent_id' => 'id'])
			->from(['contract_children'=>self::tableName()])
			->onCondition(['contract_children.is_successor'=>false])
			->orderBy([
				'contract_children.date'=>SORT_DESC,
				'contract_children.name'=>SORT_DESC,
			]);
	}
	
	public function getChildrenRecursive()
	{
		if (!is_null($this->recursive_children_cache))
			return $this->recursive_children_cache;
		
		$this->recursive_children_cache=[];
		foreach ($this->children as $child) {
			$this->recursive_children_cache[]=$child;
			if (count($recursive=$child->childrenRecursive))
				$this->recursive_children_cache=array_merge($this->recursive_children_cache,$recursive);
		}
		ArrayHelper::multisort($this->recursive_children_cache,'date',SORT_DESC);
		return $this->recursive_children_cache;
	}
	
	/**
	 * Возвращает список всех потомков этого документа и его наследников (новых версий этого же документа)
	 * только первый уровень
	 * @return Contracts[]
	 */
	public function getChainChildren()
	{
		$chain=$this->successorsChain;
		//$root=array_pop($chain); //выкидываем самого себя из цепочки
		$children=[];
		foreach ($chain as $item) $children=array_merge($children,$item->children);
		ArrayHelper::multisort($children,'date',SORT_DESC);
		return $children; //ставим себя первым
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
	 * @return OrgInet[]
	 */
	public function getOrgInets()
	{
		if (!is_null($this->inets_cache)) return $this->inets_cache;
		$this->inets_cache=[];
		foreach ($this->services as $service) {
			if (count($service->orgInets)) {
				foreach ($service->orgInets as $orgInet)
					$this->inets_cache[$orgInet->id]=$orgInet;
			}
		}
		return $this->inets_cache;
	}
	
	/**
	 * @return OrgPhones[]
	 */
	public function getOrgPhones()
	{
		if (!is_null($this->phones_cache)) return $this->phones_cache;
		$this->phones_cache=[];
		foreach ($this->services as $service) {
			if (count($service->orgPhones)) {
				foreach ($service->orgPhones as $orgPhone)
					$this->phones_cache[$orgPhone->id]=$orgPhone;
			}
		}
		return $this->phones_cache;
	}
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['id'=>'services_id'])
			->viaTable('contracts_in_services',['contracts_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::class, ['id'=>'users_id'])
			->viaTable('users_in_contracts',['contracts_id' => 'id']);
	}
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @param bool $userName
	 * @return string
	 */
	public function getPartnersNames($userName=true)
	{
		if (is_array($partners=$this->partners)&&count($partners)) {
			$names=[];
			foreach ($partners as $partner) $names[]=$partner->sname;
			return implode(',',$names);
		} elseif (is_array($users=$this->users)&&count($users)) {
			if (!$userName) return '';
			$names=[];
			foreach ($users as $user) $names[]=$user->shortName;
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
	
	public function getSname($date=true,$self=true,$partner=true,$user=true,$payId=true)
	{
		$tokens=[];
		if ($date) $tokens[]=$this->datePart;
		if ($payId && $this->pay_id) $tokens[]=Yii::$app->params['docs.pay_id.name'].' '.$this->pay_id;
		if ($self) $tokens[]=$this->selfSname;
		if ($partner &&	strlen($partner=$this->getPartnersNames($user))) $tokens[]=$partner;
		
		return implode('-',$tokens);
	}
	
	public function getSAttach()
	{
		$attaches='';
		if (count($this->children)) $attaches.='<span class="fas fa-paperclip" title="Привязаны документы: '.(count($this->children)).'шт"></span>';
		if (count($this->techs)) $attaches.='<span class="fas fa-print" title="Привязана техника: '.(count($this->techs)).'шт"></span>';
		if (count($this->materials)) $attaches.='<span class="fas fa-box-open" title="Привязаны материалы: '.(count($this->materials)).'ед"></span>';
		if (count($this->licItems)) $attaches.='<span class="fas fa-award" title="Привязаны лицензии: '.(count($this->licItems)).'шт"></span>';
		if (count($this->services)) $attaches.='<span class="fas fa-cog" title="Привязаны услуги: '.(count($this->services)).'шт"></span>';
		
		return $attaches;
	}
	
	/**
	 * Поступило материалов по документу (привязано)
	 * @return mixed
	 */
	public function getMaterialsCount()
	{
		if (isset($this->attrsCache['materialsCount'])) return $this->attrsCache['materialsCount'];
		$this->attrsCache['materialsCount']=0;
		foreach ($this->materials as $material) {
			$this->attrsCache['materialsCount']+=$material->count;
		}
		return $this->attrsCache['materialsCount'];
	}
	
	public function getTechsCount()
	{
		return count($this->techs);
	}
	
	/**
	 * Поступило лицензий по документу (привязано)
	 * @return mixed
	 */
	public function getLicsCount()
	{
		if (isset($this->attrsCache['licsCount'])) return $this->attrsCache['licsCount'];
		$this->attrsCache['licsCount']=0;
		foreach ($this->licItems as $licItem) {
			$this->attrsCache['licsCount']+=$licItem->count;
		}
		return $this->attrsCache['licsCount'];
	}
	
	/**
	 * Количество недопоставленного оборудования
	 */
	public function getUndeliveredTechsCount() {
		if (!$this->techs_delivery) return 0;	//ничего не ждем
		return max($this->techs_delivery-$this->techsCount,0); //если приехало больше чем хотели, то ничего не ждем
	}
	
	/**
	 * Количество недопоставленных материалов
	 */
	public function getUndeliveredMaterialsCount() {
		if (!$this->materials_delivery) return 0;	//ничего не ждем
		return max($this->materials_delivery-$this->materialsCount,0);//если приехало больше чем хотели, то ничего не ждем
	}
	
	/**
	 * Количество недопоставленных лицензий
	 */
	public function getUndeliveredLicsCount() {
		if (!$this->lics_delivery) return 0;	//ничего не ждем
		return max($this->lics_delivery-$this->licsCount,0);//если приехало больше чем хотели, то ничего не ждем
	}
	
	/**
	 * Возвращает массив количества недопоставленных объектов вида ['Оборудование: 3шт','Материалы и ЗИП: 7ед']
	 * @return array|mixed
	 */
	public function getUndeliveredDescription(){
		if (isset($this->attrsCache['undeliveredDescription'])) return $this->attrsCache['undeliveredDescription'];
		$this->attrsCache['undeliveredDescription']=[];
		if ($this->undeliveredTechsCount)
			$this->attrsCache['undeliveredDescription'][]=Techs::$titles.': '.($this->undeliveredTechsCount).'шт';
		
		if ($this->undeliveredMaterialsCount)
			$this->attrsCache['undeliveredDescription'][]=Materials::$titles.': '.($this->undeliveredMaterialsCount).'ед';
		
		if ($this->undeliveredLicsCount)
			$this->attrsCache['undeliveredDescription'][]=LicItems::$titles.': '.($this->undeliveredLicsCount).'шт';
		
		return $this->attrsCache['undeliveredDescription'];
	}
	
	public function getDeliveryState() {
		if (!$this->techs_delivery && !$this->materials_delivery && !$this->lics_delivery)
			return self::DELIVERY_NONE;
		
		if (!$this->isPaid) return
			self::DELIVERY_PAYMENT_WAIT;
		
		if ($this->undeliveredLicsCount+$this->undeliveredMaterialsCount+$this->undeliveredTechsCount)
			return self::DELIVERY_INCOMPLETE;
		
		return self::DELIVERY_COMPLETE;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getState()
	{
		return $this->hasOne(ContractsStates::class, ['id' => 'state_id']);
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
		return $this->hasMany(Scans::class, ['contracts_id' => 'id']);
	}


	/**
	 * Возвращает набор связанных Армов
	 */
	public function getMaterials()
	{
		return $this->hasMany(Materials::class, ['id' => 'materials_id'])
			->viaTable('{{%contracts_in_materials}}', ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор связанной техники
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::class, ['id' => 'techs_id'])
			->viaTable('{{%contracts_in_techs}}', ['contracts_id' => 'id']);
	}

	public function getLicItems()
	{
		return $this->hasMany(LicItems::class, ['id' => 'lics_id'])
			->viaTable('{{%contracts_in_lics}}', ['contracts_id' => 'id']);
	}

	
	/**
	 * набор всей техники привязанной к цепочке документов
	 * @return null|ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getTechsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->techs_chain_cache)) return $this->techs_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) $chain[]=$item->id;
		

		return $this->hasMany(Techs::class,['id' => 'techs_id'])
			->viaTable('{{%contracts_in_techs}}', ['contracts_id' => $chain]);
	}

	/**
	 * набор всех вводов интернет привязанных к цепочке документов
	 * @return OrgInet[]
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
	 * @return OrgPhones[]
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
	 * @return LicItems[]
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
	 * @return Services[]
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
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getPartners()
	{
		//if (!is_null($this->partners_cache)) return $this->partners_cache;

		//return $this->partners_cache=Partners::fetchByField('contracts_id',$this->contracts_id)
		return $this->hasMany(Partners::class, ['id' => 'partners_id'])
			->viaTable('{{%partners_in_contracts}}', ['contracts_id' => 'id']);
	}

	public static function fetchNames(){
		$list= static::find()
			->with(['partners','users'])
			->all();
		return ArrayHelper::map($list, 'id', 'sname');
	}


	/**
	 * Следующий id
	 * @return integer
	 */
	public static function fetchNextId() {
		return static::find()->max("id")+1;
	}
	
	/**
	 * @param array|string $ids передаем список контрактов
	 * @param string $form имя формы
	 * @return string на выходе список хинтов
	 */
	public static function fetchArmsHint($ids,$form='') {

		if (!is_array($ids))
			$ids=explode(',',$ids);

		if (!count($ids)) return '';

		$arms= Techs::find()
			->joinWith(['contracts','model.type'])
			->where(['techs_contracts.id'=>$ids,'tech_types.is_computer'=>1])
			->all();

		if (!count($arms)) return '';

		$hint='АРМы из привязанных документов: ';
		foreach ($arms as $arm) {
			/**
			 * @var $arm Techs
			 */
			$js=strlen($form)?
				"onclick=\"$('#$form-arms_id,#$form-arms_ids').val({$arm->id}).trigger('change');\"":
				'';
			$ttip= Url::to(['/techs/ttip','id'=>$arm->id]);
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
			$ttip= Url::to(['/contracts/ttip','id'=>$doc->id]);
			$hint.="<br /><span class='href' qtip_ajxhrf='$ttip' $js>{$doc->selfSname}</span>";
		}
	
		return $hint;
	}
	
	
	function getIsUnpaid() {
		return is_object($this->state)&&$this->state->unpaid;
	}

	function getIsPaid() {
		return is_object($this->state)&&$this->state->paid;
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
		/** @noinspection JSJQueryEfficiency */
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
			
			if (!$this->parent_id) $this->is_successor=0;
			if ($this->is_successor) {
				//Если этот документ заменяет предыдущую версию договора,
				//то мы его пришиваем именно к последней версии, чтобы не было
				//двух документов заменяющих один (это нарушает логику)
				$this->parent_id=$this->firstPredecessor->id;
			}
			if ($this->parent_id === $this->id) {
				$this->parent_id=null;
				$this->is_successor=0;
			}
		}
		return true;
	}
	
}
