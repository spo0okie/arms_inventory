<?php
/**
 * Описание ошибок RESTHelper
 */

namespace app\helpers;


class RestHelperException extends \yii\base\Exception
{
	/**
	 * @var RestHelper Хелпер хранящий последние данные о запросе
	 */
	public $helper = null;
	
	
	/**
	 * Constructor.
	 * @param string          $message Сообщение об ошибке
	 * @param RestHelper|null $helper Хелпер бросивший ошибку
	 * @param string          $code Код ошибки
	 * @param \Throwable|null $previous The previous exception used for the exception chaining.
	 */
	public function __construct(string $message, $helper=null, $code = '', $previous = null)
	{
		parent::__construct($message, 0, $previous);
		$this->helper = $helper;
		$this->code = $code;
	}
	
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'Outgoing REST Request Exception';
	}
	
	/**
	 * @return string readable representation of exception
	 */
	public function __toString()
	{
		if (is_null($this->helper))
			return parent::__toString() . PHP_EOL . '(No helper provided)';
		else
			return parent::__toString() . PHP_EOL
			. 'Requested URL:' . $this->helper->request . PHP_EOL
			. 'Response headers:' . PHP_EOL . print_r($this->helper->responseHeaders,true) . PHP_EOL
			. 'Response:' . PHP_EOL . print_r($this->helper->response,true);
	}
}