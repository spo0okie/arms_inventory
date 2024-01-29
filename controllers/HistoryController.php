<?php

namespace app\controllers;

use app\models\HistoryModel;
use http\Exception\InvalidArgumentException;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;


/**
 * UiTablesColsController implements the CRUD actions for UiTablesCols model.
 */
class HistoryController extends ArmsBaseController
{

	public function accessMap()
	{
		return [ArmsBaseController::PERM_VIEW=>['journal']];
	}
	
	
	/**
	 * @param string $class
	 * @param int    $id
	 * @return mixed
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
