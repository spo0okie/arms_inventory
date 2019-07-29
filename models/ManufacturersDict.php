<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "manufacturers_dict".
 *
 * @property int $id
 * @property string $word Вариант написания
 * @property int $manufacturers_id Производитель
 *
 * @property Manufacturers $manufacturers
 */
class ManufacturersDict extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'manufacturers_dict';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['word', 'manufacturers_id'], 'required'],
            [['manufacturers_id'], 'integer'],
            [['word'], 'string', 'max' => 255],
            [['word'], 'unique'],
            [['manufacturers_id'], 'exist', 'skipOnError' => true, 'targetClass' => Manufacturers::className(), 'targetAttribute' => ['manufacturers_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'word' => 'Вариант написания',
            'manufacturers_id' => 'Производитель',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManufacturers()
    {
        return $this->hasOne(Manufacturers::className(), ['id' => 'manufacturers_id']);
    }

    public function beforeSave($insert) {
        //нам все слова нужны в нижнем регистре чтобы искать регистронезависимо
        $this->word = mb_strtolower($this->word);
        return parent::beforeSave($insert);
    }

    /**
     * Возвращает ID производителя по написанию или NULL если не найдет
     * @param string $word написание производителя
     * @return integer ID
     * @throws \Exception
     * @throws \Throwable
     */
    public static function fetchManufacturer($word){
        $item=ManufacturersDict::getDb()->cache(function ($db)use($word) {
            return ManufacturersDict::find()->where(['word' => mb_strtolower($word,'utf-8')])->one();
        },Manufacturers::$CACHE_TIME);
        if (is_null($item)) return null;
        //error_log('got word '.$word.' is equal to '.$item['word']);
        return $item['manufacturers_id'];
    }

}
