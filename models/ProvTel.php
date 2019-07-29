<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "prov_tel".
 *
 * @property int $id id
 * @property int $contracts_id Договор
 * @property string $name
 * @property string $account Номер л/с
 * @property string $cabinet_url Личный кабинет
 * @property string $support_tel Телефон поддержки
 * @property string $comment Комментарий
 *
 * @property Contracts $contract
 * @property OrgPhones[] $orgPhones
 * @property OrgInet[] $orgInet
 */
class ProvTel extends \yii\db\ActiveRecord
{

	public static $title="Операторы связи";

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prov_tel';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'cabinet_url', 'support_tel'], 'required'],
            [['comment'], 'string'],
            [['name', 'support_tel'], 'string', 'max' => 64],
            [['cabinet_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название оператора',
            'cabinet_url' => 'Ссылка на личный кабинет',
            'support_tel' => 'Телефон техподдержки',
            'comment' => 'Комментарий',
        ];
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrgPhones()
	{
		return $this->hasMany(OrgPhones::className(), ['prov_tel_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrgInet()
	{
		return $this->hasMany(OrgInet::className(), ['prov_tel_id' => 'id']);
	}

	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
