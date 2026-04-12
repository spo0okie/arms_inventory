<?php

namespace app\controllers;

use app\models\ManufacturersDict;
use app\models\Places;
use app\models\ui\MapItemForm;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * PlacesController implements the CRUD actions for Places model.
 *
 * Управляет помещениями (Places) — физическими локациями предприятия.
 * Предоставляет карты АРМов и подразделений, управление загрузкой планов этажей
 * и позиционированием объектов на карте помещения.
 */
class PlacesController extends ArmsBaseController
{
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['armmap','depmap'],
			'edit'=>['uploads','map-set','map-delete'],
		]);
	}
	

    /**
     * Отображает список всех помещений, упорядоченный по пути дерева.
     *
     * Использует SQL-функцию getplacepath(id) для получения полного пути
     * каждого помещения в иерархии и сортировки по нему.
     * GET-параметры: нет.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'models' => Places::find()
	            ->select([
	            	'{{places}}.*',
		            'getplacepath(id) AS path'
	            ])
	            ->orderBy('path')->all(),
        ]);
    }
	
	/**
	 * Отображает карту АРМов: все помещения с полной загрузкой связанного оборудования.
	 *
	 * Тяжёлый запрос: выполняет joinWith по множеству отношений (phones, inets,
	 * techs с вложенными comp/domain/services/licKeys/licItems/licGroups/contracts/
	 * state/model/manufacturer/user, materials с type/children/usages).
	 * Рекомендуется кэшировать или ограничивать на больших инсталляциях.
	 *
	 * GET-параметры:
	 * - showArchived (bool, опционально): если true — включает архивные объекты
	 *
	 * @return mixed
	 */
	public function actionArmmap()
	{
		ManufacturersDict::initCache();
		return $this->render('armmap', [
			'models' => Places::find()
				->joinWith([
					'phones',
					'inets',
					'techs.comp.domain',
					'techs.comps.services',
					'techs.licKeys',
					'techs.licItems',
					'techs.licGroups',
					'techs',
					'techs.contracts',
					'techs.state',
					'techs.model.type',
					'techs.model.manufacturer',
					'techs.user',
					'materials.type',
					'materials.children',
					'materials.usages', //дико добавляет тормозов
				])->orderBy('short')
				->all(),
			'show_archived'=> Yii::$app->request->get('showArchived',false),
		]);
	}

		
	/**
	 * Acceptance test data for actionArmmap.
	 *
	 * Тяжёлый тест: открывает карту АРМов без параметров.
	 * Тестовые записи Places создаются через getTestData() перед запросом.
	 * Ожидается HTTP 200. Покрывает базовую доступность страницы.
	 *
	 * @return array
	 */
	public function testArmmap(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}
	/**
	 * Отображает карту подразделений: помещения, в которых есть оборудование,
	 * привязанное к подразделениям (places_techs.departments_id IS NOT NULL).
	 *
	 * Группирует по верхнеуровневому помещению через SQL-функцию getplacetop(places.id).
	 * GET-параметры: нет.
	 *
	 * @return mixed
	 */
	public function actionDepmap()
	{
		
		$dataProvider = new ActiveDataProvider([
			'query' => Places::find()
				->joinWith(['techs'])
				->where(['not',['places_techs.departments_id'=>null]])
				->groupBy('getplacetop(places.id)'),
				//->all()
			'pagination'=>false
		]);
		
		return $this->render('depmap', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	
		
	/**
	 * Acceptance test data for actionDepmap.
	 *
	 * Открывает карту подразделений без параметров.
	 * Тестовые записи Places создаются через getTestData() перед запросом.
	 * Ожидается HTTP 200. Покрывает базовую доступность страницы.
	 *
	 * @return array
	 */
	public function testDepmap(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}
    /**
     * Отображает страницу помещения с полной загрузкой связанного оборудования.
     *
     * Выполняет тяжёлый joinWith по всем связанным отношениям (phones, inets,
     * techs с вложенными comp/domain/services/sandbox/licKeys/licItems/licGroups/
     * contracts/state/model/manufacturer/user, materials с type/children/usages).
     *
     * GET-параметры:
     * - id (int, обязательно): ID помещения
     * - showArchived (bool, опционально): если true — включает архивные объекты
     *
     * @param int $id GET: ID помещения
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
			'show_archived'=> Yii::$app->request->get('showArchived',false),
	        'models' => Places::find()
				->joinWith([
					'phones',
					'inets',
					'techs.comp.domain',
					'techs.comps.services',
					'techs.comps.sandbox',
					'techs.licKeys',
					'techs.licItems',
					'techs.licGroups',
					'techs',
					'techs.contracts',
					'techs.state',
					'techs.model',
					'techs.model.type',
					'techs.model.manufacturer',
					'techs.user',
					'materials.type',
					'materials.children',
					'materials.usages', //дико добавляет тормозов
				])->orderBy('short')
		        ->all(),
        ]);
    }
	
	
	/**
	 * Удаляет помещение и перенаправляет на родительское помещение или на список.
	 *
	 * Если у удаляемого помещения есть родитель (parent_id != null),
	 * выполняет редирект на страницу родителя; иначе — на actionIndex.
	 *
	 * GET-параметры:
	 * - id (int, обязательно): ID помещения для удаления
	 *
	 * @param int $id GET: ID помещения
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	/** @var Places $model */
    	$model=$this->findModel($id);
    	$parent_id=$model->parent_id;
        $model->delete();
		if (is_null($parent_id))
            return $this->redirect(['index']);
		else
			return $this->redirect(['view','id'=>$parent_id]);
    }
	
	/**
	 * Отображает страницу загрузки планов этажа для помещения.
	 *
	 * Позволяет загружать изображения планов (floorplan uploads),
	 * которые затем используются как подложка для карты размещения оборудования.
	 *
	 * GET-параметры:
	 * - id (int, обязательно): ID помещения
	 *
	 * @param int $id GET: ID помещения
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
	
	/**
	 * Acceptance test data for actionUploads.
	 *
	 * Страница uploads открывается без предварительно загруженных файлов —
	 * единственное условие: существующее помещение (Places).
	 * ModelFactory поддерживает Places через getTestData()['full'],
	 * поэтому тест можно выполнить реально.
	 *
	 * @return array
	 */
	public function testUploads(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	public $modelClass='\app\models\Places';
	
	/**
	 * Устанавливает позицию объекта на карте помещения.
	 *
	 * Загружает данные из POST в MapItemForm и сохраняет координаты объекта
	 * в JSON-структуре карты помещения. После сохранения редиректит
	 * на страницу помещения (place_id из формы).
	 *
	 * GET-параметры:
	 * - id (int, обязательно): ID помещения
	 *
	 * POST-параметры (через MapItemForm::load):
	 * - MapItemForm[place_id]   (int):    ID помещения
	 * - MapItemForm[item_type]  (string): тип объекта (например, 'techs')
	 * - MapItemForm[item_id]    (int):    ID объекта
	 * - MapItemForm[x]          (int):    координата X на плане
	 * - MapItemForm[y]          (int):    координата Y на плане
	 *
	 * @param int $id GET: ID помещения
	 * @return mixed
	 */
	public function actionMapSet(int $id){
		$model=new MapItemForm();
		$model->load(Yii::$app->request->post());
		$model->itemSet();
		return $this->redirect(['view','id'=>$model->place_id]);
		
	}
	/**
	 * Acceptance test data for actionMapSet.
	 *
	 * Тест пропущен: для работы карты необходимо наличие загруженного плана этажа (floorplan upload).
	 * Без плана координаты объектов не имеют смысла, а actionMapSet не формирует view —
	 * только сохраняет позицию и делает редирект. Кроме того, POST-payload MapItemForm
	 * требует подготовленных данных об объектах (item_type, item_id), которые
	 * не генерируются автоматически. Необходимо сначала реализовать поддержку
	 * загрузки планов в тестовой среде.
	 *
	 * @return array
	 */
	public function testMapSet(): array
	{
		return self::skipScenario('default', 'requires floorplan upload and MapItemForm POST payload');
	}

	/**
	 * Удаляет объект с карты помещения.
	 *
	 * Читает JSON-структуру карты модели Places, удаляет запись
	 * с указанным item_id из группы item_type и сохраняет модель.
	 * После сохранения редиректит на страницу помещения.
	 *
	 * GET-параметры:
	 * - id        (int, обязательно):    ID помещения
	 * - item_type (string, обязательно): тип объекта в JSON-карте (например, 'techs')
	 * - item_id   (int, обязательно):    ID объекта для удаления с карты
	 *
	 * @param int    $id        GET: ID помещения
	 * @param string $item_type GET: тип объекта на карте
	 * @param int    $item_id   GET: ID объекта на карте
	 * @return mixed
	 */
	public function actionMapDelete(int $id, string $item_type, int $item_id){
		/** @var Places $model */
		$model=$this->findModel($id);
		$mapStruct=json_decode($model->map);
		
		if (property_exists($mapStruct,$item_type)) {
			$items=$mapStruct->$item_type;
			if (property_exists($items,$item_id)) {
				unset ($items->$item_id);
				$mapStruct->$item_type=$items;
				$model->map=json_encode($mapStruct,JSON_UNESCAPED_UNICODE);
				$model->save();
			}
		}
		
		return $this->redirect(['view','id'=>$id]);
	}	
	/**
	 * Acceptance test data for actionMapDelete.
	 *
	 * Тест пропущен по той же причине, что и testMapSet():
	 * для удаления объекта с карты необходимо, чтобы карта была предварительно
	 * настроена (загружен план этажа и размещены объекты через actionMapSet).
	 * Без этих данных выполнение DELETE-сценария бессмысленно.
	 *
	 * @return array
	 */
	public function testMapDelete(): array
	{
		return self::skipScenario('default', 'requires floorplan upload and pre-placed map items');
	}

}
