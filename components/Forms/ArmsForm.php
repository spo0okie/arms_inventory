<?php


namespace app\components\Forms;


use app\components\Forms\assets\ArmsFormAsset;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/**
 * Class ArmsForm
 * @package app\components\Forms
 *
 * Расширяем стандартный ActiveForm для своих нужд.
 * Добавляем поддержку AJAX валидации и динамических плейсхолдеров
 *
 * @method ActiveField field($model, $attribute, $options = [])
 */
class ArmsForm extends ActiveForm
{
	
	//используем собственный класс для полей формы
	public $fieldClass = ActiveField::class;
	
	const PLACEHOLDERS_ATTR='ATTR_PLACEHOLDERS';
	
	//у нас валидация по умолчанию именно AJAX
	public $enableAjaxValidation = true;
	
	//валидация на стороне фронтенда отключаем, ибо я не хочу сопровождать код и на фронте и на бэке
	public $enableClientValidation = false;
	
	//к какой модели относится форма
	public $model;
	
	//к какому классу моделей относится форма
	public $modelClass;
	
	//добавляем загрузку нашего JS
	public function registerClientScript()
	{
		parent::registerClientScript();
		$view=$this->getView();
		ArmsFormAsset::register($view);
		$id = $this->options['id'];;
		$view->registerJs("jQuery('#$id').on('ajaxComplete', armsFormAjaxComplete);");
	}
	
	public function init()
	{
		parent::init();
		
		//если модель есть, а класса нет, вытаскиваем класс
		if (isset($this->model) && !isset($this->modelClass)) {
			$this->modelClass=get_class($this->model);
		}
		
		//прописываем URL валидации
		if (isset($this->modelClass) && !isset($this->validationUrl)) {
			$this->validationUrl=['/'.StringHelper::class2Id($this->modelClass).'/validate'];
			if (isset($this->model)) $this->validationUrl['id']=$this->model->id;
		}
		
		if (!$this->validationUrl) $this->enableAjaxValidation=false;
		
		if (isset($this->modelClass) && !isset($this->options['id'])) {
			$this->options['id']=StringHelper::class2Id($this->modelClass);
			if (isset($this->model)) $this->options['id'].='-'.$this->model->id;
			$this->options['id'].='-form';
		}
		
	}
	
	/**
	 * Добавляет к данным валидации значения плейсхолдеров для формы, которые могли измениться
	 * после изменения данных (динамических плейсхолдеров)
	 * @param       $model
	 * @param array $result
	 * @return array
	 */
	public static function prepareAttrPlaceholders($model,$result=[])
	{
		$models = [$model];
		$placeholders=[];

		/* @var $model ArmsModel */
		foreach ($models as $model) {
			foreach ($model->getDynamicPlaceholders() as $attribute => $placeholder) {
				$placeholders[Html::getInputId($model, $attribute)] = $placeholder;
			}
		}
		$result[static::PLACEHOLDERS_ATTR]=$placeholders;
		return $result;
	}
	
	/**
	 * @inheritdoc
	 */
	public static function validate($model, $attributes = null) {
		$data = parent::validate($model, $attributes);
		$data = static::prepareAttrPlaceholders($model,$data);
		return $data;
	}
}