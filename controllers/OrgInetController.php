<?php

namespace app\controllers;

use app\models\OrgPhones;
use Yii;
use app\models\OrgInet;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * OrgInetController implements the CRUD actions for OrgInet model.
 */
class OrgInetController extends ArmsBaseController
{
	public $modelClass=OrgInet::class;
}
