<?php

namespace app\modules\schedules\controllers;

use app\components\DynaGridWidget;
use app\helpers\StringHelper;
use app\models\Acls;
use app\models\Places;
use app\modules\schedules\models\SchedulesAclSearch;
use app\models\Services;
use Yii;
use app\modules\schedules\models\Schedules;
use yii\web\NotFoundHttpException;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class ScheduledAccessController extends \app\controllers\ArmsBaseController
{
	public $modelClass=Schedules::class;
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['status']
		]);
	}
	
	
	/**
	 * Отображает список расписаний доступа (ACL-расписания) с поиском и фильтрацией.
	 * Использует SchedulesAclSearch и DynaGrid для построения таблицы.
	 * Поддерживает переключение отображения архивных записей.
	 *
	 * GET-параметры: стандартные параметры поиска SchedulesAclSearch через queryParams.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		//Services::cacheAllItems();
		//Places::cacheAllItems();
		$searchModel = new SchedulesAclSearch();
		$model= new $this->modelClass();
		$columns=DynaGridWidget::fetchVisibleAttributes($model,StringHelper::class2Id($this->modelClass).'-index');
		$this->archivedSearchInit($searchModel,$dataProvider,$switchArchivedCount,$columns);
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount??null,
		]);
	}
	
	/**
	 * Возвращает текущий статус расписания доступа: активно ли оно прямо сейчас.
	 * Вычисляет текущее время с учётом сдвига часового пояса из params['schedulesTZShift']
	 * и вызывает метод isWorkTime() модели Schedules.
	 *
	 * GET-параметры:
	 * @param int $id  ID расписания доступа (Schedules)
	 *
	 * @return mixed  Результат isWorkTime(): true/false или строка статуса
	 * @throws NotFoundHttpException если расписание не найдено
	 */
	public function actionStatus(int $id)
	{
		/** @var Schedules $model */
		$model=$this->findModel($id);
		return $model->isWorkTime(
			gmdate('Y-m-d',time()+Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Тестирует actionStatus: запрашивает статус расписания для записи
	 * из getTestData()['full']. Ожидает HTTP 200.
	 *
	 * @return array
	 */
	public function testStatus(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Отображает страницу просмотра расписания доступа.
	 * Если расписание является override (isOverride === true), перенаправляет
	 * на просмотр родительского расписания (override_id), передавая все GET-параметры.
	 *
	 * GET-параметры:
	 * @param int $id  ID расписания доступа (Schedules)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если расписание не найдено
	 */
    public function actionView(int $id)
    {
    	/** @var Schedules $model */
    	$model=$this->findModel($id);
    	if ($model->isOverride) {
    		$params=Yii::$app->request->get();
    		$params['id']=$model->override_id;
    		return $this->redirect(array_merge(['view'],$params));
		}
    		
        return $this->render('view', [
            'model' => $model,
        ]);
    }
	
	/**
	 * Тест пропущен: actionView может перенаправить на другое расписание,
	 * если текущее является override. Поведение непредсказуемо без фикстуры
	 * с гарантированно не-override записью.
	 *
	 * @return array
	 */
	public function testView(): array
	{
		$testData = $this->getTestData();
		// empty-модель: override_id=null → isOverride=false → рендер view без redirect
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['empty']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Создаёт новое расписание доступа (ACL-расписание).
	 * После успешного сохранения автоматически создаёт связанную запись Acls
	 * и перенаправляет на просмотр нового расписания в режиме редактирования ACL.
	 *
	 * POST-параметры: поля модели Schedules через Yii2 load().
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new Schedules();
		
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			$acl=new Acls();
			$acl->schedules_id=$model->id;
			$acl->save();
			return $this->redirect(['view', 'id' => $model->id, 'acl_mode'=>1] );
		}
		
		return $this->render('create', [
			'model' => $model,
		]);
	}
	
}
