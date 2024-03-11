<?php

namespace app\controllers;

use Yii;
use app\models\NetIps;
use app\models\NetIpsSearch;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;


/**
 * NetIpsController implements the CRUD actions for NetIps model.
 */
class NetIpsController extends ArmsBaseController
{

	public $modelClass=NetIps::class;

    /**
     * Lists all NetIps models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new NetIpsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
	
		$networkProvider=null;
        if (!$dataProvider->totalCount && ($ip_addr=$searchModel->text_addr)) {
			$ip=new NetIps(['text_addr'=>$ip_addr]);
			if ($ip->validate()) { //если тут валидный адрес, то можно подобрать сетку
				$ip->beforeSave(true);
				if (is_object($ip->network)) {
					$networkProvider= new ArrayDataProvider([
						'allModels'=>[$ip->network],
						'pagination'=>false,
					]);
				}
			}
		} /**/
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
			'networkProvider' => $networkProvider,
        ]);
    }
    
	public function actionItemByName($name)
	{
		if (($model = NetIps::findOne(['text_addr' => $name])) !== null) {
			return $this->renderPartial('item', ['model' => $model, 'static_view' => true]);
		}
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
}
