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
	/**
	 * Отображает список IP-адресов с поиском и фильтрацией.
	 *
	 * Если результатов по фильтру не найдено и указан текстовый адрес (text_addr),
	 * выполняется попытка определить принадлежность адреса к существующей сети:
	 * адрес валидируется, вычисляется его сетевой адрес и, если сеть найдена,
	 * она отображается отдельным блоком (networkProvider).
	 *
	 * GET-параметры (queryParams): любые фильтры модели NetIpsSearch,
	 * в том числе NetIpsSearch[text_addr] — текстовый IP-адрес для поиска/определения сети.
	 *
	 * @return string
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
    
	/**
	 * Отображает inline-карточку IP-адреса (partial) по его текстовому представлению.
	 *
	 * GET-параметры:
	 * @param string $name  IP-адрес в формате x.x.x.x (обязательно).
	 *
	 * @return string
	 * @throws NotFoundHttpException если IP-адрес не найден в БД
	 */
	public function actionItemByName($name)
	{
		if (($model = NetIps::findOne(['text_addr' => $name])) !== null) {
			return $this->renderPartial('item', ['model' => $model, 'static_view' => true]);
		}
		throw new NotFoundHttpException('The requested page does not exist.');
	}

	/**
	 * Acceptance test data for ItemByName.
	 *
	 * Использует text_addr из getTestData()['full'], чтобы не зависеть от захардкоженного
	 * IP-адреса в БД. Ранее тест использовал '10.20.1.11', который мог отсутствовать
	 * в тестовом окружении; теперь IP гарантированно создаётся через factory.
	 */
	public function testItemByName(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['name' => $testData['full']->text_addr],
			'response' => 200,
		]];
	}

	public $modelClass=NetIps::class;

    
	
}
