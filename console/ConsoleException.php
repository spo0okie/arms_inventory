<?php

namespace app\console;

class ConsoleException extends \yii\base\Exception
{
	/**
	 * @var $details array Массив деталей ошибки вида ['param'=>'status'];
	 */
	public $details = [];
	
	
	/**
	 * Constructor.
	 * @param string			$message Сообщение об ошибке
	 * @param array				$details Детали ошибки
	 * @param string			$code Код ошибки
	 * @param \Throwable|null $previous The previous exception used for the exception chaining.
	 */
	public function __construct(string $message, $details=[], $code = '', $previous = null)
	{
		parent::__construct($message, 0, $previous);
		$this->details = $details;
		$this->code = $code;
	}
	
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'Inventory Console Error';
	}
	
	/**
	 * @return string readable representation of exception
	 */
	public function __toString()
	{
		$details=[];
		foreach ($this->details as $param=>$value) {
			$details[]=$param.': '.(is_array($value)||is_object($value))?print_r($value,true):$value;
		}
		$detailedMessage=count($details)?PHP_EOL.implode(PHP_EOL,$details):'';
		return parent::__toString() . $detailedMessage;
	}
}