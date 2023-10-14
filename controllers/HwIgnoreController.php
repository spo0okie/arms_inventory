<?php

namespace app\controllers;

use Yii;
use app\models\HwIgnore;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * HwIgnoreController implements the CRUD actions for HwIgnore model.
 */
class HwIgnoreController extends ArmsBaseController
{
	public $modelClass=HwIgnore::class;
}
