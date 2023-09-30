<?php

use yii\db\Migration;

/**
 * Class m191103_203015_add_procedures_for_places
 */
class m191103_203015_add_procedures_for_places extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
    	$sql=<<<SQL
    	set names utf8mb4;
delimiter //
DROP PROCEDURE IF EXISTS getplacepath//
CREATE PROCEDURE getplacepath(IN place_id INT, OUT path TEXT CHARACTER SET utf8mb4)
BEGIN
    DECLARE placename VARCHAR(20) CHARACTER SET utf8mb4;
    DECLARE temppath TEXT CHARACTER SET utf8mb4;
    DECLARE tempparent INT;
    SET max_sp_recursion_depth = 32;
    SELECT short, parent_id FROM places WHERE id=place_id INTO placename, tempparent;
    IF tempparent IS NULL
    THEN
        SET path = placename;
    ELSE
        CALL getplacepath(tempparent, temppath);
        SET path = CONCAT(temppath, '/', placename);
    END IF;
END//

DROP FUNCTION IF EXISTS getplacepath//
CREATE FUNCTION getplacepath(place_id INT) RETURNS TEXT CHARACTER SET utf8mb4 DETERMINISTIC
BEGIN
    DECLARE res TEXT CHARACTER SET utf8mb4;
    CALL getplacepath(place_id, res);
    RETURN res;
END//


DROP PROCEDURE IF EXISTS getplacetop//
CREATE PROCEDURE getplacetop(IN place_id INT, OUT top INT)
BEGIN
    DECLARE tempparent INT;
    SET max_sp_recursion_depth = 32;
    SELECT parent_id FROM places WHERE id=place_id INTO tempparent;
    IF tempparent IS NULL
    THEN
        SET top = place_id;
    ELSE
        CALL getplacetop(tempparent, top);
    END IF;
END//


DROP FUNCTION IF EXISTS getplacetop//
CREATE FUNCTION getplacetop(place_id INT) RETURNS INT DETERMINISTIC
BEGIN
    DECLARE res INT;
    CALL getplacetop(place_id, res);
    RETURN res;
END//
delimiter ;
SQL;

		$this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$sql=<<<SQL
			DROP FUNCTION IF EXISTS getplacepath;
    		DROP PROCEDURE IF EXISTS getplacepath;
			DROP FUNCTION IF EXISTS getplacetop;
			DROP PROCEDURE IF EXISTS getplacetop;
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
        echo "m191103_203015_add_procedures_for_places cannot be reverted.\n";

        return false;
    }
    */
}
