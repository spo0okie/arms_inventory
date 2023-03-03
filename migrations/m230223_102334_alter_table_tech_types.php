<?php

use yii\db\Migration;

/**
 * Class m230223_102334_alter_table_tech_types
 */
class m230223_102334_alter_table_tech_types extends Migration
{
	function mb_str_pad( $input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT)
	{
		$diff = strlen( $input ) - mb_strlen( $input );
		return str_pad( $input, $pad_length + $diff, $pad_string, $pad_type );
	}
	
	function addColumnIfNotExist($table,$column,$type,$index=false)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (!isset($tableSchema->columns[$column])) {
			$this->addColumn($table,$column,$type);
			if ($index) $this->createIndex("idx-$table-$column",$table,$column);
			
		}
	}
	
	function dropColumnIfExist($table,$column)
	{
		$tableSchema = $this->db->getTableSchema($table);
		if (isset($tableSchema->columns[$column])) {
			$this->dropColumn($table,$column);
		}
	}
	
	/**
     * {@inheritdoc}
     */
    public function safeUp()
    {
	
	
		$this->addColumnIfNotExist('tech_types','is_computer',$this->boolean()->defaultValue(0));
		$this->addColumnIfNotExist('tech_types','is_phone',	$this->boolean()->defaultValue(0));
		$this->addColumnIfNotExist('tech_types','is_ups',	$this->boolean()->defaultValue(0));
		$this->addColumnIfNotExist('tech_types','is_display',$this->boolean()->defaultValue(0));
		
		foreach(\app\models\TechTypes::find()->all() as $item) {
			/**
			 * @var $item \app\models\TechTypes
			 */
			if (array_search($item->code,['laptop','aio_pc','pc','srv'])!==false) {
				$item->is_computer=true;
				$item->prefix=null;
			}
			if (array_search($item->code,['voip_phone','dect_phone','phone'])!==false)
				$item->is_phone=true;
			if (array_search($item->code,['display'])!==false)
				$item->is_display=true;
			if (array_search($item->code,['ups'])!==false)
				$item->is_ups=true;
			echo $this->mb_str_pad($item->name.' '.$item->code.': ',45).
				($item->is_computer?'computer ':'').
				($item->is_display?'display ':'').
				($item->is_ups?'ups ':'').
				($item->is_phone?'phone ':'')."\n";
			$item->save();
		}
		
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumnIfExist('tech_types','is_computer');
		$this->dropColumnIfExist('tech_types','is_phone');
		$this->dropColumnIfExist('tech_types','is_ups');
		$this->dropColumnIfExist('tech_types','is_display');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230223_102334_alter_table_tech_types cannot be reverted.\n";

        return false;
    }
    */
}
