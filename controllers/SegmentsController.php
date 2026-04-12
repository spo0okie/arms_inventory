<?php

namespace app\controllers;

use app\models\NetworksSearch;
use app\models\ServicesSearch;
use Yii;
use app\models\Segments;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;


/**
 * SegmentsController implements the CRUD actions for Segments model.
 *
 * Управляет сетевыми сегментами (Segments).
 * Предоставляет компактный список сегментов и детальную страницу
 * с привязанными сетями (Networks) и сервисами (Services).
 */
class SegmentsController extends ArmsBaseController
{
	public $modelClass=Segments::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['list'],
		]);
	}
	
	/**
	 * Отображает компактную таблицу всех сегментов (partial view).
	 *
	 * Рендерит шаблон table-compact без пагинации и сортировки.
	 * Если модель имеет атрибут archived — по умолчанию фильтрует архивные записи.
	 *
	 * GET-параметры:
	 * - showArchived (bool, опционально): если true — включает архивные сегменты
	 *
	 * @return mixed
	 */
	public function actionList() {
		$query=($this->modelClass)::find();
		$model= new $this->modelClass();
		if ($model->hasAttribute('archived')) {
			if (!Yii::$app->request->get('showArchived',$this->defaultShowArchived))
				$query->where(['not',['IFNULL(archived,0)'=>1]]);
		}
		
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => false,
			'sort'=>false
		]);
		
		return $this->renderPartial('table-compact', [
			'dataProvider' => $dataProvider,
		]);
	}

    	
	/**
	 * Acceptance test data for actionList.
	 *
	 * Открывает компактную таблицу сегментов без параметров.
	 * Тестовые записи Segments создаются через getTestData() перед запросом.
	 * Ожидается HTTP 200.
	 *
	 * @return array
	 */
	public function testList(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}
    /**
     * Отображает страницу сегмента с привязанными сетями и сервисами.
     *
     * Инициализирует NetworksSearch (фильтр по segments_id) и ServicesSearch
     * (фильтр по segment_id), передавая archived из GET.
     *
     * GET-параметры:
     * - id           (int, обязательно):  ID сегмента
     * - showArchived (bool, опционально): если true — включает архивные сети и сервисы
     *
     * @param int $id GET: ID сегмента
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
		$networksSearch = new NetworksSearch();
		$networksSearch->segments_id=$id;
		$networksSearch->archived= Yii::$app->request->get('showArchived',false);
		$networksProvider = $networksSearch->search(Yii::$app->request->queryParams);
	
		$servicesSearch = new ServicesSearch();
		$servicesSearch->segment_id=$id;
		$servicesSearch->archived=Yii::$app->request->get('showArchived',false);
	
		$servicesProvider = $servicesSearch->search(Yii::$app->request->queryParams);
	
	
		return $this->render('view', [
            'model' => $this->findModel($id),
			'networksSearch' => $networksSearch,
			'networksProvider' => $networksProvider,
			'servicesSearch' => $servicesSearch,
			'servicesProvider' => $servicesProvider,
        ]);
    }

}
