<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\helpers\ModelHelper;
use yii\console\Controller;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DevController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionTypes()
    {
		$types=[];
        $classes=ModelHelper::getModelClasses();
		foreach ($classes as $class) {
			$model=new $class();
			$attribureData=$model->attributeData();
			foreach ($attribureData as $attr=>$data) {
				if (isset($data['type'])) {
					$type=$data['type'];
					if (!isset($types[$type])) {
						$types[$type]=['c'=>1,'path'=>$class.'->'.$attr];
					} else
						$types[$type]['c']++;					
				}
			}
		}
		print_r($types);
    }
}
