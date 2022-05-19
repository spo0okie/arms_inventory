<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

/*
 * Выводит инпут для поиска модели по полю
 */


namespace app\components;

use yii\base\Widget;

class SearchFieldWidget extends Widget
{
	/**
	 * @var $model string класс модели
	 */
	public $model;

	/**
	 * @var $model string поле модели
	 */
	public $field;

	/**
	 * @var $model запрос перед инпутом
	 */
	public $label;

	public function run()
	{
		return $this->render('search/field', [
			'model' => $this->model,
			'field' => $this->field,
			'label' => $this->label,
		]);
	}
}