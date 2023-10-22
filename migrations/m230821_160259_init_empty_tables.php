<?php
namespace app\migrations;
use yii\db\Migration;
use yii\db\Query;

/**
 * Class m230821_160259_init_empty_tables
 */
class m230821_160259_init_empty_tables extends Migration
{
	
	public $initialDocStates=[
		['state_required','Потребность','Счет на руках, потребность очевидна, но еще до подачи счета на согласование'],
		['state_paywait','На согласовании','Сформирована СЗ на согласование счета. Ожидаем решения'],
		['state_paywait_confirmed','Согласовано','Счет согласован и ожидает оплаты/предоплаты финансовым отделом'],
		['state_payed_partial','Предоплачено',"После поступления частичной предоплаты на расчетный счет поставщика\nУказывает на необходимость получить услуги или товары у поставщика работающего по схеме частичной предоплаты"],
		['state_paywait_full','Ожидает оплаты',"Ожидает полной оплаты\n- после получения при работе по частичной предоплате\n- или до получения при работе по полной предоплате"],
		['state_payed','Оплачено 100%','После поступления 100% стоимости на расчетный счет поставщика'],
		['state_revoked','Отказано','Не прошел процедуру согласования, был отклонен по каким-то иным причинам'],
		['state_fail','Сторнировано','Счет потерял актуальность, необходимо запросить новый'],
	];
	
	public $initialTechStates=[
		['state_issued','На руках',0,'Выдано пользователю на руки.'],
		['state_confirmed','Согл',0,'Приобретение согласовано. Формируются заявки / счета / запросы.'],
		['state_in_supply_service','Снабж.',0,'В службе снабжения: все документы переданы в службу снабжения, ожидаем приобретения и доставки к месту потребности оборудования / ПО.'],
		['state_in_warehouse','Склад',0,'На складе. В настоящий момент не установлен, но имеется в наличии'],
		['state_operating','ОК',0,'Находится в работе по месту установки.'],
		['state_malfunction','Замеч.',0,'К работе оборудования имеются замечания, необходимо устранить.'],
		['state_broken','Сломан',0,'Полностью не работоспособен. Требуется ремонт или списание.'],
		['state_decommisioned','Списано',1,'Выведено из эксплуатации.'],
	];
	
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
		$query=new Query();
		$docState=$query->select('*')->from('contracts_states')->one();
		if (!is_array($docState)) {
			foreach ($this->initialDocStates as $s) $this->execute(
				'insert into contracts_states (code,name,descr) values (:code,:name,:descr)',[
				'code'=>$s[0],
				'name'=>$s[1],
				'descr'=>$s[2],
			]);
		}
		
		$docState=$query->select('*')->from('tech_states')->one();
		if (!is_array($docState)) {
			foreach ($this->initialTechStates as $s) $this->execute(
				'insert into tech_states (code,name,archived,descr) values (:code,:name,:archived,:descr)',[
				'code'=>$s[0],
				'name'=>$s[1],
				'descr'=>$s[3],
				'archived'=>$s[2],
			]);
		}
	
		$this->alterColumn('users','Login',$this->string(32));
		
		$this->addColumnIfNotExist('manufacturers_dict','updated_at',$this->timestamp());
		$this->addColumnIfNotExist('manufacturers_dict','updated_by',$this->string(32));
		$this->dropForeignKey('manufacturers','manufacturers_dict');
	
		$this->renameColumn('manufacturers','created_at','updated_at');
		$this->addColumnIfNotExist('manufacturers','updated_by',$this->string(32));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->alterColumn('users','Login',$this->string(255));
	
		$this->dropColumnIfExist('manufacturers_dict','updated_at');
		$this->dropColumnIfExist('manufacturers_dict','updated_by');
	
		$this->renameColumn('manufacturers','updated_at','created_at');
		$this->dropColumnIfExist('manufacturers','updated_by');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230821_160259_init_empty_tables cannot be reverted.\n";

        return false;
    }
    */
}
