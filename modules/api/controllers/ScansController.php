<?php

namespace app\modules\api\controllers;

use app\models\Scans;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;


class ScansController extends BaseRestController
{

	public $modelClass='app\models\Scans';

	/**
	 * Поля поиска (search/filter). SAPsync тянет фото сотрудника через
	 * GET /api/scans?users_id=… и выбирает портрет как последний по fileDate
	 * (отдельного флага портрета нет). Остальные владельцы — по образцу.
	 * @var array
	 */
	public static array $searchFields=[
		'id',
		'users_id' => 'users_id',
		'contracts_id' => 'contracts_id',
		'techs_id' => 'techs_id',
		'arms_id' => 'arms_id',
	];


	/**
	 * Принимает загруженный файл скана и сохраняет его в БД и на диск.
	 * Ожидает multipart/form-data с файлом scanFile и опциональными contracts_id/users_id.
	 * Последовательно выполняет валидацию, загрузку файла (upload()) и сохранение модели.
	 * При ошибке на любом этапе возвращает массив ошибок или JSON-строку с ошибкой.
	 * При успехе возвращает созданную модель Scans.
	 *
	 * POST-параметры:
	 *   scanFile      — файл скана (multipart upload)
	 *   contracts_id  — int, ID контракта для привязки (опционально)
	 *   users_id      — int, ID сотрудника для привязки фото (опционально)
	 *
	 * @return mixed  Модель Scans при успехе, массив ошибок или строка с ошибкой при неудаче
	 */
	public function actionUpload()
	{
		$model = new Scans();
		$model->scanFile = UploadedFile::getInstanceByName('scanFile');
		$model->contracts_id = Yii::$app->request->post('contracts_id');
		$model->users_id = Yii::$app->request->post('users_id');
		if (!$model->validate()) return $model->getErrors();
		if (!$model->upload()) return '{"error":"upload err"}';
		if (!$model->save(false)) return '{"error":"model saving error"}';
		return $model;
	}
	
	/**
	 * Отдаёт файл скана клиенту по его ID.
	 * Ищет запись Scans по `id`, проверяет наличие файла на диске (fileExists).
	 * Если файл найден — отправляет его через Response::sendFile().
	 * Если файл не найден физически — возвращает false без исключения.
	 *
	 * GET-параметры:
	 * @param int $id  ID записи скана (Scans)
	 *
	 * @return mixed  Ответ с файлом или false если файл отсутствует на диске
	 * @throws NotFoundHttpException если запись скана не найдена в БД
	 */
	public function actionDownload($id) {
		$model = Scans::findOne($id);
		if (!is_object($model)) throw new NotFoundHttpException('Requested scan not found');
		
		if ($model->fileExists) {
			return Yii::$app->response->sendFile($model->fsFname, $model->name);
		}
		return false;
	}
}
