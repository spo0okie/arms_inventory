<?php

namespace app\controllers;

use Yii;
use app\models\TechStates;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TechStatesController implements the CRUD actions for TechStates model.
 */
class TechStatesController extends ArmsBaseController
{
	public $modelClass='\app\models\TechStates';
	public $defaultShowArchived=true;
}
