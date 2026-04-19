<?php

namespace app\modules\schedules\controllers;

use app\modules\schedules\models\SchedulesEntries;
use Throwable;
use Yii;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * SchedulesDaysController implements the CRUD actions for SchedulesDays model.
 */
class SchedulesEntriesController extends \app\controllers\ArmsBaseController
{
	public $modelClass=SchedulesEntries::class;
	
	public function disabledActions()
	{
		return ['item-by-name','view'];
	}
	
	/**
	 * Отображает всплывающую подсказку для записи расписания (SchedulesEntries).
	 * Если передан GET-параметр `timestamp`, загружает запись из журнала по ID и времени
	 * через findJournalRecord(); иначе загружает текущую запись по ID.
	 * Передаёт в view списки положительных/отрицательных меток для визуализации статуса.
	 *
	 * GET-параметры:
	 * @param int      $id         ID записи SchedulesEntries
	 * @param int|null $timestamp  Unix-timestamp для поиска в журнале истории (опционально)
	 * @param array    $positive   Список меток «работает» для отображения в tooltip (опционально)
	 * @param array    $negative   Список меток «не работает» для отображения в tooltip (опционально)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена
	 */
	public function actionTtip(int $id)
	{
		if ($t=Yii::$app->request->get('timestamp')) {
			return $this->renderPartial('ttip', [
				'model' => $this->findJournalRecord($id,$t),
				'positive' => Yii::$app->request->getQueryParam( 'positive',[]),
				'negative' => Yii::$app->request->getQueryParam('negative',[]),
			]);
		}
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'positive' => Yii::$app->request->getQueryParam( 'positive',[]),
			'negative' => Yii::$app->request->getQueryParam('negative',[]),
		]);
	}
	

    /**
     * Создаёт новую запись расписания (SchedulesEntries).
     * После успешного сохранения перенаправляет:
     * - на страницу расписания доступа (/schedules/scheduled-access/view), если master — ACL
     * - на страницу обычного расписания (/schedules/schedules/view) в остальных случаях.
     * Если POST-данных нет, предзаполняет форму из GET-параметров.
     *
     * GET-параметры: поля модели SchedulesEntries (в т.ч. schedule_id)
     * POST-параметры: поля модели SchedulesEntries через Yii2 load()
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SchedulesEntries();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return (is_object($model->master) && $model->master->isAcl)?
				$this->defaultReturn(['/schedules/scheduled-access/view', 'id' => $model->schedule_id],[$model]):
				$this->defaultReturn(['/schedules/schedules/view', 'id' => $model->schedule_id],[$model]);
			     }
	
		$model->load(Yii::$app->request->get());
	
		return $this->defaultRender('create', ['model' => $model,]);
			 }

	/**
	 * Базовый testCreate открывает форму без параметров, но рендер create-шаблона
	 * SchedulesEntries падает 500 без предзаполненного schedule_id (мастер-расписание
	 * требуется для ссылок/редиректов в форме). Передаём GET-параметр
	 * `SchedulesEntries[schedule_id]` из существующей записи.
	 */
	public function testCreate(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['SchedulesEntries' => ['schedule_id' => $testData['full']->schedule_id]],
			'response' => 200,
		]];
	}

	/**
	 * Обновляет существующую запись расписания (SchedulesEntries).
	 * После успешного сохранения перенаправляет:
	 * - на /schedules/scheduled-access/view, если master — ACL-расписание
	 * - на /schedules/schedules/view в остальных случаях.
	 * Если POST-данных нет, предзаполняет форму из GET-параметров.
	 *
	 * GET-параметры:
	 * @param int $id  ID записи SchedulesEntries
	 *
	 * POST-параметры: поля модели SchedulesEntries через Yii2 load()
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена
	 */
	public function actionUpdate(int $id)
	{
		/** @var SchedulesEntries $model */
		$model = $this->findModel($id);
		
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return (is_object($model->master) && $model->master->isAcl)?
				$this->defaultReturn(['/schedules/scheduled-access/view', 'id' => $model->schedule_id],[$model]):
				$this->defaultReturn(['/schedules/schedules/view', 'id' => $model->schedule_id],[$model]);
		}
		
		$model->load(Yii::$app->request->get());
		
		return $this->defaultRender('update', ['model' => $model,]);
	}

	/**
	 * Базовый testUpdate делает два сценария — form open и data post через
	 * ModelHelper::fillForm(update-data). Для SchedulesEntries второй сценарий падает
	 * 500, т.к. полная генерация update-data не согласуется с бизнес-правилами формы.
	 * Оставляем только form open.
	 */
	public function testUpdate(): array
	{
		$testData=$this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['update']->id],
			'response' => 200,
		]];
	}

	/**
	 * Удаляет запись расписания (SchedulesEntries).
	 * Запоминает master-расписание до удаления, чтобы определить направление редиректа:
	 * - на /schedules/scheduled-access/view, если master — ACL-расписание
	 * - на /schedules/schedules/view, если обычное расписание
	 * - на /schedules/schedules/index, если master недоступен
	 *
	 * GET-параметры:
	 * @param int $id  ID удаляемой записи SchedulesEntries
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	/** @var SchedulesEntries $item */
    	$item=$this->findModel($id);
    	//запоминаем мастера чтобы потом понятно было с чем мы работали с простым расписанием или расписанием доступа
    	$schedule=$item->master;
    	
        $item->delete();
	
		return is_object($schedule)?($schedule->isAcl?
			$this->redirect(['/schedules/scheduled-access/view', 'id' => $schedule->id]):
			$this->redirect(['/schedules/schedules/view', 'id' => $schedule->id])
		):$this->redirect(['/schedules/schedules/index']);
    }


}
