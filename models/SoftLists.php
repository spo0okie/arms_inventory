<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "soft_lists".
 *
 * @property int $id ID
 * @property string $name Служебное имя
 * @property string $descr Описание
 * @property string $comment Комментарий
 * @property Soft[] $soft
 */
class SoftLists extends \yii\db\ActiveRecord
{
	
	public static $title = 'Список ПО';
	public static $titles = 'Списки ПО';
	
	protected static $soft_in_lists = null;

    protected static $names_list = null;
    protected static $AGREED_LIST_NAME = 'soft_agreed';
    protected static $AGREED_LIST_ID  = null;
	protected static $IGNORED_LIST_NAME = 'soft_ignore';
	protected static $IGNORED_LIST_ID = null;
	protected static $FREE_LIST_NAME = 'soft_free';
	protected static $FREE_LIST_ID = null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'soft_lists';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'descr', 'comment'], 'required'],
            [['comment'], 'string'],
            [['name'], 'string', 'max' => 32],
            [['descr'], 'string', 'max' => 256],
            [['name'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Служебное имя',
            'descr' => 'Описание',
			'comment' => 'Комментарий',
			'created_at' => 'Дата добавления',
        ];
    }

    /**
     * Возвращает набор списков, в которых находится ПО
     */
    public function getSoft()
    {
        return $this->hasMany(Soft::className(), ['id' => 'soft_id'])
            ->viaTable('{{%soft_in_lists}}', ['list_id' => 'id']);
    }


    /**
     * Возвращает массив ключ=>значение из всех записей таблицы
     * @param string $keyField поле - ключ
     * @param string $valueField поле - значение
     * @param bool $asArray
     * @return array
     */
    public static function listAll($keyField = 'id', $valueField = 'descr', $asArray = true)
    {
        $query = static::find();
        if ($asArray) {
            $query->select([$keyField, $valueField])->asArray();
        }

        return \yii\helpers\ArrayHelper::map($query->all(), $keyField, $valueField);
    }

    /**
     * возвращает массив id=>name и кэширует результат
     * @return array
     */
    public static function getNames()
    {
        if (is_null(static::$names_list)) {
            static::$names_list=static::listAll('id','name');
        }
        return static::$names_list;
    }

    /**
     * Возвращает ID списка согласованного ПО
     */
    public static function getAgreedListId() {
        if (is_null(static::$AGREED_LIST_ID)) {
            static::$AGREED_LIST_ID=array_search(static::$AGREED_LIST_NAME,static::getNames());
        }
        return static::$AGREED_LIST_ID;
    }

	/**
	 * Возвращает ID списка игнорируемого ПО
	 */
	public static function getIgnoredListId() {
		if (is_null(static::$IGNORED_LIST_ID)) {
			static::$IGNORED_LIST_ID=array_search(static::$IGNORED_LIST_NAME,static::getNames());
		}
		return static::$IGNORED_LIST_ID;
	}

	/**
	 * Возвращает ID списка игнорируемого ПО
	 */
	public static function getFreeListId() {
		if (is_null(static::$FREE_LIST_ID)) {
			static::$FREE_LIST_ID=array_search(static::$FREE_LIST_NAME,static::getNames());
		}
		return static::$FREE_LIST_ID;
	}

	/**
	 * Возвращает ID списка игнорируемого ПО
	 */
	public static function getSoftInLists() {
		if (is_null(static::$soft_in_lists)) {
			$query=new \yii\db\Query();
			static::$soft_in_lists=$query->select('*')->from('soft_in_lists')->all();
		}
		return static::$soft_in_lists;
	}

	public static function getSoftLists($soft_id) {
		$lists=[];
		//var_dump(static::getSoftInLists() );
		foreach (static::getSoftInLists() as $items) {
			if ($items['soft_id']==$soft_id) $lists[]=$items['list_id'];
		}
		return $lists;
	}


}
