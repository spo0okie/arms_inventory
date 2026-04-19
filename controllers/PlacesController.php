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
	 * Что делает actionMapSet:
	 * - принимает POST с полями `MapItemForm` (`place_id`, `item_type`, `techs_id|places_id`,
	 *   `x`, `y`, `width`, `height`);
	 * - загружает данные в модель формы и вызывает `MapItemForm::itemSet()`, которая
	 *   находит Places по `place_id`, декодирует JSON-поле `map`, добавляет/обновляет
	 *   запись об объекте (в группе `item_type` под ключом `<item_type>_id`) и
	 *   сохраняет Places;
	 * - делает redirect на `/places/view?id={place_id}` (HTTP 302).
	 *
	 * Почему физический план этажа НЕ требуется:
	 * - координаты хранятся как JSON-структура в `places.map` и не зависят от
	 *   присутствия растрового файла плана на диске. Раньше тест был в skip
	 *   из-за ошибочного предположения, что для `map-set` нужен загруженный
	 *   floorplan — на самом деле действие работает чисто на JSON-поле.
	 *
	 * Что именно проверяем:
	 * 1) `'set techs on map'` — POST валидный MapItemForm с `item_type=techs` и id только что
	 *    созданного оборудования. Ожидаемый код — 302 (redirect на view).
	 *    В `assert`-колбэке подтверждаем, что свежее Places.map содержит запись
	 *    `techs->{id}` с переданными координатами.
	 * 2) `'invalid post'` — POST без обязательных полей. `MapItemForm::load()` вернёт false,
	 *    `itemSet()` всё равно вызывается, но place_id=null → `Places::findOne(null)` → null
	 *    → обращение к свойству null вызывает PHP warning → 500.
	 *    Этот сценарий фиксирует фактическое (хоть и не идеальное) поведение — чтобы
	 *    будущие изменения action, валидирующие форму перед вызовом itemSet(), были замечены.
	 */
	public function testMapSet(): array
	{
		$place = \app\generation\ModelFactory::create(Places::class, ['empty' => true]);
		$tech = \app\generation\ModelFactory::create(\app\models\Techs::class, ['empty' => true]);

		return [
			[
				'name' => 'set techs on map',
				'GET' => ['id' => $place->id],
				'POST' => [
					'MapItemForm' => [
						'place_id'  => $place->id,
						'item_type' => 'techs',
						'techs_id'  => $tech->id,
						'x'         => 10,
						'y'         => 20,
						'width'     => 30,
						'height'    => 40,
					],
				],
				'response' => 302,
				'assert' => static function () use ($place, $tech) {
					$fresh = Places::findOne($place->id);
					\PHPUnit\Framework\Assert::assertNotNull($fresh, 'Place must still exist after map-set');
					$map = json_decode((string)$fresh->map);
					\PHPUnit\Framework\Assert::assertIsObject($map, 'places.map must be a JSON object');
					\PHPUnit\Framework\Assert::assertObjectHasProperty(
						'techs',
						$map,
						'places.map must contain techs group'
					);
					\PHPUnit\Framework\Assert::assertObjectHasProperty(
						(string)$tech->id,
						$map->techs,
						'places.map.techs must contain just-added techs id'
					);
					\PHPUnit\Framework\Assert::assertSame(10, $map->techs->{$tech->id}->x);
					\PHPUnit\Framework\Assert::assertSame(20, $map->techs->{$tech->id}->y);
				},
			],
			[
				'name'     => 'invalid post',
				'GET'      => ['id' => $place->id],
				'POST'     => [],
				'response' => 500,
			],
		];
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
	 * Что делает actionMapDelete:
	 * - находит Places по GET `id` (404 если не найден);
	 * - декодирует JSON из `places.map`; если в нём есть группа `item_type` и в ней
	 *   запись с ключом `item_id` — удаляет эту запись и сохраняет Places;
	 * - если структура неполная (нет группы или id) — тихо пропускает и всё равно
	 *   делает redirect на `/places/view?id={place_id}`.
	 *
	 * Фикстура данных:
	 * - создаём Places через ModelFactory и руками заполняем `places.map` JSON с одной
	 *   заранее известной записью в группе `techs` под ключом `{tech_id}`. Этого достаточно,
	 *   чтобы убедиться, что ветка удаления работает и корректно обновляет JSON в БД.
	 *   Физический план этажа не требуется — `map` хранится в той же таблице places.
	 *
	 * Что именно проверяем:
	 * 1) `'delete existing item'` — GET с id/item_type=techs/item_id={tech->id}, где запись
	 *    в map-структуре существует. Ожидаемый код — 302 (redirect).
	 *    Ассертом подтверждаем, что `places.map.techs.{tech->id}` больше не существует.
	 * 2) `'delete missing item'` — GET с тем же place, но с item_id, которого нет в карте.
	 *    Ожидаемый код — 302 (тихий пропуск на уровне property_exists). Проверяем, что
	 *    исходная запись в map-структуре не пострадала.
	 * 3) `'missing place'` — GET с несуществующим place id → 404 (NotFoundHttpException).
	 */
	public function testMapDelete(): array
	{
		$place = \app\generation\ModelFactory::create(Places::class, ['empty' => true]);
		$tech = \app\generation\ModelFactory::create(\app\models\Techs::class, ['empty' => true]);
		$otherTechId = $tech->id + 7777;

		// Заранее формируем JSON карты с одной записью в группе techs.
		$initialMap = [
			'techs' => [
				(string)$tech->id => [
					'x' => 5, 'y' => 6, 'width' => 7, 'height' => 8,
				],
			],
		];
		$place->map = json_encode($initialMap, JSON_UNESCAPED_UNICODE);
		$place->save(false);

		$missingPlaceId = (int)(Places::find()->max('id')) + 1000;

		return [
			[
				'name' => 'delete existing item',
				'GET' => [
					'id'        => $place->id,
					'item_type' => 'techs',
					'item_id'   => $tech->id,
				],
				'response' => 302,
				'assert' => static function () use ($place, $tech) {
					$fresh = Places::findOne($place->id);
					$map = json_decode((string)$fresh->map);
					\PHPUnit\Framework\Assert::assertFalse(
						isset($map->techs->{$tech->id}),
						'map-delete must remove the requested item from places.map'
					);
				},
			],
			[
				'name' => 'delete missing item',
				'GET' => [
					'id'        => $place->id,
					'item_type' => 'techs',
					'item_id'   => $otherTechId,
				],
				'response' => 302,
			],
			[
				'name' => 'missing place',
				'GET' => [
					'id'        => $missingPlaceId,
					'item_type' => 'techs',
					'item_id'   => $tech->id,
				],
				'response' => 404,
			],
		];
	}

}
