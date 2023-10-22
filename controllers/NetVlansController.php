<?php

namespace app\controllers;

use Yii;
use app\models\NetVlans;
use app\models\NetVlansSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;

/**
 * NetVlansController implements the CRUD actions for NetVlans model.
 */
class NetVlansController extends ArmsBaseController
{
	public $modelClass=NetVlans::class;
}
