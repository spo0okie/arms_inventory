<?php
/**
 * Имя сотрудника и его производные (разбор ФИО из Ename).
 *
 * Вынесено в отдельный трейт (а не в UsersModelCalcFieldsTrait), чтобы
 * подключаться и к журнальной записи UsersHistory: журнал не умеет в связи
 * (techs), которые нужны единственному calc-полю effectivePhone, но имя
 * журнальной записи нужно обязательно - по нему рисуются ссылки на сотрудника
 * в карточках изменений (LinkObjectWidget::$name).
 */

namespace app\models\traits;

trait UsersModelNamesTrait
{
	private $tokens_cache=null; //имя разбитое на токены

	/**
	 * Полное имя, разобранное на токены (Фамилия Имя Отчество)
	 * @return array
	 */
	public function getTokens() {
		if (!is_null($this->tokens_cache)) return $this->tokens_cache;
		return $this->tokens_cache=explode(' ',$this->Ename??'');
	}

	/**
	 * Get Last Name
	 * @return string
	 */
	public function getLn() {
		if (!count($tokens=$this->getTokens())) return '';
		return $tokens[0];
	}

	/**
	 * Get First Name
	 * @return string
	 */
	public function getFn() {
		if (count($tokens=$this->getTokens())<2) return '';
		return $tokens[1];
	}

	/**
	 * Get Middle Name
	 * @return string
	 */
	public function getMn() {
		if (count($tokens=$this->getTokens())<3) return '';
		return $tokens[2];
	}

	/**
	 * Сокращенные И.О.
	 * @return string
	 */
	public function getShortName() {
		if (($count=count($tokens=$this->getTokens()))<2) return $this->Ename;
		for ($i=1;$i<$count;$i++) {
			$tokens[$i]=mb_substr($tokens[$i],0,1).'.';
		}
		return implode(' ',$tokens);
	}

	public function getName() {
		return $this->Ename;
	}

	public function getFullName() {
		return $this->Ename;
	}
}
