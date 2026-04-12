<?php

namespace app\controllers;

use app\models\HistoryModel;
use http\Exception\InvalidArgumentException;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;


/**
 * HistoryController реализует отображение журнала изменений объектов.
 *
 * Позволяет просматривать историю изменений любой модели, реализующей
 * интерфейс HistoryModel, по идентификатору объекта.
 */
class HistoryController extends ArmsBaseController
{
	/**
	 * Возвращает список отключённых приёмочных тестов.
	 *
	 * Все тесты контроллера отключены ('*'): журнал истории работает только
	 * с реально существующими историческими данными в БД. Без предварительно
	 * сформированных записей в таблицах истории (master_id, изменённые атрибуты)
	 * тесты не имеют смысла. Для включения тестов необходимо реализовать
	 * фикстуры исторических данных для конкретных моделей HistoryModel.
	 *
	 * @return array список отключённых тестов (wildcard '*' — все)
	 */
	public function disabledTests(): array
	{
		return ['*'];
	}

	public function accessMap()
	{
		return [ArmsBaseController::PERM_VIEW=>['journal']];
	}
	
	
	/**
	 * Отображает журнал изменений для заданного объекта.
	 *
	 * Принимает FQCN класса модели-истории и идентификатор объекта-владельца
	 * (master_id). Проверяет существование класса и его принадлежность к
	 * HistoryModel. Строит ActiveDataProvider с сортировкой по убыванию id.
	 *
	 * GET-параметры:
	 * @param string $class Полное имя класса (FQCN) модели истории,
	 *                      например: app\models\history\CompsHistory.
	 *                      Класс должен реализовывать HistoryModel.
	 * @param int    $id    Идентификатор объекта-владельца (master_id),
	 *                      история которого запрашивается.
	 *
	 * @return mixed Рендер представления journal
	 * @throws \yii\web\NotFoundHttpException если класс не найден
	 * @throws \http\Exception\InvalidArgumentException если класс не является HistoryModel
	 */
    public function actionJournal(string $class,int $id)
    {
    	/** @var HistoryModel $class */
    	if (!class_exists($class))
			throw new NotFoundHttpException('The requested class does not exist.');
     
    	$instance=new $class();
    	if (!$instance instanceof HistoryModel) {
			throw new InvalidArgumentException('Incorrect class requested');
		}
    	
    	$dataProvider=new ActiveDataProvider([
			'query' => $class::find()
				->where(['master_id'=>$id])
				->orderBy(['id'=>SORT_DESC])
		]);
    	
    	$master=$instance->getHistoryMaster($id);
    	
    	return $this->render('journal',[
    		'dataProvider'=>$dataProvider,
			'class'=>$class,
			'instance'=>$instance,
			'master'=>$master
		]);
			
    }

}
