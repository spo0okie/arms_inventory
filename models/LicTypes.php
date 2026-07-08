<?php

namespace app\models;

use app\models\base\ArmsModel;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "lic_types".
 *
 * @property int $id Идентификатор
 * @property string $name Служебное имя
 * @property string $descr Описание
 * @property string $comment Комментарий
 * @property string $links Ссылки
 * @property string $created_at Время создания
 *
 * @property LicGroups[] $licGroups Группы лицензий по этой схеме
 */
class LicTypes extends ArmsModel
{

	public static $title='Схема лицензирования';
	public static $titles='Схемы лицензирования';

	public static function modelDescription(): string
	{
		return 'Схемы лицензирования: как считается лицензия (на ПК, плавающая, на сокет и т.п.); переиспользуются в типах лицензий.';
	}

    /**
     * @inheritdoc
     */
    public static function tableName()
    {return 'lic_types';}

    public static $syncableFields=['name','descr','comment','links','updated_at','updated_by'];

	public $linksSchema=[
		'scans_id'=>Scans::class,
	];

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'descr', 'comment'], 'required'],
            [['created_at','links'], 'safe'],
            [['name'], 'string', 'max' => 32],
            [['descr'], 'string', 'max' => 128],
            [['name'], 'unique'],
        ];
    }

	/**
	 * @inheritdoc
	 */
	public function attributeData()
	{
		return [
			'comment' => [
				'Описание',
				'hint' => 'Подробное описание схемы лицензирования. Если описание сложное, можно завести страничку в Вики а сюда добавить ссылку в соотв поле, можно добавить ссылку на официальную документацию',
				'type' => 'text',
				'typeClass' => \app\types\TextType::class,
			],
			'created_at' => [
				'Время создания',
			],
			'descr' => [
				'Название',
				'hint' => 'Понятное для человека название схемы',
			],
			'links' => [
				'Ссылки',
				//формат заполнения подскажет UrlsType (inputHint)
				'hint' => 'Желательно указать ссылку на описание схемы лицензирования в интернете',
			],
			'name' => [
				'Служебное имя',
				'hint' => 'Машинное название схемы латиницей, в нижнем регистре и без пробелов. Пока не используется ни для чего. Формально можно использовать в CSS оформлении, но не реализовано и это',
			],
			//search-параметры контроллера (join-фильтры, не атрибуты модели)
			'product_id' => ['ID продукта (ПО)', 'typeClass'=>\app\types\IntegerType::class],
			'comp_name' => ['Имя компьютера (FQDN или DOMAIN\hostname)', 'typeClass'=>\app\types\StringType::class],
			'user_login' => ['Логин пользователя', 'typeClass'=>\app\types\StringType::class],
		];
	}



	/**
     * @return ActiveQuery
     */
    public function getLicGroups()
    {
        return $this->hasMany(LicGroups::className(), ['lic_types_id' => 'id']);
    }


	public static function fetchNames(){
		$list= static::find()
			->select(['id','descr'])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'id', 'descr');
	}

}
