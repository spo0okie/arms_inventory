<?php

namespace app\controllers;


use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use kartik\markdown\Markdown;
use Yii;
use yii\web\NotFoundHttpException;


/**
 * MaintenanceReqsController implements the CRUD actions for MaintenanceReqs model.
 */
class MaintenanceReqsController extends ArmsBaseController
{
	public $modelClass=MaintenanceReqs::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['list'],
		]);
	}
	
	/**
	 * Tooltip для требования технического обслуживания.
	 *
	 * Рендерит partial-шаблон 'ttip' с карточкой требования.
	 * При наличии параметра timestamp рендерит историческую версию записи
	 * из журнала изменений (findJournalRecord).
	 * Опциональные параметры позволяют отобразить дополнительный контекст
	 * прямо в тултипе: какой Job удовлетворяет требование и какой Req его поглощает.
	 *
	 * GET-параметры:
	 * @param int      $id          Идентификатор MaintenanceReqs.
	 * @param int|null $satisfiedBy ID задания (MaintenanceJobs), которое
	 *                               удовлетворяет данное требование (опционально).
	 * @param int|null $absorbedBy  ID другого требования (MaintenanceReqs),
	 *                               которое поглощает данное требование (опционально).
	 *                               Передаётся, когда требование является избыточным
	 *                               в наборе.
	 *  - timestamp (string, опционально) — метка времени для исторической версии.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена
	 */
	public function actionTtip(int $id, $satisfiedBy=null, $absorbedBy=null)
	{
		if ($t=Yii::$app->request->get('timestamp')) {
			return $this->renderPartial('ttip', [
				'model' => $this->findJournalRecord($id,$t),
				'job' => $satisfiedBy?MaintenanceJobs::findOne($satisfiedBy):null,
				'absorbed' => $absorbedBy?MaintenanceReqs::findOne($absorbedBy):null,
			]);
		}
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'job' => $satisfiedBy?MaintenanceJobs::findOne($satisfiedBy):null,
			'absorbed' => $absorbedBy?MaintenanceReqs::findOne($absorbedBy):null,
		]);
	}
	
	/**
	 * Рендер таблицы всех требований технического обслуживания.
	 *
	 * Возвращает HTML-таблицу со всеми MaintenanceReqs, отсортированными по имени.
	 * Каждая строка содержит item-виджет требования и markdown-описание.
	 * Используется для встраивания в другие страницы.
	 *
	 * GET-параметры: отсутствуют.
	 *
	 * @return string HTML-строка с таблицей всех требований
	 */
	public function actionList()
	{
		$output=[];
		$output[]='<table>';
		foreach (MaintenanceReqs::find()->orderBy(['name'=>SORT_ASC])->All() as $item) {
			$output[]='<tr>';
				$output[]='<td>';
					$output[]=$item->renderItem($this->view,['static_view'=>true]);
				$output[]='</td>';
				$output[]='<td>';
					$output[]=Markdown::convert($item->description);
				$output[]='</td>';
			$output[]='</tr>';
		}
		$output[]='</table>';
		return implode("\n",$output);
	}	
	/**
	 * Acceptance test data for List.
	 *
	 * Вызывает action без GET-параметров. Является smoke-тестом: проверяет,
	 * что таблица требований рендерится с кодом 200.
	 * Перед вызовом создаются тестовые данные через getTestData()['full'],
	 * чтобы гарантировать наличие хотя бы одной записи MaintenanceReqs в БД.
	 */
	public function testList(): array
	{
		$this->getTestData();
		return [[]];
	}

}
