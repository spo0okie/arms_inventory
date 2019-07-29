<?php

namespace app\controllers;

use Codeception\Module\Yii2;
use Yii;
use app\models\Places;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PlacesController implements the CRUD actions for Places model.
 */
class PlacesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }


	/**
	 * Displays a tooltip.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip($id)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
		]);
	}

    /**
     * Lists all Places models.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'models' => Places::find()->orderBy('name')->all(),
        ]);
    }

	/**
	 * Lists all Places models.
	 * @return mixed
	 */
	public function actionArmmap()
	{
		return $this->render('armmap', [
			'models' => Places::find()
				->leftJoin('techs','`techs`.`places_id` = `places`.`id` and `techs`.`arms_id` is NULL')
				->leftJoin('tech_models','`tech_models`.`id` = `techs`.`model_id`')
				->leftJoin('tech_types','`tech_types`.`id` = `tech_models`.`type_id`')
				->joinWith([
					'arms.user',
					'arms.techs',
					'arms.state',
					'arms.comp.domain',
					'arms.comps',
					'arms.techModel',
					'arms.licKeys',
					'arms.licItems',
					'arms.licGroups',
					'arms.contracts'
					//'techs.model.type'
					])->orderBy('short')
				->all(),
		]);
	}

    /**
     * Displays a single Places model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
	        'models' => Places::find()
		        ->leftJoin('techs','`techs`.`places_id` = `places`.`id` and `techs`.`arms_id` is NULL')
		        ->leftJoin('tech_models','`tech_models`.`id` = `techs`.`model_id`')
		        ->leftJoin('tech_types','`tech_types`.`id` = `tech_models`.`type_id`')
		        ->joinWith([
			        'arms.user',
			        'arms.techs',
			        'arms.state',
			        'arms.comp.domain',
			        'arms.comps',
			        'arms.techModel',
			        'arms.licKeys',
			        'arms.licItems',
			        'arms.licGroups',
			        //'techs.model.type'
		        ])->orderBy('short')
		        ->all(),
        ]);
    }

    /**
     * Creates a new Places model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Places();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['armmap']);
        }

        if ($parent_id=Yii::$app->request->get('parent_id')) $model->parent_id=$parent_id;

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Places model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['armmap']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Places model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Places model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Places the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Places::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
