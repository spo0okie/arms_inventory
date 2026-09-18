<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "segments".
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $description
 * @property string $history
 * @property string $links
 * @property int $marker_id Цветовой маркер
 * @property Networks $networks
 * @property Services $services
 * @property Markers $marker
 */
class Segments extends ArmsModel
{
	use \app\models\traits\MarkerOwnerTrait;


	static $titles='Сегменты инфраструктуры';
	static $title='Сегмент инфраструктуры';

	public static function modelDescription(): string
	{
		return 'Сегменты ИТ инфраструктуры: области с разными требованиями информационной '
			.'безопасности; принадлежность объектов выводится из сетей и сервисов.';
	}
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'segments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['name'], 'string', 'max' => 32],
            [['code','description'], 'string', 'max' => 255],
			[['marker_id'], 'integer'],
			[['history','links'], 'safe'],
        ];
    }

	use \app\models\traits\AclsFieldTrait;	//incomingAces: записи доступа к сегменту как ресурсу

	public $linksSchema=[
		'services_ids' =>				[Services::class,'segment_id'],
		'networks_ids' =>				[Networks::class,'segment_id'],
		'marker_id' =>					[Markers::class,'segments_ids'],
		//сегмент в списках доступа (plans/access-chains.md, итерация 4): ресурс ACL и субъект ACE
		'acls_ids' =>					[Acls::class,'segments_id'],
		'aces_ids' =>					[Aces::class,'segments_ids'],
	];
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'code' => [
				'Код',
				'hint' => 'Служебное имя сегмента.'
					.'<br><i>Устарело: раньше использовалось как класс CSS для раскраски — теперь выбирайте цветовой маркер</i>',
				'typeClass'=>\app\types\StringType::class,
			],
			'marker_id' => [
				'Маркер',
				'hint' => 'Обеспечивает цветовую раскраску сегмента в интерфейсе.'
					.'<br>Наследуется сетями, IP-адресами и ячейками карты IPAM',
				'placeholder' => 'Без маркера',
			],
			'acls_ids' => [
				'Входящие доступы',
				'hint' => 'Списки доступа, у которых ресурсом обозначен этот сегмент: доступ сразу ко всем его '
					.'сетям и сервисам. Здесь видны все субъекты таких доступов, включая не-сегменты '
					.'(сервис, сеть, адрес) — в матрицу межсегментного доступа попадают только доступы «сегмент → сегмент»',
				'typeClass'=>\app\types\LinkType::class,
			],
			'aces_ids' => [
				'Исходящие доступы',
				'hint' => 'Записи доступа, у которых субъектом обозначен этот сегмент: доступ получают '
					.'сразу все его сети и сервисы',
				'typeClass'=>\app\types\LinkType::class,
			],
			'description' => [
				'Короткое описание',
				'hint' => 'Короткое описание сегмента, выводится в общем списке',
				'typeClass'=>\app\types\StringType::class,
			],
			'id' => ['ID','typeClass'=>\app\types\IntegerType::class],
			'links' => [
				'hint' => 'Ссылки на связанные страницы и ресурсы.<br>'
					.'При настроенной интеграции с DokuWiki сюда можно добавить статью вики с описанием сегмента — '
					.'она будет подгружаться во вкладку при просмотре сегмента и связанных с ним сетей',
			],
			'name' => [
				'Название',
				'hint' => 'Понятное человеку название',
				'typeClass'=>\app\types\StringType::class,
			],
		]);
	}
	
	/**
	 * @return ActiveQuery|Segments
	 */
	public function getNetworks()
	{
		return $this->hasMany(Networks::class, ['segments_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery|Segments
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['segment_id' => 'id']);
	}

	/**
	 * Списки доступа, у которых ресурс — этот сегмент (входящие доступы сегмента)
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['segments_id' => 'id']);
	}

	/**
	 * Записи доступа, у которых субъект — этот сегмент (исходящие доступы сегмента)
	 * @return ActiveQuery
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'aces_id'])
			->viaTable('{{%segments_in_aces}}', ['segments_id' => 'id']);
	}

	/**
	 * Узлы доступа сегмента: объект доступа — сам сегмент, а узлы — его подсети и
	 * узлы его сервисов. Одинаково для сегмента-ресурса и сегмента-субъекта.
	 * @return array [uuid => Networks|Comps|Techs]
	 */
	public function getAccessNodes(): array
	{
		if (isset($this->attrsCache['accessNodes'])) return $this->attrsCache['accessNodes'];
		$nodes=[];
		foreach ($this->networks as $network) $nodes[$network->uuid()]=$network;
		foreach ($this->services as $service)
			foreach ($service->nodesRecursive as $node)
				if (is_object($node)) $nodes[$node->uuid()]=$node;
		return $this->attrsCache['accessNodes']=$nodes;
	}

	/**
	 * Матрица межсегментного доступа (issue #220): ТОЛЬКО доступы, у которых ресурсом
	 * обозначен сегмент, а субъектом — тоже сегмент. Доступы, нацеленные на конкретный
	 * узел/сервис/сеть внутри сегмента, и доступы с несегментным субъектом сюда не
	 * попадают — они видны на карточке сегмента и во входящих соединениях сети.
	 * Архивные (истёкшее расписание) отбрасываются.
	 *
	 * @return array [id сегмента-субъекта][id сегмента-ресурса] => Aces[]
	 */
	public static function accessMatrix(): array
	{
		$matrix=[];
		$aces=Aces::find()
			->innerJoin('acls','acls.id=aces.acls_id')
			->innerJoin('segments_in_aces','segments_in_aces.aces_id=aces.id')
			->where(['not',['acls.segments_id'=>null]])
			->with(['acl.schedule','accessTypes','segments'])
			->distinct()
			->all();
		foreach ($aces as $ace) {
			/** @var Aces $ace */
			if ($ace->archived) continue;
			foreach ($ace->segments as $subject)
				$matrix[(int)$subject->id][(int)$ace->acl->segments_id][]=$ace;
		}
		return $matrix;
	}

	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->orderBy(['name'=>SORT_ASC])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
}
