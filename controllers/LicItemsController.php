<?php

namespace app\controllers;

use app\models\Contracts;
use app\models\LicKeys;
use app\models\links\LicLinks;
use Yii;
use app\models\LicItems;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use yii\data\ActiveDataProvider;
use yii\web\Response;


/**
 * LicItemsController implements the CRUD actions for LicItems model.
 */
class LicItemsController extends ArmsBaseController
{
	
	/**
	 * Acceptance test data for Contracts.
	 *
	 * Проверяет страницу контрактов для существующей лицензионной позиции.
	 * Использует getTestData()['full'] — полностью заполненный экземпляр LicItems,
	 * сохранённый в БД. Это гарантирует стабильный id без жёсткой зависимости
	 * от конкретной записи в БД (в отличие от устаревшего макроса {anyId}).
	 */
	public function testContracts(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Acceptance test data for Delete.
	 *
	 * Проверяет удаление лицензионной позиции.
	 * Использует getTestData()['delete'] — минимальную модель LicItems,
	 * созданную специально для удаления; её id динамичен и не зависит
	 * от конкретной записи в БД (в отличие от захардкоженного id=4).
	 */
	public function testDelete(): array
	{
		$testData = $this->getTestData();
		$delete = $testData['delete'];
		return [[
			'name' => 'default',
			'GET' => ['id' => $delete->id],
			'POST' => [],
			'saveModel' => ['storeAs' => 'deleted', 'model' => ['id' => $delete->id]],
			'dropReverseLinks' => ['id' => $delete->id],
			'response' => 302,
		]];
	}
	
	/**
	 * Acceptance test data for Link.
	 *
	 * Пропускается, так как для теста необходимо:
	 *  - создать LicItems через getTestData()['full'];
	 *  - создать и привязать хотя бы один из объектов: Soft, Arms, Users или Comps.
	 * Без предварительно подготовленных связанных объектов тест привязки
	 * не может быть выполнен корректно.
	 */
	public function testLink(): array
	{
		return self::skipScenario('default', 'requires LicItems with linked soft/arms/users/comps — prepare via getTestData() and link objects manually');
	}
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['hint-arms','contracts'],
		]);
	}
	
	public function disabledActions()
	{
		return ['item-by-name',];
	}
	
	/**
	 * Возвращает подсказку (hint) ARM-объектов, связанных с лицензионной позицией через контракты.
	 *
	 * Используется для автодополнения поля выбора АРМ в форме редактирования:
	 * метод находит контракты, привязанные к LicItems, и через них — список
	 * связанных АРМ, пригодных для привязки.
	 *
	 * GET-параметры:
	 * @param int    $id   Идентификатор LicItems.
	 * @param string $form Имя формы-получателя подсказки (например, 'LicItems').
	 *
	 * @return mixed Сырой HTML/JSON-фрагмент подсказки или null если модель не найдена.
	 * @throws NotFoundHttpException если LicItems с данным id не найдена
	 */
	public function actionHintArms(int $id, string $form)
	{
		if ($model=$this->findModel($id)) {
			/** @var $model LicItems */
			return Yii::$app->formatter->asRaw(Contracts::fetchArmsHint($model->contracts_ids,$form));
		}
		return null;
	}


	/**
	 * Displays a single Arms model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	/*public function actionContracts(int $id)
	{
		return $this->renderAjax('contracts', ['model' => $this->findModel($id)]);
	}*/


	/**
	 * Acceptance test data for HintArms.
	 *
	 * Проверяет endpoint подсказки АРМ для существующей лицензионной позиции.
	 * id берётся из getTestData()['full'] — полностью заполненного экземпляра LicItems,
	 * сохранённого в БД. Параметр form='test' — произвольное имя формы,
	 * достаточное для прохождения валидации без реального UI-контекста.
	 */
	public function testHintArms(): array
	{
		$testData=$this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id, 'form' => 'test'],
			'response' => 200,
		]];
	}
	/**
	 * Страница просмотра лицензионной позиции.
	 *
	 * Отображает карточку LicItems, список связанных лицензионных ключей (LicKeys)
	 * и таблицу привязанных объектов (Soft, Arms, Users, Comps) через LicLinks.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор LicItems.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена
	 */
    public function actionView(int $id)
    {
	
		return $this->render('view', [
            'model' => $this->findModel($id),
	        'keys' => new ActiveDataProvider([
		        'query' => LicKeys::find()->where(['lic_items_id'=>$id]),
	        ]),
			'linksData'=>new ArrayDataProvider([
				'allModels' => LicLinks::findForLic('items',$id),
				'key'=>'id',
				'sort' => [
					'attributes'=> [
						'objName',
						'comment',
						'changedAt',
						'changedBy',
					],
					'defaultOrder' => [
						'objName' => SORT_ASC
					]
				],
				'pagination' => false,
			])
        ]);
    }
	
	/**
	 * Отвязка объекта от лицензионной позиции.
	 *
	 * Удаляет связь одного из объектов (Soft, Arms, Users или Comps) с указанной
	 * лицензионной позицией и перенаправляет на страницу просмотра позиции.
	 * Передаётся ровно один из необязательных параметров.
	 *
	 * GET-параметры:
	 * @param int      $id       Идентификатор LicItems.
	 * @param int|null $soft_id  ID программного обеспечения для отвязки.
	 * @param int|null $arms_id  ID АРМ для отвязки.
	 * @param int|null $users_id ID пользователя для отвязки.
	 * @param int|null $comps_id ID компьютера для отвязки.
	 *
	 * @return string|Response
	 * @throws NotFoundHttpException если LicItems с данным id не найдена
	 */
	public function actionUnlink(int $id, $soft_id=null, $arms_id=null, $users_id=null, $comps_id=null){
		/** @var LicItems $model */
		$model = $this->findModel($id);
		$updated = false;

		//если нужно отстегиваем софт
		if (!is_null($soft_id)) {
			$model->soft_ids=array_diff($model->soft_ids,[$soft_id]);
			$updated=true;
		}
		
		//если нужно то АРМ
		if (!is_null($arms_id)) {
			$model->arms_ids=array_diff($model->arms_ids,[$arms_id]);
			$updated=true;
		}
		
		//если нужно то Комп
		if (!is_null($comps_id)) {
			$model->comps_ids=array_diff($model->comps_ids,[$comps_id]);
			$updated=true;
		}
		
		//если нужно то Пользователя
		if (!is_null($users_id)) {
			$model->users_ids=array_diff($model->users_ids,[$users_id]);
			$updated=true;
		}
		
		//сохраняем
		if ($updated) $model->save();

		return $this->redirect(['view', 'id' => $model->id]);
	}	
	/**
	 * Acceptance test data for Unlink.
	 *
	 * Пропускается, так как для теста необходимо:
	 *  - создать LicItems через getTestData()['full'];
	 *  - привязать к ней хотя бы один из объектов: Soft, Arms, Users, Comps;
	 *  - передать в GET параметр id и соответствующий *_id объекта для отвязки.
	 * Без предварительно созданных связей проверить логику разрыва невозможно.
	 */
	public function testUnlink(): array
	{
		return self::skipScenario('default', 'requires LicItems with linked objects — prepare via getTestData() and link objects manually');
	}

	public $modelClass=LicItems::class;



}
