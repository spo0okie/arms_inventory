<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Handles adding columns to table `{{%places}}`.
 */
class m221024_153826_add_comment_column_to_places_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table = $this->db->getTableSchema('places');
		if (!isset($table->columns['comment']))
			$this->addColumn('places', 'comment', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumn('places', 'comment');
    }
}
