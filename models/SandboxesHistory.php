<?php

namespace app\models;

use app\models\traits\CompsModelCalcFieldsTrait;

/**
 * This is the model class for table "maintenance_reqs_history".
 *
 * @property int $id
 * @property int|null $master_id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $links
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string sname
 */
class SandboxesHistory extends HistoryModel
{
	use CompsModelCalcFieldsTrait;
	
	public static $title='Изменения Окружения/Песочницы';
	public static $titles='Изменения Окружений/Песочниц';
	
	public $masterClass=Sandboxes::class;
	
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sandboxes_history';
    }
	
}