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

	public $linksSchema=[
		'services_ids' =>				[Services::class,'segment_id'],
		'networks_ids' =>				[Networks::class,'segment_id'],
		'marker_id' =>					[Markers::class,'segments_ids'],
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

	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->orderBy(['name'=>SORT_ASC])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
}
