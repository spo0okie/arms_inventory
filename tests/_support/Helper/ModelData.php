<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use app\helpers\StringHelper;

class ModelData extends \Codeception\Module
{
	/**
	 * Возвращает список атрибутов, которые надо заполнять в форме для модели
	 * @param \app\models\ArmsModel $model
	 * @return array
	 */
	public static function getFormAttributes($model)
	{
		$attributes=$model->attributes();
		foreach ($model->getLinksSchema() as $attribute => $schema) {
			// Если атрибут заканчивается на _ids, то это точно не junction_table
			if (!StringHelper::endsWith($attribute,'_ids')) continue;
			//проверяем обратную ссылку
			$reverseLink=$model->attributeReverseLink($attribute);
			//она должна тоже заканчиваться на _ids
			if (!$reverseLink) continue;
			if (!StringHelper::endsWith($reverseLink,'_ids')) continue;
			//это junction_table атрибут и его тоже надо в форме выводить
			codecept_debug($attribute.' is junction_table attribute with ' .$schema[0].'::'.$reverseLink);
			$attributes[]=$attribute;
		}
		return $attributes;
	}
	
	/**
	 * Заполняет поля формы значениями из модели
	 * @param $attrs
	 * @param $model
	 * @param $skip
	 * @return array
	 */
	public static function fillForm($attrs,$model,$skip=['id'])
	{
		$form=[];
		foreach ($attrs as $attribute) {
			if (in_array($attribute,$skip)) continue;
			$form[$attribute]=$model->$attribute;
		}
		return $form;
	}
	
	/**
	 * Рвет все реверсивные и many-2-many связи в форме, чтобы можно было удалить потом модель
	 * @param $form
	 * @return void
	 */
	public static function clearLinks($form) {
		foreach ($form as $name => $value) {
			if (str_ends_with($name,'_ids')) {  //все реверсивные и many-2-many связи
				$form[$name]=[];				//рвем
			}
		}
		return $form;
	}
	
	public function _beforeSuite($settings = array())
	{
		/*
		Перенесено в PagesAccessCest->routesProvider,
		так как он вызывается раньше этого метода
		*/
	}
	public function _afterSuite()
	{
		//if (static::$testsFailed) return;
		// После завершения тестов удаляем тестовую БД
		//Yii2::initFromFilename('test-web.php');
		//Database::dropYiiDb();
	}
}
