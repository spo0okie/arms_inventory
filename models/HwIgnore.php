<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "hw_ignore".
 *
 * @property int $id ID
 * @property string $fingerprint Отпечаток 
 * @property string $comment Комментарий
 */
class HwIgnore extends ArmsModel
{

    private static $all_items=null;
	public static $title='Игнорируемое оборудование';
	public static $titles='Игнорируемое оборудование';
	
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hw_ignore';
	}
	
	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['fingerprint', 'comment'], 'required'],
			[['fingerprint', 'comment'], 'string', 'max' => 255],
		];
	}
	
	/**
	 * @inheritdoc
	 */
	public function attributeData()
	{
		return [
			'fingerprint' => 'Отпечаток ',
			'comment' => 'Пояснение',
		];
	}
	
	public function getName()
	{
		return $this->comment;
	}
	
    public static function fetchAll(){
        if (is_null(static::$all_items)) {
            $tmp=static::find()->all();
            static::$all_items=[];
            foreach ($tmp as $item) static::$all_items[$item->id]=$item;
        }
        return static::$all_items;
    }

    public static function fetchItem($id){
        return isset(static::fetchAll()[$id])?
            static::fetchAll()[$id]
            :
            null;
    }

    public static function fetchItems($ids){
        $tmp=[];
        foreach ($ids as $id) $tmp[$id]=static::fetchItem($id);
        return $tmp;
    }

    public static function exists($fingerprint){
        foreach (static::fetchAll() as $item) if (preg_match('/'.$item->fingerprint.'/ui',$fingerprint)) return true;
        return false;
    }

 
}
