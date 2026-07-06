<?php


namespace app\components\Forms;


use app\components\Forms\assets\ArmsFormAsset;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
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

	public function init(): void
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

		/* @var $model \app\models\base\ArmsModel */
		foreach ($models as $model) {
			foreach ($model->getDynamicPlaceholders() as $attribute => $placeholder) {
				$placeholders[Html::getInputId($model, $attribute)] = $placeholder;
			}
		}
		$result[static::PLACEHOLDERS_ATTR]=$placeholders;
		return $result;
	}

	/**
	 * Перед закрытием формы добавляем поле комментария к изменению (issue #205)
	 * для моделей, которые ведут историю. Поле кладём внутрь формы, чтобы оно
	 * отправлялось вместе с ней, а маленький скрипт переносит его к кнопке
	 * сохранения (при неудаче поле просто останется внизу формы — не критично).
	 * @return string
	 */
	public function run()
	{
		//ActiveForm буферизует содержимое формы (ob_start в init) и оборачивает его
		//в <form> только в parent::run(). Поэтому поле надо не приклеивать к результату,
		//а echo-нуть в ещё открытый буфер — тогда оно окажется ВНУТРИ формы.
		if (is_object($this->model) && $this->model->getHistoryClass()) {
			echo $this->renderHistoryCommentField();
		}
		return parent::run();
	}

	/**
	 * Рендер поля "Пояснение к изменению" (транзиентный атрибут historyComment).
	 * Используем штатный ActiveField + textAutoresize, как и остальные поля форм,
	 * чтобы не плодить сырой HTML.
	 * @return string
	 */
	protected function renderHistoryCommentField()
	{
		$blockId=Html::getInputId($this->model,'historyComment').'-block';

		$field=(string)$this->field($this->model,'historyComment',[
			'options'=>['id'=>$blockId,'class'=>'form-group arms-history-comment mb-3'],
		])->textAutoresize(['rows'=>2])->label('Пояснение к изменению')->hint('Если в журнале изменений нужно отразить комментарий к этой правке/изменению, то введите свой комментарий тут');

		//переносим блок к кнопке сохранения (без отдельного JS-файла, чтобы не ловить
		//проблемы с публикацией ассетов). Ошибки глушим — деградация не критична.
		$formId=$this->options['id'];
		$script=Html::script(
			"try{".
			"var f=document.getElementById(".json_encode($formId).");".
			"var b=document.getElementById(".json_encode($blockId).");".
			"var s=f&&f.querySelector('button[type=submit],input[type=submit]');".
			"if(f&&b&&s){var g=s.closest('.form-group')||s;g.parentNode.insertBefore(b,g);}".
			"}catch(e){}"
		);

		return $field.$script;
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
