<?php

namespace app\controllers;

use app\models\AcesSearch;
use app\models\NetIps;
use app\models\Networks;
use app\models\NetworksSearch;
use Yii;
use yii\web\NotFoundHttpException;


/**
 * NetworksController implements the CRUD actions for Networks model.
 */
class NetworksController extends ArmsBaseController
{
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['incoming-connections-list','ipam'],
		]);
	}
	
	/**
	 * Отображает inline-карточку сети (partial) по её текстовому адресу в нотации CIDR.
	 *
	 * GET-параметры:
	 * @param string $name  Адрес сети в формате x.x.x.x/mask (обязательно), например: '10.20.1.0/24'.
	 *
	 * @return string
	 * @throws NotFoundHttpException если сеть не найдена в БД
	 */
	public function actionItemByName($name)
	{
		if (($model = Networks::findOne(['text_addr' => $name])) !== null) {
			return $this->renderPartial('item', ['model' => $model, 'static_view' => true]);
		}
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
	/**
	 * Acceptance test data for ItemByName.
	 *
	 * Использует text_addr из getTestData()['full'], чтобы не зависеть от захардкоженного
	 * адреса сети '10.20.1.0/24', который может отсутствовать в тестовом окружении.
	 * Сеть гарантированно создаётся через factory в getTestData().
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

	/**
     * Отображает страницу просмотра сети с полным списком её IP-адресов.
     *
     * Загружает все IP-адреса сети с join-ами на компьютеры, технику, VLAN,
     * упорядоченные по числовому значению адреса.
     *
     * GET-параметры:
     * @param int $id  Идентификатор сети (обязательно).
     *
     * @return string
     * @throws NotFoundHttpException если сеть не найдена
     */
    public function actionView(int $id)
	{
		$model=$this->findModel($id);
		$ips= NetIps::find()
			->joinWith(['comps','techs','network.netVlan'])
			->where(['networks_id'=>$model->id])
			->orderBy(['addr'=>SORT_ASC])
			->all();
	
		return $this->render('view',
            compact('model','ips')
        );
    }
	
	/**
	 * Отображает список сетей с возможностью переключения между активными и архивными.
	 *
	 * GET-параметры:
	 *   showArchived — (bool, опционально, по умолчанию false) показывать архивные сети вместо активных.
	 *   Также принимаются любые фильтры NetworksSearch через queryParams.
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		$searchModel = new NetworksSearch();
		$searchModel->archived= Yii::$app->request->get('showArchived',false);
		
		//ищем тоже самое но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;
		
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount,
		]);
	}
	
	/**
	 * AJAX: список входящих ACL/ACE-соединений для сети (с учётом вложенных).
	 *
	 * Возвращает список ACE-правил, у которых есть IP-доступ к данной сети или её дочерним сетям.
	 * Используется для отображения входящих связей на странице сети.
	 *
	 * GET-параметры:
	 * @param int $id  Идентификатор сети (обязательно).
	 *
	 * @return string
	 * @throws NotFoundHttpException если сеть не найдена
	 */
	public function actionIncomingConnectionsList(int $id)
	{
		/** @var Networks $model */
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$searchModel=new AcesSearch();
		
		// получаем всех детей
		$aces=$model->getIncomingAcesEffective();
		
		foreach ($aces as $id=>$ace) {
			if (!$ace->hasIpAccess()) unset($aces[$id]);
		}
		
		$ids=array_keys($aces);

		$dataProvider = $searchModel->search(array_merge(
			Yii::$app->request->queryParams,
			['AcesSearch'=>['ids'=>$ids]]
		));
		
		return $this->renderAjax('aces-list', [
			'searchModel'=>$searchModel,
			'dataProvider' => $dataProvider,
			'model' => $model
		]);
	}
	
	/**
	 * Acceptance test data for IncomingConnectionsList.
	 *
	 * Использует id из getTestData()['full']. Если у тестовой сети нет входящих ACE-соединений,
	 * экшен вернёт пустой список — это ожидаемое поведение и не является ошибкой.
	 */
	public function testIncomingConnectionsList(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Отображает карту распределения IP-пространства (IPAM) для всех сетей.
	 *
	 * Строит визуальную карту подсетей в заданном диапазоне префиксов
	 * относительно базового IP-адреса. При отсутствии параметров используются
	 * дефолтные значения: baseIp=192.168.0.0, minPrefix=29, maxPrefix=24.
	 *
	 * GET-параметры (все опциональны):
	 *   baseIp     — базовый IP-адрес для карты (строка, по умолчанию '192.168.0.0').
	 *   minPrefix  — минимальная длина префикса подсетей (int, по умолчанию 29).
	 *   maxPrefix  — максимальная длина префикса подсетей (int, по умолчанию 24).
	 *
	 * @return string
	 */
	public function actionIpam()
	{
		$baseIp = Yii::$app->request->get('baseIp', '192.168.0.0');
		$minPrefix = (int) Yii::$app->request->get('minPrefix', 29);
		$maxPrefix = (int) Yii::$app->request->get('maxPrefix', 24);
		
		$models = Networks::find()->all();
		return $this->render('ipam', compact('models','baseIp','minPrefix','maxPrefix'));
	}

	/**
	 * Acceptance test data for Ipam.
	 *
	 * Проверяет рендер карты IP-пространства без параметров — в этом случае используется
	 * дефолтный базовый адрес 192.168.0.0. Тест проверяет, что страница рендерится без ошибок
	 * независимо от наличия данных о сетях в БД.
	 */
	public function testIpam(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}
	
	public $modelClass=Networks::class;

}
