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
 * Контроллер для разработки, можно тут прокручивать код
 */
class DevController extends Controller
{
    /**
     * Вывести все типы объявленные в ArmsModel потомках
     */
    public function actionTypes(): void
    {
		$types=ModelHelper::getModelAtributesTypes();	
		print_r($types);
    }

    /**
     * Вывести все rules объявленные в ArmsModel потомках
     */
    public function actionRules(): void
    {
		print_r(ModelHelper::getModelAtributesRules());
    }

	/**
	 * Вывести все классы от ArmsModel
	 */
	public function actionModels() : void {
		$models = ModelHelper::getModelClasses();
		print_r($models);
	}
}
