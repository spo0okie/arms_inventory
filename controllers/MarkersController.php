<?php

namespace app\controllers;

use app\models\Markers;

/**
 * MarkersController реализует CRUD операции для модели Markers
 */
class MarkersController extends ArmsBaseController
{
	/**
	 * @var string Класс модели для CRUD операций
	 */
	public $modelClass = Markers::class;
}
