<?php

namespace app\controllers;


use app\models\LicKeys;
use app\models\links\LicLinks;
use Throwable;
use yii\data\ArrayDataProvider;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * LicKeysController implements the CRUD actions for LicKeys model.
 */
class LicKeysController extends ArmsBaseController
{
	public $modelClass=LicKeys::class;

    /**
     * Displays a single LicKeys model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
			'linksData'=>new ArrayDataProvider([
				'allModels' => LicLinks::findForLic('keys',$id),
				'key'=>'id',
				'sort' => [
					'attributes'=> [
						'objName',
						'comment',
						'changedAt',
						'changedBy',
					],
					'defaultOrder' => [
						'objName' => SORT_ASC
					]
				],
				'pagination' => false,
			]),
		]);
    }
	
	/**
	 * Deletes an existing LicKeys model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	/** @var LicKeys $model */
    	$model=$this->findModel($id);
    	$lic_items_id=$model->lic_items_id;
        $model->delete();

	    return $this->redirect(['/lic-items/view', 'id' => $lic_items_id]);
    }
}
