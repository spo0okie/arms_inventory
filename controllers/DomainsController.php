<?php

namespace app\controllers;

use Yii;
use app\models\Domains;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * DomainsController implements the CRUD actions for Domains model.
 */
class DomainsController extends ArmsBaseController
{
	public $modelClass=Domains::class;
}
