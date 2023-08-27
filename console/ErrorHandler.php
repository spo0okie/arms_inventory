<?php
 namespace app\console;
 
 use app\helpers\RestHelperException;
 use yii\helpers\Console;


 /**
  *
  * Class ErrorHandler
  * Модифицировал оригинальный, чтобы обрабатывать свои ошибки кастомно
  * https://github.com/yiisoft/yii2/blob/master/framework/console/ErrorHandler.php
  * @package app\console
  */
 
 class ErrorHandler extends \yii\console\ErrorHandler {
 	protected function renderException($exception)
	{
		if ($exception instanceof RestHelperException) {
			// от сих
			$previous = $exception->getPrevious();
			$message = $this->formatMessage("Exception ({$exception->getName()})");
			$message .= $this->formatMessage(" '" . get_class($exception) . "'", [Console::BOLD, Console::FG_BLUE])
				. ' with message ' . $this->formatMessage("'{$exception->getMessage()}'", [Console::BOLD])
				. "\n\nin " . dirname($exception->getFile()) . DIRECTORY_SEPARATOR . $this->formatMessage(basename($exception->getFile()), [Console::BOLD])
				. ':' . $this->formatMessage($exception->getLine(), [Console::BOLD, Console::FG_YELLOW]) . "\n";
			
			if (!empty($exception->helper)) {
				$message .= "\n" .
					$this->formatMessage("Requested URL:\n", [Console::BOLD]) .
					$exception->helper->request;
				$message .= "\n" .
					$this->formatMessage("Response headers:", [Console::BOLD]) .' '.
					print_r($exception->helper->responseHeaders, true);
				$message .= "\n" .
					$this->formatMessage("Response:\n", [Console::BOLD]) .
					print_r($exception->helper->response, true);
			}
			
			//до сих обработка кастомного прерывания, с форматированием ошибки
			//дальше оригинальный код рендера, просто вставленный в IF
			
			if ($previous === null) {
				$message .= "\n" . $this->formatMessage("Stack trace:\n", [Console::BOLD]) . $exception->getTraceAsString();
			}
			if (PHP_SAPI === 'cli') {
				Console::stderr($message . "\n");
			} else {
				echo $message . "\n";
			}
			if (YII_DEBUG && $previous !== null) {
				$causedBy = $this->formatMessage('Caused by: ', [Console::BOLD]);
				if (PHP_SAPI === 'cli') {
					Console::stderr($causedBy);
				} else {
					echo $causedBy;
				}
				$this->renderException($previous);
			}
			
		} else {
			parent::renderException($exception);
		}
	}
 }