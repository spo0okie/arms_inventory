<?php

namespace app\controllers;

use Yii;
use app\models\MaterialsTypes;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * MaterialsTypesController implements the CRUD actions for MaterialsTypes model.
 */
class MaterialsTypesController extends ArmsBaseController
{
	public $modelClass=MaterialsTypes::class;
}
