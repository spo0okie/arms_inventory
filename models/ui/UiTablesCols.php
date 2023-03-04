<?php


namespace app\models\ui;

/**
 * Class UiTablesCols
 * @package models\ui
 *
 * @property string $table
 * @property string $column
 * @property string $value
 * @property integer $user_id
 */
class UiTablesCols extends \app\models\ArmsModel
{
	
	private static $cache=[];
	
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'ui_tables_cols';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['table'], 'string', 'max' => 64],
			[['column'], 'string', 'max' => 32],
			[['user_id'],'integer'],
			[['value'], 'string', 'max' => 255],
		];
	}
	
	private static function cacheIndex($table,$user_id) {
		return $user_id.':'.$table;
	}
	
	public static function cacheTableData($table,$user_id) {
		$index=static::cacheIndex($table,$user_id);
		if (isset(static::$cache[$index])) return;
		
		static::$cache[$index]=[];
		
		$data=static::find()->where([
			'table'=>$table,
			'user_id'=>$user_id
		])->all();
		
		foreach ($data as $col) {
			static::$cache[$index][$col['column']]=$col['value'];
		}
	}
	
	public static function fetchColWidth($table,$column,$user_id=null) {
		if (is_null($user_id)) $user_id=\Yii::$app->user->id;
		$index=static::cacheIndex($table,$user_id);
		static::cacheTableData($table,$user_id);
		
		if (isset(static::$cache[$index][$column])) return static::$cache[$index][$column];
		
		return null;
	}
	
}