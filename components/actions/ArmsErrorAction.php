<?php


namespace app\components\actions;

use Yii;
use yii\web\ErrorAction;

/**
 *
 * @property-read mixed $viewRenderParams
 */
class ArmsErrorAction extends ErrorAction
{
	
	protected function renderHtmlResponse(): string
	{
		//это как в родителе
		$view=$this->view ?: $this->id;

		//ищем что ща ошибку обрабатываем
		if (($exception = Yii::$app->errorHandler->exception) !== null) {
			//ищем код
			$code = $exception->statusCode ?? $exception->getCode();
			//для кода составляем кастомную страничку
			$viewPath = Yii::$app->controller->getViewPath() . '/error/' . $code . '.php';
			//если она есть - то ее и будем отображать
			if (is_file($viewPath)) $view='error/'.$code;
		}

		return $this->controller->render($view, $this->getViewRenderParams());
	}
}