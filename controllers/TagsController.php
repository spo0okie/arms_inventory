<?php

namespace app\controllers;

use app\models\Tags;
use app\models\TagsSearch;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * TagsController реализует CRUD операции для модели Tags
 */
class TagsController extends ArmsBaseController
{
    /**
     * @var string Класс модели для CRUD операций
     */
    public $modelClass = Tags::class;
}