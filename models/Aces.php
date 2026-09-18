<?php

namespace app\models;

use app\models\base\ArmsModel;
use app\generation\context\GenerationContext;
use app\models\traits\AcesModelCalcFieldsTrait;
use Yii;
use app\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * This is the model class for table "aces".
 *
 * @property int $id
 * @property int $acls_id
 * @property string $ips
 * @property string $comment
 * @property string $notepad
 * @property string $sname
 * @property bool $is_forward запись описывает проброс соединения (NAT, реверс-прокси), а не доступ субъекта
 * @property Acls	$acl
 * @property Users[]	$users
 * @property Departments[]	$departments
 * @property Comps[]	$comps
 * @property NetIps[]	$netIps
 * @property AccessTypes[] $accessTypes
 * @property Partners[] $partners
 * @property Networks[] $networks
 * @property Services[] $services
 * @property int[]	$netIps_ids
 * @property int[]	$comps_ids
 * @property int[]	$users_ids
 * @property int[]	$services_ids
 * @property int[]	$networks_ids
 * @property int[]	$segments_ids сегменты-субъекты
 * @property Segments[] $segments
 * @property int[]	$access_types_ids
 * @property int[]	$next_aces_ids следующие хопы транзита
 * @property int[]	$prev_aces_ids предыдущие хопы транзита
 * @property Aces[]	$nextAces
 * @property Aces[]	$prevAces
 */
class Aces extends ArmsModel
{
	use AcesModelCalcFieldsTrait;

	public static $title='Доступ';
	public static $titles='Доступы';

	public static function modelDescription(): string
	{
		return 'Записи доступа (ACE): какие субъекты (пользователи, IP) какой тип доступа получают; группируются в списки доступа (ACL).';
	}

	public static $noAccessName='нет доступа';

	public $ipParamsStorage;	//ip параметры доступа

	/**
	 * {@inheritdoc}
	 */
	public $linksSchema=[
		'access_types_ids' => [AccessTypes::class,'aces_ids'],
		'comps_ids' =>		[Comps::class,'aces_ids'],
		'users_ids' =>		[Users::class,'aces_ids'],
		'services_ids' =>	[Services::class,'aces_ids'],
		'networks_ids' =>	[Networks::class,'aces_ids'],
		'segments_ids' =>	[Segments::class,'aces_ids'],
		'netIps_ids' =>		[NetIps::class,'aces_ids'],
		'acls_id' =>		[Acls::class,'aces_ids'],
		//транзит (plans/access-chains.md, итерация 3): обе стороны одной junction-таблицы;
		//связь — документация маршрута, удалению записи не мешает
		'next_aces_ids' =>	[Aces::class,'prev_aces_ids','loader'=>'nextAces','deletable'=>true],
		'prev_aces_ids' =>	[Aces::class,'next_aces_ids','loader'=>'prevAces','deletable'=>true],
	];

	public function getLinksSchema()
	{
		//дополняем нашу статичную схему связей апдейтером для параметров типов доступа
		return ArrayHelper::recursiveOverride($this->linksSchema,[
			'access_types_ids' => ['updater'=>[
				'viaTableAttributesValue' => [
					'ip_params' => function($updater, $relatedPk) {
						$ace = $updater->getBehavior()->owner;
						/** @var Aces $ace */
						return $ace->getIpParams()[$relatedPk]??null;
					},
				]
			]],
		]);
	}

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aces';
    }

	public function extraFields()
	{
		return array_merge(parent::extraFields(),[
			'accessTypes',
			'users',
			'acl',
		]);
	}


	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['acls_id'], 'integer'],
			[['acls_id'], 'required'],
			[['comps_ids','users_ids','access_types_ids','netIps_ids','services_ids','networks_ids','segments_ids'], 'each', 'rule'=>['integer']],
			[['ipParams'], 'each', 'rule'=>['string']],
			[['is_forward'], 'boolean'],
			[['next_aces_ids','prev_aces_ids'], 'each', 'rule'=>['integer']],
			//сторож циклов: маршрут не должен возвращаться в пройденный хоп
			['next_aces_ids',function ($attribute){
				$this->validateTransitLink($attribute,'nextAces');
			}],
			['prev_aces_ids',function ($attribute){
				$this->validateTransitLink($attribute,'prevAces');
			}],
            [['ips', 'notepad','name'], 'string'],
            [['comment'], 'string', 'max' => 255],
			['ips', function ($attribute) {
				Networks::validateInput($this,$attribute);
			}],
			['ips', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],
			[['services_ids', 'ips', 'comps_ids', 'users_ids', 'segments_ids', 'comment'],
				'validateRequireOneOf',
				'skipOnEmpty' => false,
				'params'=>['attrs'=>['services_ids', 'ips', 'comps_ids', 'users_ids', 'segments_ids', 'comment']]
			]
        ];
    }

	public function afterGenerate(GenerationContext $context, array $options = []): void
	{
		parent::afterGenerate($context, $options);

	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'acls_id' => [
				'ACL',
				'hint' => 'К какому списку доступа (ACL) относится эта запись доступа (ACE)',
				'join' => ['acl'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'access_types_ids' => [
				AccessTypes::$titles,
				'hint'=>'Какой доступ субъекты получают к ресурсам'
					.'<br>Список можно сузить фильтром по названию, а кнопкой «+» — создать новый тип доступа, не покидая форму',
				'indexHint'=>'Какой доступ субъекты получают к ресурсам',
				'join' => ['accessTypes'],			//для поиска или вывода в таблице нужно заджойнить типы доступа
				'filter' => 'access_types.name',		//при поиске ищем по имени типа доступа
				'typeClass'=>\app\types\LinkType::class,
			],
			'access_types' => ['alias'=>'access_types_ids'],
			'accessTypes' => ['alias'=>'access_types_ids'],
			'comment' => [
				'Прочее',
				'hint' => 'Если есть какие-то объекты предоставления доступа, которые не получается учесть через другие поля,<br> вписываем их текстом сюда',
				'viewHint' => 'Прочие объекты доступа (текстовое описание)',
				'typeClass'=>\app\types\TextType::class,
			],
			'comps_ids' => [
				'Компьютеры',
				'hint' => 'Компьютеры/серверы с которых разрешается доступ',
				'join' => ['comps'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'id' => ['ID','typeClass'=>\app\types\IntegerType::class],
			'ipParams' => [
				'IP параметры типов доступа',
				'hint' => 'Вообще не должно вылазить в UI, это служебный атрибут для записи параметров в junction таблицу',
				'type' => 'string[]',	//генератор его нормально не создаст, это надо будет создавать в ModelResolver
				'typeClass'=>\app\types\StringArrayType::class,
			],
			'ips' => [
				'IP адреса и сети',
				//формат заполнения (по одному в строке, адрес/сеть с маской) подскажет IpsType
				'hint' => 'IP адреса и сети, из которых разрешается доступ<br>'
					.'Сеть (с маской) должна быть уже заведена — незаведённые сети при сохранении отбрасываются; '
					.'отдельный адрес указывается без маски и создаётся автоматически',
				'join' => ['netIps','networks'],
				'typeClass'=>\app\types\IpsType::class,
			],
			'name' => [
				'Пояснение',
				'hint'=>'С какой целью у этого объекта доступ к этому ресурсу<br>'
					.'<i>Например:</i><ul>'
					.'<li>Забирает список пользователей по WEB-API <i>(про доступ одного сервиса к другому)</i></li>'
					.'<li>Подключается к своему АРМ <i>(про доступ пользователя к ОС)</i></li>'
					.'<li>Отправляет уведомления по почте <i>(про доступ одного сервиса к другому по SMTP)</i></li>'
					.'</ul>',
				'typeClass'=>\app\types\TextType::class,
			],
			'netIps_ids' => [
				'readOnly' => true, //при записи они формируются из поля ips
				'hint' => 'IP адреса, с которых разрешается доступ.',
				'apiHint' => '{same} Список ссылок на объекты NetIps из поля ips. '
					.'При записи все объекты находятся среди существующих либо создаются автоматически',
				'typeClass'=>\app\types\LinkType::class,
			],
			'networks_ids' => [
				'readOnly' => true, //при записи они формируются из поля ips
				'hint' => 'IP сети, с которых разрешается доступ.',
				'apiHint' => '{same} Список ссылок на объекты Networks. Список формируется при записи поля ips. '
					.'Если в ips указана отсутствующая в БД сеть, то она выбрасывается из поля ips',
				'typeClass'=>\app\types\LinkType::class,
			],
			'is_forward' => [
				'Проброс (NAT, реверс-прокси)',
				'hint' => 'Запись описывает не доступ субъекта к ресурсу, а проброс соединения: '
					.'субъект — адрес входа (белый IP), ресурс списка доступа — узел назначения или его адрес.<br>'
					.'Типы доступа — обычные (HTTPS, RDP…), в сетевых параметрах — <b>порт входа -> порт назначения</b>, '
					.'например <b>TCP 443->8443</b>; без стрелки порт не меняется.<br>'
					.'Такие записи показываются у узла, его адресов и DNS-имён как «доступен снаружи»',
				'indexLabel' => 'Проброс',
				'indexHint' => 'Запись описывает проброс соединения (NAT, реверс-прокси), а не доступ субъекта к ресурсу',
				'typeClass'=>\app\types\BooleanType::class,
			],
			'next_aces_ids' => [
				'Следующие хопы',
				'hint' => 'Транзит: куда соединение уходит дальше. Если ресурс этой записи — посредник '
					.'(реверс-прокси, шлюз), здесь указываются его исходящие записи доступа, в которые '
					.'продолжается именно это соединение.<br>'
					.'Так связь «А ходит в В через Б» складывается из двух записей (А→Б и Б→В) и указателя между ними',
				'indexLabel' => 'Следующие хопы',
				'placeholder' => 'Соединение заканчивается на ресурсе',
				'typeClass'=>\app\types\LinkType::class,
			],
			'nextAces' => ['alias'=>'next_aces_ids'],
			'prev_aces_ids' => [
				'Предыдущие хопы',
				'hint' => 'Транзит: чьё соединение продолжает эта запись. Если субъект этой записи — посредник '
					.'(реверс-прокси, шлюз), здесь указываются его входящие записи доступа, которые он пробрасывает сюда.<br>'
					.'Обратная сторона «следующих хопов»: достаточно заполнить с любой стороны',
				'indexLabel' => 'Предыдущие хопы',
				'placeholder' => 'Соединение начинается с субъекта',
				'typeClass'=>\app\types\LinkType::class,
			],
			'prevAces' => ['alias'=>'prev_aces_ids'],
			'transit' => [
				'Маршрут',
				'hint' => 'Полные маршруты, в которых участвует эта запись: от первого субъекта через посредников '
					.'до конечного ресурса. Собираются по указателям «следующие/предыдущие хопы»',
				'indexHint' => '{same}.<br>Пусто — соединение не транзитное (один хоп)',
				'typeClass'=>\app\types\StringType::class,
				'readOnly' => true,
			],
			'notepad' => [
				'Заметки по этой ACE',
				'hint' => 'Это заметки к Access Entry - записи доступа. Их у одной ACL может быть несколько.'
					.'<br>Если есть какие-то заметки по этому субъкту(ам), типу доступа(ов),'
					.'<br>то можно их записать здесь.',
				'typeClass'=>\app\types\TextType::class,
			],
			'resource' => [
				'Ресурс',
				'indexHint' => 'К какому ресурсу субъект получает доступ',
				'join'=>['acl.service','acl.comp','acl.tech','acl.ip','acl.network','acl.segment',],
				'typeClass'=>\app\types\StringType::class,
			],
			'resource_nodes' => [
				'Узлы ресурса',
				'indexHint' => 'К каким узлам ресурса получают доступ субъекты:<br>'
					.'В случае если доступ предоставляется к сервису, то<br>'
					.'он автоматически предоставляется и к узлам, на которых сервис крутится',
				//NB: acl.aces сюда добавлять нельзя - joinWith в prepareSearch упадет
				//на self-join таблицы aces без алиаса (проверено)
				'join'=>['acl.service','acl.comp','acl.tech','acl.ip','acl.network','acl.segment',],
				'typeClass'=>\app\types\StringType::class,
			],
			'schedule'=> [
				'Временное ограничение',
				'Наименование временного доступа в рамках которого действует эта ACE (запись доступа)',
				'join' => ['acl.schedule'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'services_ids' => [
				'Сервисы',
				'hint' => 'Сервисы, которым предоставляется доступ<br>'
					.'Подразумевает доступ всех узлов, обеспечивающих работу перечисленных сервисов',
				'join' => ['services'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'segments_ids' => [
				'Сегменты',
				'hint' => 'Сегменты инфраструктуры, из которых разрешается доступ: сразу из всех сетей и сервисов сегмента.<br>'
					.'Доступы «сегмент → сегмент» складываются в матрицу межсегментного доступа',
				'join' => ['segments'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'subjects' => [
				'Субъекты',
				'indexHint' => 'Субъекты доступа: кто получает доступ',
				'join' => ['users','comps','services','netIps','networks','segments'],
				//значение - гетерогенный список объектов (ref уводит вывод на объектный
				//путь renderItem), StringType остаётся для поиска/подсказок
				'ref'=>\app\models\base\ArmsModel::class, 'refMulti'=>true,
				'typeClass'=>\app\types\StringType::class,
			],
			'subject_nodes' => [
				'Узлы субъектов',
				'indexHint' => 'Какие узлы субъектов получают доступ:<br>'
					.'В случае если доступ предоставляется сервису, то<br>'
					.'он автоматически предоставляется узлам, на которых сервис крутится',
				'join' => ['users','comps','services','netIps','networks','segments'],
				'typeClass'=>\app\types\StringType::class,
			],
			//read-only вычисляемая ссылка (категория C): гетерогенный список
			//узлов-субъектов (Users/Comps/NetIps/узлы сервисов), потому базовый класс
			'nodes' => ['ref'=>\app\models\base\ArmsModel::class, 'refMulti'=>true],
			'users_ids' => [
				Users::$titles,
				'hint' => Users::$titles.', которым предоставляется доступ<br>'.
					'Сотрудников других организаций можно также добавить в список пользователей',
				'join' => ['users'],
				'typeClass'=>\app\types\LinkType::class,
			],
		]);
	}



	/**
	 * SQL-условие «у записи доступа остались живые субъекты»: есть хоть один
	 * неархивный субъект ЛИБО объектных субъектов нет вовсе (запись описана текстом
	 * в «Прочее» - архивироваться нечему).
	 *
	 * Условие самодостаточно: коррелированные подзапросы по aces.id не зависят от
	 * того, какие связи заджойнены в запросе (набор join-ов в списках плавает
	 * от видимых колонок). PHP-двойник - AcesModelCalcFieldsTrait::getArchived().
	 *
	 * @return array условие для ActiveQuery::andWhere()
	 */
	public static function aliveSubjectsCondition(): array
	{
		//[таблица субъектов, junction-таблица, ключ junction, условие «субъект жив»]
		$subjects=[
			[Users::tableName(),	'users_in_aces',	'users_id',		'COALESCE(users.Uvolen,0)=0'],
			[Comps::tableName(),	'comps_in_aces',	'comps_id',		'COALESCE(comps.archived,0)=0'],
			[Services::tableName(),	'services_in_aces',	'services_id',	'COALESCE(services.archived,0)=0'],
			[Networks::tableName(),	'networks_in_aces',	'networks_id',	'COALESCE(networks.archived,0)=0'],
			[Segments::tableName(),	'segments_in_aces',	'segments_id',	'COALESCE(segments.archived,0)=0'],
			//у IP-адреса своей архивности нет - она у сети, которой адрес принадлежит
			[NetIps::tableName(),	'ips_in_aces',		'ips_id',		'COALESCE(ip_networks.archived,0)=0'],
		];

		$any=['or'];	//есть хоть какой-то объектный субъект
		$alive=['or'];	//есть хоть один живой субъект
		foreach ($subjects as [$table,$junction,$key,$aliveWhere]) {
			$query=(new \yii\db\Query())
				->from($junction)
				->innerJoin($table,$table.'.id='.$junction.'.'.$key)
				->where($junction.'.aces_id=aces.id');
			if ($table===NetIps::tableName())
				$query->leftJoin(Networks::tableName().' ip_networks','ip_networks.id='.$table.'.networks_id');
			$any[]=['exists',$query];
			$alive[]=['exists',(clone $query)->andWhere($aliveWhere)];
		}

		return ['or',$alive,['not',$any]];
	}

	public function getAcl()
	{
		return $this->hasOne(Acls::class, ['id' => 'acls_id']);
	}

	/**
	 * Следующие хопы транзита. Алиас обязателен: self-join таблицы aces без алиаса
	 * роняет joinWith поиска.
	 * @return \yii\db\ActiveQuery
	 */
	public function getNextAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'next_aces_id'])->from(['next_aces'=>Aces::tableName()])
			->viaTable('{{%aces_next_aces}}', ['aces_id' => 'id']);
	}

	/**
	 * Предыдущие хопы транзита (обратная сторона той же таблицы)
	 * @return \yii\db\ActiveQuery
	 */
	public function getPrevAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'aces_id'])->from(['prev_aces'=>Aces::tableName()])
			->viaTable('{{%aces_next_aces}}', ['next_aces_id' => 'id']);
	}

	/**
	 * Сторож циклов транзита: обходит цепочку в сторону $getLink от выбранных в форме
	 * хопов и ругается, если маршрут возвращается в уже пройденную запись (или в себя).
	 * @param string $attribute next_aces_ids|prev_aces_ids
	 * @param string $getLink nextAces|prevAces
	 */
	public function validateTransitLink(string $attribute, string $getLink): void
	{
		$ids=array_filter(array_map('intval',(array)$this->$attribute));
		if (!count($ids)) return;
		$this->validateRecursiveLink($attribute,[
			'getLink'=>$getLink,
			'initialLink'=>static::find()->where(['id'=>$ids])->all(),
		]);
	}

	/**
	 * Подпись записи для выбора хопа в форме: "пояснение: субъекты → ресурс"
	 * @return string
	 */
	public function getHopLabel(): string
	{
		$subjects=[];
		foreach ($this->subjects as $subject) $subjects[]=is_object($subject)?$subject->name:(string)$subject;
		return ($this->name?$this->name.': ':'')
			.implode(', ',$subjects)
			.' → '.(is_object($this->acl)?$this->acl->sname:'?');
	}

	/**
	 * Узлы и сервисы "вокруг" сервиса, по которым ищутся соседние хопы: сам сервис
	 * с предками и потомками (доступ к предку — доступ и сюда, см. views/services/view.php).
	 * @param Services $service
	 * @return int[]
	 */
	protected static function serviceFamilyIds(Services $service): array
	{
		$ids=[(int)$service->id];
		foreach ((array)$service->getChildrenRecursive() as $child) $ids[]=(int)$child->id;
		foreach (Services::buildTreeBranch($service,'parentService') as $parent) $ids[]=(int)$parent->id;
		return array_values(array_unique($ids));
	}

	/**
	 * Кандидаты в следующие хопы: записи доступа, у которых субъект — ресурс этой
	 * записи (посредник): сам сервис-ресурс с роднёй, узлы ресурса и их адреса.
	 * Уже выбранные хопы остаются в списке всегда.
	 * @return array [id => подпись]
	 */
	public function nextCandidates(): array
	{
		$ids=array_map('intval',(array)$this->next_aces_ids);
		$acl=$this->acl;
		if (is_object($acl)) {
			$services=$comps=$ips=[];
			if (is_object($acl->service)) $services=static::serviceFamilyIds($acl->service);
			foreach ($acl->nodes as $node) {
				if ($node instanceof Comps) {
					$comps[]=(int)$node->id;
					foreach ($node->netIps as $ip) $ips[]=(int)$ip->id;
				} elseif ($node instanceof NetIps) $ips[]=(int)$node->id;
			}
			foreach ([
				['services_in_aces','services_id',$services],
				['comps_in_aces','comps_id',$comps],
				['ips_in_aces','ips_id',$ips],
			] as [$table,$column,$values]) if (count($values)) {
				$ids=array_merge($ids,(new \yii\db\Query())->select('aces_id')->from($table)
					->where([$column=>$values])->column());
			}
		}
		return static::hopLabels($ids,[(int)$this->id]);
	}

	/**
	 * Кандидаты в предыдущие хопы: записи доступа к субъектам этой записи (посреднику):
	 * ACL на сервисы-субъекты с роднёй, на ОС-субъекты и на адреса-субъекты.
	 * @return array [id => подпись]
	 */
	public function prevCandidates(): array
	{
		$ids=array_map('intval',(array)$this->prev_aces_ids);
		$services=[];
		foreach ((array)$this->services as $service) $services=array_merge($services,static::serviceFamilyIds($service));
		$comps=array_map('intval',(array)$this->comps_ids);
		$ips=array_map('intval',(array)$this->netIps_ids);
		$resource=['or'];
		if (count($services)) $resource[]=['acls.services_id'=>$services];
		if (count($comps)) $resource[]=['acls.comps_id'=>$comps];
		if (count($ips)) $resource[]=['acls.ips_id'=>$ips];
		if (count($resource)>1) {
			$ids=array_merge($ids,(new \yii\db\Query())->select('aces.id')->from('aces')
				->innerJoin('acls','acls.id=aces.acls_id')->where($resource)->column());
		}
		return static::hopLabels($ids,[(int)$this->id]);
	}

	/**
	 * @param int[] $ids
	 * @param int[] $exclude
	 * @return array [id => подпись хопа], по подписи
	 */
	protected static function hopLabels(array $ids, array $exclude=[]): array
	{
		$ids=array_diff(array_unique(array_map('intval',$ids)),$exclude);
		if (!count($ids)) return [];
		$labels=[];
		foreach (static::find()->where(['aces.id'=>$ids])->with(static::ROUTES_WITH)->all() as $ace)
			$labels[$ace->id]=$ace->hopLabel;
		asort($labels);
		return $labels;
	}

	/** @var null|array рёбра транзита [next=>[id=>[ids]], prev=>[id=>[ids]]] — одна выборка на запрос */
	private static $transitEdgesCache=null;

	/**
	 * Все рёбра транзита из общего кэша: маршруты дергаются на каждую строку списков
	 * доступов, без кэша это запросы на каждый ACE (у подавляющего большинства транзита нет).
	 * @return array
	 */
	public static function transitEdges(): array
	{
		if (is_null(static::$transitEdgesCache)) {
			static::$transitEdgesCache=['next'=>[],'prev'=>[]];
			foreach ((new \yii\db\Query())->from('aces_next_aces')->all() as $row) {
				static::$transitEdgesCache['next'][(int)$row['aces_id']][]=(int)$row['next_aces_id'];
				static::$transitEdgesCache['prev'][(int)$row['next_aces_id']][]=(int)$row['aces_id'];
			}
		}
		return static::$transitEdgesCache;
	}

	public static function resetTransitCache(): void
	{
		static::$transitEdgesCache=null;
	}

	/** Сколько маршрутов максимум собирать на одну запись (ветвления перемножаются) */
	const ROUTES_LIMIT=12;
	/** Предельная длина полумаршрута — страховка от циклов, заведённых мимо валидации */
	const ROUTE_DEPTH_LIMIT=10;

	/**
	 * Полумаршруты от записи в одну сторону: списки ID без самой записи,
	 * в порядке удаления от неё.
	 * @param int $id
	 * @param string $direction next|prev
	 * @param int[] $visited пройденные записи (цикл обрываем)
	 * @return int[][]
	 */
	protected static function transitBranches(int $id, string $direction, array $visited=[]): array
	{
		$visited[]=$id;
		$links=static::transitEdges()[$direction][$id]??[];
		if (!count($links) || count($visited)>static::ROUTE_DEPTH_LIMIT) return [[]];
		$branches=[];
		foreach ($links as $linkId) {
			if (in_array($linkId,$visited)) continue;
			foreach (static::transitBranches($linkId,$direction,$visited) as $tail)
				$branches[]=array_merge([$linkId],$tail);
		}
		return count($branches)?$branches:[[]];
	}

	/**
	 * Участвует ли запись в транзите (есть хоть один следующий или предыдущий хоп)
	 * @return bool
	 */
	public function getHasTransit(): bool
	{
		if ($this->isNewRecord) return false;
		$edges=static::transitEdges();
		return isset($edges['next'][$this->id]) || isset($edges['prev'][$this->id]);
	}

	/**
	 * Полные маршруты через эту запись: каждый — список ID записей от первого хопа
	 * до последнего (сама запись внутри). Пусто, если транзита нет.
	 * @return int[][]
	 */
	public function getRouteIds(): array
	{
		if (!$this->hasTransit) return [];
		$routes=[];
		foreach (static::transitBranches((int)$this->id,'prev') as $up) {
			foreach (static::transitBranches((int)$this->id,'next') as $down) {
				$routes[]=array_merge(array_reverse($up),[(int)$this->id],$down);
				if (count($routes)>=static::ROUTES_LIMIT) return $routes;
			}
		}
		return $routes;
	}

	/**
	 * Маршруты текстом (экспорт, API): «субъекты → ресурс → ресурс», по строке на маршрут
	 * @return string
	 */
	public function getTransit(): string
	{
		$lines=[];
		foreach (static::routesOf([$this])[$this->id]??[] as $hops) {
			$first=reset($hops);
			$names=[];
			foreach ($first->subjects as $subject) $names[]=is_object($subject)?$subject->name:(string)$subject;
			$line=implode(', ',$names);
			foreach ($hops as $hop) $line.=' → '.(is_object($hop->acl)?$hop->acl->sname:'?');
			$lines[]=$line;
		}
		return implode("\n",$lines);
	}

	/** Связи, нужные рендеру маршрутов (views/aces/routes.php) */
	const ROUTES_WITH=['acl.comp','acl.tech','acl.service','acl.ip','acl.network','accessTypes',
		'users','comps','services','netIps','networks','segments'];

	/**
	 * Маршруты через набор записей одной пачкой: [id записи => [маршрут => Aces[]]].
	 * Все записи всех маршрутов грузятся одним запросом.
	 * @param Aces[] $aces
	 * @return array
	 */
	public static function routesOf(array $aces): array
	{
		$routeIds=[];
		$allIds=[];
		foreach ($aces as $ace) {
			$routeIds[$ace->id]=$ace->routeIds;
			foreach ($routeIds[$ace->id] as $route) foreach ($route as $id) $allIds[$id]=$id;
		}
		if (!count($allIds)) return [];
		$loaded=static::find()->where(['aces.id'=>array_values($allIds)])->with(static::ROUTES_WITH)->indexBy('id')->all();
		$result=[];
		foreach ($routeIds as $aceId=>$routes) {
			foreach ($routes as $route) {
				$hops=[];
				foreach ($route as $id) if (isset($loaded[$id])) $hops[]=$loaded[$id];
				if (count($hops)>1) $result[$aceId][]=$hops;
			}
		}
		return $result;
	}

	/**
	 * Мягкая проверка стыка хопов: продолжает ли $next соединение, пришедшее по $prev, —
	 * т.е. есть ли ресурс предыдущего хопа (или его узлы) среди субъектов следующего.
	 * Не запрет (субъекты бывают шире: сеть, «все») — только подсказка в маршруте.
	 * @param Aces $prev
	 * @param Aces $next
	 * @return bool true — стык сходится
	 */
	public static function transitJoint(Aces $prev, Aces $next): bool
	{
		if (!is_object($prev->acl)) return true;
		$resource=$prev->acl->resource;
		if (!is_object($resource)) return true;	//текстовый ресурс не проверить
		$uuids=[$resource->uuid()=>true];
		foreach ($prev->acl->nodes as $node) if (is_object($node)) $uuids[$node->uuid()]=true;
		foreach ($next->subjects as $subject) if (is_object($subject) && isset($uuids[$subject->uuid()])) return true;
		foreach ($next->nodes as $node) if (is_object($node) && isset($uuids[$node->uuid()])) return true;
		return false;
	}


	/**
	 * Привязанные пользователи
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->from(['users_subjects'=>Users::tableName()])
			->viaTable('{{%users_in_aces}}', ['aces_id' => 'id']);
	}

	/**
	 * Привязанные сервисы
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['id' => 'services_id'])
			->from(['services_subjects'=>Services::tableName()])
			->viaTable('{{%services_in_aces}}', ['aces_id' => 'id']);
	}

	/**
	 * Сегменты-субъекты
	 */
	public function getSegments()
	{
		return $this->hasMany(Segments::class, ['id' => 'segments_id'])
			->from(['segments_subjects'=>Segments::tableName()])
			->viaTable('{{%segments_in_aces}}', ['aces_id' => 'id']);
	}

	/**
	 * Привязанные сети
	 */
	public function getNetworks()
	{
		return $this->hasMany(Networks::class, ['id' => 'networks_id'])
			->from(['networks_subjects'=>Networks::tableName()])
			->viaTable('{{%networks_in_aces}}', ['aces_id' => 'id']);
	}

	/**
	 * Привязанные пользователи
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->from(['comps_subjects'=>Comps::tableName()])
			->viaTable('{{%comps_in_aces}}', ['aces_id' => 'id']);
	}

	/**
	 * Привязанные пользователи
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])
			->from(['ips_subjects'=>NetIps::tableName()])
			->viaTable('{{%ips_in_aces}}', ['aces_id' => 'id']);
	}

	/**
	 * Типы доступа
	 */
	public function getAccessTypes()
	{
		return $this->hasMany(AccessTypes::class, ['id' => 'access_types_id'])
			->viaTable('{{%access_in_aces}}', ['aces_id' => 'id']);
	}



	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
			->orderBy(['name'])
            ->all();
        return ArrayHelper::map($list, 'id', 'sname');
    }


	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {

			/* взаимодействие с NetIPs */
			$this->netIps_ids=NetIps::fetchIpIds($this->ips,true);
			$this->networks_ids=Networks::fetchNetworkIds($this->ips);

			//грузим старые значения записи
			$old=static::findOne($this->id);
			if (!is_null($old)) {
				//находим все IP адреса которые от этой ОС отвалились
				$removed = array_diff($old->netIps_ids, $this->netIps_ids);
				//если есть отвязанные от это ос адреса
				if (count($removed)) foreach ($removed as $id) {
					//если он есть в БД
					if (is_object($ip=NetIps::findOne($id))) $ip->detachAce($this->id);
				}
			}
			return true;
		}
		return false;
	}


	/**
	 * @inheritdoc
	 */
	/**
	 * {@inheritdoc}
	 * Рёбра транзита кэшируются статически на запрос — после записи/удаления ACE
	 * (в том же процессе: тесты, POST-экшены) кэш обязан перечитаться.
	 */
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		static::resetTransitCache();
	}

	/**
	 * {@inheritdoc}
	 */
	public function afterDelete()
	{
		parent::afterDelete();
		static::resetTransitCache();
	}

	public function beforeDelete()
	{
		if (!parent::beforeDelete()) {
			return false;
		}

		//отрываем IP от удаляемого компа
		foreach ($this->netIps as $ip) {
			$ip->detachAce($this->id);
		}

		return true;
	}

	/**
	 * Раскладывает сетевые параметры проброса на вход и назначение.
	 * Формат: «TCP 443->8443» (допустимы «=>», «→», пробелы вокруг стрелки);
	 * без стрелки порт не транслируется — назначение пустое.
	 * @param string $params
	 * @return array [string вход («TCP 443»), string назначение («8443» либо '')]
	 */
	public static function parseForwardParams(string $params): array
	{
		$parts=preg_split('/\s*(?:->|=>|→)\s*/u',trim($params),2);
		return [trim($parts[0]??''),trim($parts[1]??'')];
	}

	/**
	 * Связи, нужные рендеру пробросов (views/aces/forwards.php): жадно, иначе
	 * на каждую строку — отдельные запросы за ресурсом, адресами входа и их именами.
	 */
	const FORWARDS_WITH=['acl.comp','acl.tech.state','acl.ip.network','acl.ip.comps','acl.ip.techs','netIps.network','netIps.dnsNames','accessTypes'];

	/**
	 * Входящие пробросы: записи доступа с признаком проброса, у которых ресурс ACL — один
	 * из переданных узлов или адресов («доступен снаружи»). Архивные (истёкшее
	 * расписание, мёртвый ресурс/субъекты) отбрасываются.
	 *
	 * @param int[] $compsIds ОС-ресурсы
	 * @param int[] $techsIds оборудование-ресурсы
	 * @param int[] $ipsIds адреса-ресурсы
	 * @return Aces[]
	 */
	public static function findForwardsTo(array $compsIds=[], array $techsIds=[], array $ipsIds=[]): array
	{
		$resource=['or'];
		if (count($compsIds)) $resource[]=['acls.comps_id'=>$compsIds];
		if (count($techsIds)) $resource[]=['acls.techs_id'=>$techsIds];
		if (count($ipsIds)) $resource[]=['acls.ips_id'=>$ipsIds];
		if (count($resource)==1) return [];

		return static::filterAliveForwards(
			static::find()
				->innerJoin('acls','acls.id=aces.acls_id')
				->where(['aces.is_forward'=>1])
				->andWhere($resource)
				->with(static::FORWARDS_WITH)
				->distinct()
				->all()
		);
	}

	/**
	 * Исходящие пробросы: записи доступа с признаком проброса, у которых субъект — один
	 * из переданных адресов («пробрасывается на»).
	 * @param int[] $ipsIds адреса входа
	 * @return Aces[]
	 */
	public static function findForwardsFrom(array $ipsIds): array
	{
		if (!count($ipsIds)) return [];

		return static::filterAliveForwards(
			static::find()
				->innerJoin('ips_in_aces forward_ips','forward_ips.aces_id=aces.id')
				->where(['aces.is_forward'=>1])
				->andWhere(['forward_ips.ips_id'=>$ipsIds])
				->with(static::FORWARDS_WITH)
				->distinct()
				->all()
		);
	}

	/**
	 * @param Aces[] $aces
	 * @return Aces[] только действующие (не архивные) записи
	 */
	protected static function filterAliveForwards(array $aces): array
	{
		return array_values(array_filter($aces,static function(Aces $ace){return !$ace->archived;}));
	}

	/** @var null|array Кэш всей access_in_aces, сгруппированный по aces_id (грузится один раз на запрос) */
	private static $accessLinksCache=null;

	/**
	 * Строки access_in_aces этого ACE из общего кэша
	 * (в списках доступы дергаются на каждую строку - без кэша это запрос на каждый ACE)
	 * @return array
	 */
	public function getAccessLinks() {
		if (is_null(static::$accessLinksCache)) {
			static::$accessLinksCache=[];
			foreach ((new \yii\db\Query())->from('access_in_aces')->all() as $row)
				static::$accessLinksCache[$row['aces_id']][]=$row;
		}
		return static::$accessLinksCache[$this->id]??[];
	}

	/**
	 * Получить IP параметры доступов
	 */
	public function getIpParams() {
		//кэш (setIpParams) проверяем раньше isNewRecord: предзаполнение дефолтами
		//сервиса выставляет параметры и на новой (несохранённой) записи
		if (isset($this->attrsCache['ipParams'])) return $this->attrsCache['ipParams'];
		if ($this->isNewRecord) return [];
		$params=[];
		foreach ($this->accessLinks as $row) {
			$type=AccessTypes::getLoadedItem($row['access_types_id'],true);
			if (is_object($type) && $type->is_ip) {
				$params[$row['access_types_id']]=(string)$row['ip_params'];
			}
		}

		return $this->attrsCache['ipParams']=$params;
	}

	public function setIpParams($value) {
		$value=ArrayHelper::recursiveOverride($this->getIpParams(),$value);
		$this->attrsCache['ipParams']=$value;
	}

	/**
	 * Копирует содержимое этой записи доступа (субъекты, типы доступа, ip-параметры,
	 * comment, notepad, name) в другую ACE. Не копирует id и acls_id — их задаёт вызывающий.
	 *
	 * IP-субъекты переносятся через текстовое поле ips (на сохранении из него формируются
	 * netIps_ids/networks_ids). Используется при добавлении ресурса в группу: новый ACL
	 * получает копию общего набора ACE группы.
	 *
	 * @param Aces $target ACE-приёмник (как правило новый, ещё не сохранённый)
	 */
	public function copyContentTo(Aces $target): void
	{
		$target->name=$this->name;
		$target->comment=$this->comment;
		$target->notepad=$this->notepad;
		$target->ips=$this->ips;
		$target->users_ids=$this->users_ids ?: [];
		$target->comps_ids=$this->comps_ids ?: [];
		$target->services_ids=$this->services_ids ?: [];
		$target->segments_ids=$this->segments_ids ?: [];
		$target->is_forward=$this->is_forward;
		$target->access_types_ids=$this->access_types_ids ?: [];
		$ipParams=$this->getIpParams();
		if ($ipParams) $target->setIpParams($ipParams);
	}

	/**
	 * Канонический отпечаток записи доступа (ACE) для сравнения «одинаковости».
	 *
	 * Согласно ТЗ групповых ACL (plans/group-acls.md) две ACE одинаковы, если совпадают по:
	 * набору субъектов (users/comps/services/netIps/networks), типам доступа,
	 * параметрам доступа (ip_params), комментарию и записной книжке.
	 * «Пояснение» (name) в сравнение НЕ входит. Порядок элементов не важен.
	 *
	 * @return string
	 */
	public function aceSignature(): string
	{
		$norm=static function($ids){
			$ids=array_map('intval',(array)$ids);
			sort($ids);
			return $ids;
		};
		//id-шники берем через loaderIds (один запрос на связь за весь веб-запрос):
		//сигнатуры считаются для КАЖДОГО ACE страницы, а атрибуты `*_ids` (LinkerBehavior)
		//грузили бы модели связей отдельными запросами на каждый ACE (~13 запросов/ACE)
		$ids=fn(string $loader,string $attr)=>$this->loaderIds($loader) ?? $this->$attr;
		$ipParams=$this->getIpParams();
		ksort($ipParams);
		$parts=[
			'users'    => $norm($ids('users','users_ids')),
			'comps'    => $norm($ids('comps','comps_ids')),
			'services' => $norm($ids('services','services_ids')),
			'netips'   => $norm($ids('netIps','netIps_ids')),
			'networks' => $norm($ids('networks','networks_ids')),
			'segments' => $norm($ids('segments','segments_ids')),
			'types'    => $norm($ids('accessTypes','access_types_ids')),
			'ipparams' => $ipParams,
			'forward'  => (int)(bool)$this->is_forward,
			'comment'  => (string)$this->comment,
			'notepad'  => (string)$this->notepad,
		];
		return md5(json_encode($parts,JSON_UNESCAPED_UNICODE));
	}
}
