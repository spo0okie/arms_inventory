<?php
namespace app\migrations;
use app\models\Techs;
use yii\data\SqlDataProvider;
use yii\db\Migration;
use yii\db\Query;

/**
 * Class m230224_081112_migrate_arms2techs
 */
class m230224_081112_migrate_arms2techs extends Migration
{
	
	private function dropForeignKeyIfExists($tableName,$keyName){
		$table = $this->db->getTableSchema($tableName);
		if (isset($table->foreignKeys[$keyName]))
			$this->dropForeignKey($keyName,$tableName);
	}
	
	
	private function TechFromArm($arm,$model_id){
		
		unset($arm['id']);
		unset($arm['model']);		//какое-то текстовое поле нахрен не нужно
		unset($arm['is_server']);	//это надо делать вычисляемым. что за объявление типа это сервер. есть сервисы - сервер
		
		if (empty($arm['model_id']))
			$arm['model_id']=$model_id;		//ну лучше как-то неправильно провести миграцию чем сфейлить
		
		$tech=new Techs();
		
		foreach (array_keys($arm) as $field)
			$tech->$field=$arm[$field];
		
		//print_r($arm);
		//print_r($tech);
		//exit;
		return $tech;
	}
	
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		$this->dropForeignKeyIfExists('comps','arms');
		$this->dropForeignKeyIfExists('techs','techs_ibfk_3');
		$this->dropForeignKeyIfExists('contracts_in_arms','contracts_in_arms_ibfk_1');
		$this->dropForeignKeyIfExists('lic_items_in_arms','lic_items_in_arms_ibfk_1');
		$this->dropForeignKeyIfExists('materials_usages','materials_usages_ibfk_2');
	
	
		$pcCode='pc';
		$pcQuery=new Query();
		$pc=$pcQuery->select('*')->from('tech_types')->where(['code'=>$pcCode])->one();
		//$pc=\app\models\TechTypes::find()->where(['code'=>'pc'])->one();
		if (!is_array($pc)) {
			echo "Creating PC tech_type\n";
			$this->execute(
				'insert into tech_types (code,name,prefix,comment,is_computer) values (:code,:name,:prefix,:comment,:is_computer)',[
				'code'=>$pcCode,
				'name'=>'ПК',
				'prefix'=>'ПК',
				'comment'=>'Персональный компьютер (создано автоматически при обновлении БД)',
				'is_computer'=>true,
			]);
			$pc=$pcQuery->select('*')->from('tech_types')->where(['code'=>$pcCode])->one();
			if (!is_array($pc)) {
				echo "Error creating PC tech_type!\n";
				return false;
			}
		}
		
		echo "PC type ID: ".$pc['id']."\n";
		
		$noNameVendorName='No Name';
		$noNameVendorQuery=new Query();
		$noNameVendor=$noNameVendorQuery->select('*')->from('manufacturers')->where(['name'=>$noNameVendorName])->one();
		if (!is_array($noNameVendor)) {
			echo "Creating \"No Name\" vendor\n";
			$this->execute(
				'insert into manufacturers (name,full_name,comment) values (:name,:full_name,:comment)',[
				'name'=>$noNameVendorName,
				'full_name'=>'Безымянные и не определяющиеся производители',
				'comment'=>'А как без этого',
			]);
			$noNameVendor=$noNameVendorQuery->select('*')->from('manufacturers')->where(['name'=>$noNameVendorName])->one();
			if (!is_array($noNameVendor)) {
				echo "Error creating \"No Name\" vendor!\n";
				return false;
			}
		}
	
		echo "NoName Vendor ID: ".$noNameVendor['id']."\n";
		
		
		$unknownPcName='Unknown';
		$unknownPcQuery=new Query();
		$unknownPc=$unknownPcQuery->select('*')->from('tech_models')->where(['name'=>$unknownPcName])->one();
		if (!is_array($unknownPc)) {
			echo "Creating \"unknown PC\" tech_model\n";
			$this->execute(
				'insert into tech_models (manufacturers_id,name,comment,type_id) values (:manufacturers_id,:name,:comment,:type_id)',[
				'manufacturers_id'=>$noNameVendor['id'],
				'name'=>$unknownPcName,
				'comment'=>'Автоматически созданная модель оборудования назначена всем АРМ, где модель не была указана при обновлении БД. Нужно проставить правильные модели оборудования и удалить эту.',
				'type_id'=>$pc['id'],
			]);
			$unknownPc=$unknownPcQuery->select('*')->from('tech_models')->where(['name'=>$unknownPcName])->one();
			if (!is_array($unknownPc)) {
				echo "Error creating \"unknown PC\" tech_model!\n";
				return false;
			}
		}
	
		echo "UnknownPC model ID: ".$unknownPc['id']."\n";
	
		$preCheckFail=false;
		$arms = new SqlDataProvider(['sql'=> 'SELECT * FROM arms ','pagination' => false,]);
		foreach ($arms->models as $model) {
			$tech=$this->TechFromArm($model,$unknownPc['id']);
			$tech->installed_pos='migrated_arm:'.$model['id'];
			$tech->validate();
			if (count($tech->errors)){
				echo $model['num'];
				print_r($tech->errors);
				$preCheckFail=true;
			}
		}
		if ($preCheckFail) exit();

		
		foreach ($arms->models as $model) {
			echo $model['num']."\n";
			
			$oldId=$model['id'];
			
			$tech=$this->TechFromArm($model,$unknownPc['id']);
			$tech->installed_pos='migrated_arm:'.$model['id'];
			$tech->save();
			$tech->refresh();
			$newId=$tech->id;
			
			$this->execute("update comps set arm_id=:new where arm_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update techs set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update lic_groups_in_arms set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update lic_items_in_arms set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update lic_keys_in_arms set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update materials_usages set techs_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update ports set techs_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update scans set techs_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			
			$contracts = new SqlDataProvider(['sql'=> "select * from contracts_in_arms where arms_id=$oldId",'pagination' => false,]);
			foreach ($contracts->models as $contract) {
				$this->execute('insert into contracts_in_techs (contracts_id,techs_id) values (:contract,:new)',[
					':contract'=>(int)$contract['contracts_id'],
					':new'=>$newId
				]);
			}
			$tech->save();
			echo "OK\n";
		}
		
		return true;
    }

    /**
     * {@inheritdoc}
	 * @noinspection SqlResolve
     */
    public function down()
    {
		$arms = new SqlDataProvider(['sql'=> 'SELECT * FROM techs where installed_pos like "migrated_arm:%"','pagination' => false,]);
		foreach ($arms->models as $model) {
			echo $model['num'].':'.$model['installed_pos']."\n";
		
			$oldId=$model['id'];

			$tokens=explode(':',$model['installed_pos']);
			$newId=(int)$tokens[1];
		
			$this->execute("update comps set arm_id=:new where arm_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update techs set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update lic_groups_in_arms set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update lic_items_in_arms set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update lic_keys_in_arms set arms_id=:new where arms_id=:old",[':old'=>$oldId,':new'=>$newId]);
			$this->execute("update materials_usages set techs_id=null where arms_id=:new",[':new'=>$newId]);
			$this->execute("update ports set techs_id=null where arms_id=:new",[':new'=>$newId]);
			$this->execute("update scans set techs_id=null where arms_id=:new",[':new'=>$newId]);
			$this->execute("delete from contracts_in_techs where techs_id=:old",[':old'=>$oldId]);
			$this->execute("delete from techs where id=:old",[':old'=>$oldId]);
			echo "OK\n";
		}
	
		return true;
    }

}
