<?php

namespace app\controllers;

use app\components\Forms\assets\ArmsFormAsset;
use app\models\Aces;
use app\models\Acls;
use Yii;


/**
 * AclsController implements the CRUD actions for Acls model.
 */
class AclsController extends ArmsBaseController
{
	public $modelClass=Acls::class;
	
	public function disabledActions()
	{
		return ['item-by-name',];
	}
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['ace-cards']
		]);
	}
	
	/**
	 * Отображает список ACE (Access Control Entry) для заданного ACL.
	 *
	 * Рендерит представление ace-cards с моделью ACL и всеми
	 * привязанными к ней записями ACE.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор ACL-записи (acls.id)
	 *
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException если ACL с заданным id не найден
	 */
	public function actionAceCards(int $id) {
		return $this->defaultRender('ace-cards',['model'=>$this->findModel($id)]);
	}
	
	/**
	 * Тестовые данные приёмочного теста для actionAceCards.
	 *
	 * Тест пропущен (skip): для корректной проверки необходим сохранённый ACL
	 * с как минимум одним привязанным ACE. Условие выполнимо через getTestData(),
	 * если фабрика модели Acls создаёт объект с предзаполненными связанными ACE
	 * через yii2-linker-behavior. До реализации такой фабрики тест отключён.
	 *
	 * @return array сценарий skip
	 */
	public function testAceCards(): array
	{
		return self::skipScenario('default', 'requires stable ACL-to-schedule relation');
	}
	
	public function routeOnUpdate($model)
	{
		if (Yii::$app->request->get('accept')) return ['update','id'=>$model->id];
		return $model->schedules_id?
			['/scheduled-access/view','id'=>$model->schedules_id]:
			['view','id'=>$model->id];
	}
	
	
	/**
	 * @inheritdoc
	 */
    public function routeOnDelete($model)
    {
    	/** @var Acls $model */
    	$schedules_id=$model->schedules_id;
        return $schedules_id?
			['/scheduled-access/view','id'=>$schedules_id]:
			['/scheduled-access/index-acl'];
    }
	
	
	/**
	 * Создаёт новую пару ACL + ACE в единой объединённой форме.
	 *
	 * Логика: т.к. создавать ACL без хотя бы одного ACE неинтуитивно,
	 * форма включает поля обеих моделей одновременно. Сначала валидируется
	 * и сохраняется Acls, затем — Aces с привязкой acls_id. Если ACE-поля
	 * не переданы, ACL сохраняется без ACE.
	 *
	 * POST-параметры (через load):
	 *   - Acls[*]  — поля модели Acls
	 *   - Aces[*]  — поля модели Aces (опционально)
	 *
	 * GET-параметры (предзаполнение формы):
	 *   - Acls[*]  — предзаполнение полей ACL
	 *   - Aces[*]  — предзаполнение полей ACE
	 *   - accept   — если передан, после сохранения перенаправляет на update
	 *
	 * При успехе перенаправляет на страницу расписания (schedules) или view.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		/** @var Acls $model */
		$model = new $this->modelClass();
		$ace = new Aces();
		
		if ($model->load(Yii::$app->request->post())){
			if($model->validate()) {
				if ($ace->load(Yii::$app->request->post())){
					if($ace->validate()) {
						//успех по обеим моделям
						$model->save();
						$model->refresh();
						$ace->acls_id=$model->id;
						$ace->save();
						return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
					} else {
						//неудача по ACE
						$model->load(Yii::$app->request->get());
						$ace->load(Yii::$app->request->get());
						return $this->defaultRender('create', ['model' => $model,'ace'=>$ace]);
					}
				}
				//успех ACL, отсутствует ACE
				$model->save();
				return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
			}
		}
		
		//неудача ACL
		$model->load(Yii::$app->request->get());
		$ace->load(Yii::$app->request->get());
		return $this->defaultRender('create', ['model' => $model,'ace'=>$ace]);
	}
}
