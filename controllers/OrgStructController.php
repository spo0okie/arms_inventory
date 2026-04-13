<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\UsersSearch;
use Yii;
use app\models\OrgStruct;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;
use yii\web\Response;

/**
 * OrgStructController implements the CRUD actions for OrgStruct model.
 *
 * Управляет организационными структурами (подразделениями) предприятия.
 * Поддерживает иерархическое дерево подразделений внутри организации
 * и предоставляет AJAX-бэкенд для Dependent Dropdown.
 */
class OrgStructController extends ArmsBaseController
{
	public $modelClass=OrgStruct::class;

	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['dep-drop']
		]);
	}
	
	/**
	 * Отображает список подразделений верхнего уровня выбранной организации.
	 *
	 * Выводит только корневые элементы (parent_id = null) в алфавитном порядке.
	 *
	 * @param int $org_id GET: ID организации (по умолчанию 1)
	 * @return mixed
	 */
    public function actionIndex($org_id=1)
    {
		return $this->render('index', [
			'models' => OrgStruct::find()
				->where(['org_id'=>$org_id,'parent_id'=>null])
				->orderBy(['name'=>SORT_ASC])
				->all(),
			'org_id'=>$org_id
		]);
    }
	
	
	/**
	 * Отображает страницу подразделения с перечнем сотрудников.
	 *
	 * Инициализирует UsersSearch с фильтрами по org_id и Orgeh (hr_id подразделения),
	 * чтобы по умолчанию показывать только сотрудников данного подразделения.
	 *
	 * @param int $id GET: ID подразделения OrgStruct
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
    public function actionView(int $id)
    {
    	/** @var OrgStruct $model */
		$model=$this->findModel($id);
  
		$params=Yii::$app->request->queryParams;
		$params=ArrayHelper::setTreeDefaultValue($params,['UsersSearch','org_id'],$model->org_id);
		$params=ArrayHelper::setTreeDefaultValue($params,['UsersSearch','Orgeh'],$model->hr_id);
  
		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search($params);
		
        return $this->render('view', [
            'model' => $model,
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создаёт новое подразделение OrgStruct.
     *
     * При успешном сохранении редиректит на страницу подразделения,
     * если GET-параметр return=previous — на предыдущую страницу (Url::previous()).
     *
     * @return mixed
     *
     * GET-параметры:
     * - return (string, опционально): если 'previous' — редирект на предыдущий URL.
     *
     * POST-параметры (через OrgStruct::load):
     * - OrgStruct[name]     (string, обязательно): название подразделения
     * - OrgStruct[org_id]   (int):                 ID организации
     * - OrgStruct[parent_id] (int, опционально):   ID родительского подразделения
     * - OrgStruct[hr_id]    (string, опционально): код подразделения в системе HR
     * - прочие поля модели OrgStruct
     */
    public function actionCreate()
    {
        $model = new OrgStruct();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id, 'org_id' => $model->org_id]);
        }
	
		$model->load(Yii::$app->request->get());
        return $this->render('create', [
            'model' => $model,
        ]);
    }
    
	
	/**
	 * AJAX-бэкенд Dependent Dropdown: возвращает список подразделений для выбранных организаций.
	 *
	 * Используется виджетом kartik\depdrop\DepDrop: при изменении поля организации
	 * обновляет список подразделений в зависимом поле.
	 *
	 * Ответ всегда в формате JSON:
	 * - при наличии parents: ['output' => [['id'=>...,'name'=>...], ...], 'selected' => '']
	 * - при отсутствии данных: ['output' => '', 'selected' => '']
	 *
	 * POST-параметры:
	 * - depdrop_parents (int[], обязательно): массив ID организаций (родительских элементов dropdown)
	 *
	 * @return array JSON-ответ для DepDrop
	 */
	public function actionDepDrop()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		if (isset($_POST['depdrop_parents'])) {
			$parents = $_POST['depdrop_parents'];
			if ($parents != null) {
				$models=OrgStruct::fetchOrgNames($parents);
				$output=[];
				foreach ($models as $id=>$name) {
					$output[]=['id'=>$id,'name'=>$name];
				}
				//$out = self::getSubCatList($cat_id);
				// the getSubCatList function will query the database based on the
				// cat_id and return an array like below:
				// [
				//    ['id'=>'<sub-cat-id-1>', 'name'=>'<sub-cat-name1>'],
				//    ['id'=>'<sub-cat_id_2>', 'name'=>'<sub-cat-name2>']
				// ]
				return ['output'=>$output, 'selected'=>''];
			}
		}
		return ['output'=>'', 'selected'=>''];
	}
	/**
	 * Acceptance test data for actionDepDrop.
	 *
	 * Тест пропущен: actionDepDrop — это AJAX Dependent Dropdown callback.
	 * Для корректного теста требуется:
	 *   - POST-запрос с параметром depdrop_parents (массив int[] ID организаций);
	 *   - наличие в БД хотя бы одной организации (Org) с подразделениями (OrgStruct).
	 * Acceptance-фреймворк не формирует depdrop-POST автоматически,
	 * а создание связанного набора Org + OrgStruct через getTestData() не предусмотрено
	 * для данного контроллера. Заменить skip на реальный тест можно только
	 * после добавления OrgStruct и Org в ModelFactory.
	 *
	 * @return array
	 */
	public function testDepDrop(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'POST'     => ['depdrop_parents' => [$testData['full']->org_id]],
			'response' => 200,
		]];
	}

	
}
