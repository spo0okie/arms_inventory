<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\CompsSearch;
use app\models\LicGroupsSearch;
use Yii;
use app\models\Soft;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;

/**
 * SoftController implements the CRUD actions for Soft model.
 */
class SoftController extends ArmsBaseController
{
	public $modelClass=Soft::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['select-update'],
		]);
	}
	
	public function disabledActions()
	{
		return ['item-by-name','ttip'];
	}
	
	/**
	 * Displays a single Soft model ttip.
	 * @param integer     $id
	 * @param string|null $hitlist
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip(int $id, string $hitlist=null)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'hitlist' => $hitlist
		]);
	}

	/**
	 * Displays a single Soft model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView(int $id)
	{
		$searchModel = new CompsSearch();
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(Yii::$app->request->queryParams,['CompsSearch'=>['linkedSoft_ids'=>$id]]));
		
		$licSearchModel = new LicGroupsSearch();
		$licProvider = $licSearchModel->search(ArrayHelper::recursiveOverride(Yii::$app->request->queryParams,['LicGroupsSearch'=>['soft_ids'=>$id]]));
		
		return $this->render('view', [
			'model' => $this->findModel($id),
			'searchModel'=>$searchModel,
			'dataProvider'=>$dataProvider,
			'licProvider'=>$licProvider,
		]);
	}
	
	
	/**
     * Creates a new Soft model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Soft();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->manufacturers_id=Yii::$app->request->get('manufacturers_id');
        $descr=Yii::$app->request->get('descr');
        $cut=is_object($model->manufacturer)?$model->manufacturer->cutManufacturer($descr):'';
        if ($cut) $descr=trim(substr($descr,$cut));
        $model->descr=$descr;

        $model->items=Yii::$app->request->get('items');


        return $this->render('create', [
            'model' => $model,
        ]);
    }
	
	
	/**
	 * Рисует форму с выбором к какому софту добавить строку обнаружения
	 * @param string   $name	Строка для добавления к элементам софта
	 * @param int|null $manufacturers_id	Ограничить софт производителем
	 * @return mixed
	 */
	public function actionSelectUpdate(string $name, int $manufacturers_id=null)
	{
		if (!is_null($manufacturers_id))
			//для случаев, если производитель определен, выводим его продукты (только названия самих продуктов)
			$items=\yii\helpers\ArrayHelper::map(Soft::fetchBy(['manufacturers_id'=>$manufacturers_id]),'id','descr');
		else
			//в ином случае выводим список всех продуктов с указанием производителя в названии
			$items= Soft::listItemsWithPublisher();
		
		
		return Yii::$app->request->isAjax?
			$this->renderAjax( '/soft/_search_by_name_to_update',
			[
				'addItems'=>$name,
				'items'=>$items,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('/soft/_search_by_name_to_update',
			[
				'addItems'=>$name,
				'items'=>$items,
			]);
	}
	
	/**
	 * Updates an existing TechModels model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUploads(int $id)
	{
		$model = $this->findModel($id);
		return $this->render('uploads', [
			'model' => $model,
		]);
	}
    
}
