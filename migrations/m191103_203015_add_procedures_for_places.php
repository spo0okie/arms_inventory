<?php

use yii\db\Migration;

/**
 * Class m191103_203015_add_procedures_for_places
 */
class m191103_203015_add_procedures_for_places extends Migration

{
	static $drop= <<<SQL
DROP PROCEDURE IF EXISTS getplacepath;
DROP FUNCTION IF EXISTS getplacepath;
DROP PROCEDURE IF EXISTS getplacetop;
DROP FUNCTION IF EXISTS getplacetop;
SQL;

	static $getPlacePathProc=<<<SQL
set names utf8mb4;
delimiter //
CREATE PROCEDURE getplacepath(IN place_id INT, OUT path TEXT CHARACTER SET utf8mb4)
COMMENT 'Recursive path build'
READS SQL DATA
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
SQL;
	
	static $getPlacePathFunc=<<<SQL
set names utf8mb4;
delimiter //
CREATE FUNCTION getplacepath(place_id INT) RETURNS TEXT CHARACTER SET utf8mb4 DETERMINISTIC
BEGIN
    DECLARE res TEXT CHARACTER SET utf8mb4;
    CALL getplacepath(place_id, res);
    RETURN res;
END//
SQL;
	
	static $getPlaceTopProc=<<<SQL
set names utf8mb4;
CREATE PROCEDURE getplacetop(IN place_id INT, OUT top INT)
COMMENT 'Recursive last parent search'
READS SQL DATA
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
END;
SQL;
	
	static $getPlaceTopFunc=<<<SQL
set names utf8mb4;
CREATE FUNCTION getplacetop(place_id INT) RETURNS INT DETERMINISTIC
BEGIN
    DECLARE res INT;
    CALL getplacetop(place_id, res);
    RETURN res;
END;
SQL;
	
	/**
     * {@inheritdoc}
     */
    public function up()
    {
		$this->execute(static::$drop);
		$this->execute(static::$getPlacePathProc);
		$this->execute(static::$getPlacePathFunc);
		$this->execute(static::$getPlaceTopProc);
		$this->execute(static::$getPlaceTopFunc);
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
		$this->execute(static::$drop);
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
