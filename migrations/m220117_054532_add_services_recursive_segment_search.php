<?php

use yii\db\Migration;

/**
 * Class m220117_054532_add_services_recursive_segment_search
 */
class m220117_054532_add_services_recursive_segment_search extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$sql=<<<SQL
    	set names utf8mb4;
DROP FUNCTION IF EXISTS getServiceSegment;
DROP PROCEDURE IF EXISTS getServiceSegment;
DELIMITER //
CREATE PROCEDURE getServiceSegment(IN itemId INT, OUT resultValue INT)
COMMENT 'Recursive search of NOT NULL segment_id value'
READS SQL DATA
BEGIN
  DECLARE parentId INT;
  SET max_sp_recursion_depth = 32;
  SELECT segment_id, parent_id FROM services WHERE id=itemId INTO resultValue,parentId;
  IF (resultValue IS NULL) and (NOT parentId IS NULL) THEN
    CALL getServiceSegment(parentId,resultValue);
  END IF;
END//

CREATE FUNCTION getServiceSegment(itemId INT) RETURNS INT DETERMINISTIC
BEGIN
    DECLARE res INT;
	CALL getServiceSegment(itemId, res);
    RETURN res;
END//
DELIMITER ;
SQL;
	
		$this->execute($sql);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$sql=<<<SQL
DROP FUNCTION IF EXISTS getServiceSegment;
DROP PROCEDURE IF EXISTS getServiceSegment;
SQL;
		$this->execute($sql);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220117_054532_add_services_recursive_segment_search cannot be reverted.\n";

        return false;
    }
    */
}
