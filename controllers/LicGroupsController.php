<?php

namespace app\controllers;

use app\models\LicItemsSearch;
use app\models\links\LicLinks;
use Yii;
use app\models\LicGroups;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * LicGroupsController implements the CRUD actions for LicGroups model.
 */
class LicGroupsController extends ArmsBaseController
{
	
	/**
	 * Acceptance test data for Link.
	 *
	 * Пропускается, так как для теста необходимо:
	 *  - создать LicGroup через getTestData()['full'];
	 *  - создать и привязать хотя бы один из связанных объектов:
	 *    Soft, Arms, Users или Comps (их IDs передаются через many-to-many).
	 * Без этих данных тест привязки не может быть выполнен корректно.
	 */
	public function testLink(): array
	{
		return self::skipScenario('default', 'requires LicGroup with linked soft/arms/users/comps — prepare via getTestData() and link objects manually');
	}
	public function disabledActions()
	{
		return ['item-by-name',];
	}

    /**
     * Страница просмотра группы лицензий.
     *
     * Отображает карточку группы, список лицензионных позиций (LicItems),
     * входящих в группу, а также связанные объекты (Soft, Arms, Users, Comps)
     * через LicLinks.
     *
     * GET-параметры:
     * @param int $id Идентификатор LicGroups.
     *
     * @return mixed
     * @throws NotFoundHttpException если запись не найдена
     */
    public function actionView(int $id)
    {
	    $searchModel = new LicItemsSearch();
	    $query=Yii::$app->request->queryParams;
	    if (!isset($query['LicItemsSearch'])) $query['LicItemsSearch']=[];
	    $query['LicItemsSearch']['lic_group_id']=$id;
	    $dataProvider = $searchModel->search($query);
	    
	    $linksData=new ArrayDataProvider([
			'allModels' => LicLinks::findForLic('groups',$id),
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
		]);
			

        return $this->render('view', [
            'model' => $this->findModel($id),
		    'searchModel' => $searchModel,
		    'dataProvider' => $dataProvider,
	        'linksData' => $linksData,
	    ]);

    }
	
	
	/**
	 * Отвязка объекта от группы лицензий.
	 *
	 * Удаляет связь одного из связанных объектов (Soft, Arms, Users или Comps)
	 * с указанной группой лицензий и перенаправляет на страницу просмотра группы.
	 * Передаётся ровно один из необязательных параметров — тот объект, связь
	 * с которым нужно разорвать.
	 *
	 * GET-параметры:
	 * @param int      $id       Идентификатор LicGroups.
	 * @param int|null $soft_id  ID программного обеспечения для отвязки.
	 * @param int|null $arms_id  ID АРМ для отвязки.
	 * @param int|null $users_id ID пользователя для отвязки.
	 * @param int|null $comps_id ID компьютера для отвязки.
	 *
	 * @return string|Response
	 * @throws NotFoundHttpException если LicGroups с данным id не найдена
	 */
	public function actionUnlink(int $id, $soft_id=null, $arms_id=null, $users_id=null, $comps_id=null){
		/** @var LicGroups $model */
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
	 *  - создать LicGroup через getTestData()['full'];
	 *  - привязать к ней хотя бы один из объектов: Soft, Arms, Users, Comps;
	 *  - передать в GET параметр id и соответствующий *_id объекта для отвязки.
	 * Без предварительно созданных связей проверить логику разрыва невозможно.
	 */
	public function testUnlink(): array
	{
		return self::skipScenario('default', 'requires LicGroup with linked objects — prepare via getTestData() and link objects manually');
	}
	public $modelClass=LicGroups::class;


}
