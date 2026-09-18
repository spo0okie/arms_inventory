<?php

namespace app\controllers;

use app\models\DnsNames;
use yii\web\NotFoundHttpException;

/**
 * DnsNamesController реализует CRUD операции для модели DnsNames (DNS-имена в зонах).
 */
class DnsNamesController extends ArmsBaseController
{
	/**
	 * @var string Класс модели для CRUD операций
	 */
	public $modelClass = DnsNames::class;

	/**
	 * item-by-name работает по полному имени: колонки name у таблицы нет,
	 * DnsNames::findByName раскладывает FQDN на зону и имя в зоне.
	 * @param string $name
	 * @return DnsNames
	 * @throws NotFoundHttpException
	 */
	protected function findByName(string $name)
	{
		if (($model = DnsNames::findByName($name)) !== null) {
			return $model;
		}
		throw new NotFoundHttpException('Object with requested name does not exist.');
	}
}
