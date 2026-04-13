<?php

namespace app\modules\schedules\controllers;

use app\helpers\StringHelper;
use app\models\MaintenanceJobs;
use app\modules\schedules\models\SchedulesEntries;
use app\models\Services;
use Throwable;
use Yii;
use app\modules\schedules\models\Schedules;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class SchedulesController extends \app\controllers\ArmsBaseController
{
	public $modelClass='app\modules\schedules\models\Schedules';
	
	/**
	 * Отображает страницу просмотра расписания.
	 * Если расписание является override (isOverride === true), перенаправляет
	 * на просмотр родительского расписания (override_id), передавая все GET-параметры.
	 *
	 * GET-параметры:
	 * @param int $id  ID расписания (Schedules)
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
	 * если текущее является override. Требуется фикстура с гарантированно
	 * не-override записью, которая недоступна через getTestData().
	 *
	 * @return array
	 */
	public function testView(): array
	{
		$testData = $this->getTestData();
		// empty-модель гарантированно не имеет override_id (null), поэтому не делает redirect
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['empty']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Тест пропущен: actionTtip требует расписания с корректной цепочкой override,
	 * которая не гарантируется в тестовых данных getTestData().
	 *
	 * @return array
	 */
	public function testTtip(): array
	{
		$testData = $this->getTestData();
		return [
			['name' => 'ttip full',  'GET' => ['id' => $testData['full']->id],  'response' => 200],
			['name' => 'ttip empty', 'GET' => ['id' => $testData['empty']->id], 'response' => 200],
		];
	}
	
	/**
	 * Создаёт новое расписание работы.
	 * Поддерживает предзаполнение имени через GET-параметры привязки:
	 * - `attach_service`   — привязка к сервису (providing_schedule_id)
	 * - `support_service`  — привязка к поддержке сервиса
	 * - `attach_job`       — привязка к задаче обслуживания (MaintenanceJobs)
	 * - `override_id`      — создание override-расписания для указанного ID
	 * Если задано `defaultItemSchedule` — создаёт начальную запись расписания (SchedulesEntries).
	 * После сохранения перенаправляет на страницу привязанной сущности или на view расписания.
	 *
	 * GET-параметры:
	 *   attach_service  — int, ID сервиса для привязки
	 *   support_service — int, ID поддерживаемого сервиса
	 *   attach_job      — int, ID задачи обслуживания
	 *   override_id     — int, ID переопределяемого расписания
	 *
	 * POST-параметры: поля модели Schedules через Yii2 load().
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new Schedules();
		
		$support_service=null;
		$service=null;
		$job=null;
		$item=null;

		//
		if (Yii::$app->request->get('attach_service')) {
			$service= Services::findOne(Yii::$app->request->get('attach_service'));
			if (is_object($service)) {
				$model->name= Schedules::$title.' работы '.StringHelper::mb_lcfirst($service->name);
			}
		} elseif (Yii::$app->request->get('support_service')) {
			$support_service= Services::findOne(Yii::$app->request->get('support_service'));
			if (is_object($support_service)) {
				$model->name= Schedules::$title.' поддержки '.StringHelper::mb_lcfirst($support_service->name);
			}
		} elseif (Yii::$app->request->get('attach_job')) {
			$job= MaintenanceJobs::findOne(Yii::$app->request->get('attach_job'));
			if (is_object($job)) {
				$model->name= Schedules::$title.' '.StringHelper::mb_lcfirst($job->name);
			}
		}

		$model->load(Yii::$app->request->get());
		
		if ($model->override_id) {
			//$model->parent_id = $model->override_id;
			$model->start_date = date('Y-m-d');
			$model->name='Override for #'.$model->override_id;
		}

		if ($model->load(Yii::$app->request->post())) {
			if ($model->save()) {
				//если было указано расписание по умолчанию - надо его создать в БД
				if (strlen($model->defaultItemSchedule)) {
					$item=new SchedulesEntries();
					$item->schedule=$model->defaultItemSchedule;
					$item->date='def';
					$item->schedule_id = $model->id;
					$item->save();
				}
				//если надо привязать сервис
				if (is_object($service)) {
					$service->providing_schedule_id = $model->id;
					$service->save();
					return $this->defaultReturn(['services/view', 'id' => $service->id],[$model]);
				} elseif (is_object($support_service)) { //или поддержку сервиса
					$support_service->providing_schedule_id = $model->id;
					$support_service->save();
					return $this->defaultReturn(['services/view', 'id' => $support_service->id],[$model]);
				} elseif (is_object($job)) { //или поддержку сервиса
					$job->schedules_id = $model->id;
					$job->save();
					return $this->defaultReturn(['maintenance-jobs/view', 'id' => $job->id],[$model]);
				} else
					return $this->defaultReturn(['view', 'id' => $model->id],[$model]);
			}
		}
		
		
		
		return $this->defaultRender('create',[
			'model' => $model,
			'attach_service'=>Yii::$app->request->get('attach_service')
		]);
		
	}
	
	
	/**
	 * Удаляет расписание.
	 * После удаления перенаправляет на страницу родительского расписания (parent_id),
	 * если оно существует, иначе — на список расписаний (index).
	 *
	 * GET-параметры:
	 * @param int $id  ID удаляемого расписания (Schedules)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если расписание не найдено
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
		/** @var Schedules $model */
    	$model=$this->findModel($id);
    	$parent=$model->parent_id;
        $this->findModel($id)->delete();

        return $this->redirect($parent?['view', 'id' => $parent]:['index']);
    }

	/**
	 * Тестирует actionDelete: удаление расписания с redirect на index.
	 *
	 * SchedulesController и ScheduledAccessController оба используют Schedules::class как modelClass,
	 * поэтому они делят один testDataCache. Чтобы не зависеть от порядка выполнения тестов
	 * (ScheduledAccessController может удалить 'delete'-запись из общего кэша раньше),
	 * создаём отдельную запись напрямую через ModelFactory.
	 *
	 * @return array
	 */
	public function testDelete(): array
	{
		$toDelete = \app\generation\ModelFactory::create($this->modelClass, ['empty' => true]);
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $toDelete->id],
			'POST'     => [],
			'response' => 302,
		]];
	}
}
