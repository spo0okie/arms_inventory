<?php

namespace app\modules\api;


use app\modules\api\controllers\BaseRestController;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\web\NotFoundHttpException;

/**
 * Rest module definition class
 */
class Rest extends Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\api\controllers';
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Yii::$app->user->enableSession = false;
        // custom initialization code goes here
    }
	
	
	public function createControllerByID($id)
	{
		$pos = strrpos($id, '/');
		if ($pos === false) {
			$prefix = '';
			$className = $id;
		} else {
			$prefix = substr($id, 0, $pos + 1);
			$className = substr($id, $pos + 1);
		}
		
		if ($this->isIncorrectClassNameOrPrefix($className, $prefix)) {
			return null;
		}
		
		//класс модели
		$modelClassName = preg_replace_callback('%-([a-z0-9_])%i', function ($matches) {
			return ucfirst($matches[1]);
		}, ucfirst($className));
		//класс контроллера
		$className=$modelClassName. 'Controller';
		$className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix) . $className, '\\');
		
		if (strpos($className, '-') !== false) return null;
		
		//тут у нас своя логика: если такого класса нет - мы проверяем что у нас есть нужная модель
		//и создаем для нее базовый (со стандартным функционалом) контроллер
		if (!class_exists($className)) {
			$modelClassName="app\\models\\$modelClassName";
			if (!class_exists($modelClassName)) throw new NotFoundHttpException("No suitable controller for $id");
			$controller = Yii::createObject(BaseRestController::class, [$id, $this]);
			$controller->modelClass = $modelClassName;
			return $controller;
		}
		
		if (is_subclass_of($className, 'yii\base\Controller')) {
			$controller = Yii::createObject($className, [$id, $this]);
			return get_class($controller) === $className ? $controller : null;
		} elseif (YII_DEBUG) {
			throw new InvalidConfigException('Controller class must extend from \\yii\\base\\Controller.');
		}
		
		return null;
	}
	
	
	/**
	 * Checks if class name or prefix is incorrect
	 *
	 * @param string $className
	 * @param string $prefix
	 * @return bool
	 */
	private function isIncorrectClassNameOrPrefix(string $className, string $prefix):bool
	{
		if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
			return true;
		}
		if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
			return true;
		}
		
		return false;
	}
}
