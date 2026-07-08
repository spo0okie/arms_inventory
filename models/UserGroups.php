<?php

namespace app\models;

use app\models\base\ArmsModel;

/**
 * This is the model class for table "user_groups".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $notebook
 * @property string $ad_group
 * @property string $sync_time
 * @property array $users_ids
 * @property \app\models\Users[] $users
 */
class UserGroups extends ArmsModel
{

	public static $title='Группа пользователей';
	public static $titles='Группы пользователей';

	public static function modelDescription(): string
	{
		return 'Группы пользователей — именованные списки сотрудников. '
			.'Состав группы может синхронизироваться с группой AD (скрипт на стороне AD работает с этой БД через REST API).';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'user_groups';
	}

	public $linksSchema=[
		'users_ids' => [Users::class],
	];

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['name', 'description'], 'required'],
			[['users_ids'], 'each', 'rule'=>['integer']],
			[['description', 'notebook'], 'string'],
			[['sync_time'], 'safe'],
			[['name'], 'string', 'max' => 64],
			[['ad_group'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return array_merge(parent::attributeData(), [
			'name' => [
				'Имя группы',
				'hint' => 'Короткое имя для обозначения группы',
				'typeClass' => \app\types\StringType::class,
			],
			'description' => [
				'Описание группы',
				'hint' => 'Развернутое объяснение назначения этой группы',
				'typeClass' => \app\types\TextType::class,
			],
			'users_ids' => [
				'Члены группы',
				'hint' => 'Список сотрудников входящих в эту группу',
				'placeholder' => 'Выберите сотрудников',
				'typeClass' => \app\types\LinkType::class,
			],
			'notebook' => [
				'Записная книжка',
				'hint' => 'Все что нужно записать по этой группе кроме заданных отдельными полями параметров',
				'typeClass' => \app\types\TextType::class,
			],
			'ad_group' => [
				'Синхронизация с AD',
				'hint' => 'Имя группы в AD с которой синхронизировать состав этой группы (скрипт работает на стороне AD c этой БД через REST API). Если пусто - синхронизации не будет',
				'typeClass' => \app\types\StringType::class,
			],
			'sync_time' => [
				'Время последней синхронизации',
				'hint' => 'Параметр только для чтения. Показывает когда производилась последняя синхронизация с AD',
				'readOnly' => true,
				'typeClass' => \app\types\DatetimeType::class,
			],
		]);
	}

	/**
	 * Возвращает членов группы
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->viaTable('{{%users_in_groups}}', ['groups_id' => 'id']);
	}

	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
