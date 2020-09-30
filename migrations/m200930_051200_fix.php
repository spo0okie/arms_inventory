<?php

use yii\db\Migration;

/**
 * Class m200930_051200_fix
 */
class m200930_051200_fix extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$arms= \app\models\Arms::find()->all();
		foreach ($arms as $arm) {
			echo "checking arm ".$arm->id."\n";
			if (count($arm->comps)) {
				//есть привязанные ОС
				
				if (empty($arm->comp_id)) {
					//а основной нет
					$arm->comp_id=$arm->comps[0]->id;
					$arm->save();
				} else {
					//основная есть, проверяем что она корректная (одна из привязанных ОС)
					$isCorrect=false;
					foreach ($arm->comps as $comp) {
						if ($comp->id == $arm->comp_id) $isCorrect=true;
					}
					
					//если некорректная, то также как в случае если ее нет
					if (!$isCorrect) {
						$arm->comp_id=$arm->comps[0]->id;
						$arm->save();
					}
				}
				
			} else {
				//нет
				
				
				if (!is_null($arm->comp_id)) {
					//а основая ОС есть
					$arm->comp_id=null;
					$arm->save();
				}
			}
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200930_051200_fix cannot be reverted.\n";

        return false;
    }
    */
}
