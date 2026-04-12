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
	 * Принимает загруженный файл скана и сохраняет его в БД и на диск.
	 * Ожидает multipart/form-data с файлом scanFile и опциональным contracts_id.
	 * Последовательно выполняет валидацию, загрузку файла (upload()) и сохранение модели.
	 * При ошибке на любом этапе возвращает массив ошибок или JSON-строку с ошибкой.
	 * При успехе возвращает созданную модель Scans.
	 *
	 * POST-параметры:
	 *   scanFile      — файл скана (multipart upload)
	 *   contracts_id  — int, ID контракта для привязки (опционально)
	 *
	 * @return mixed  Модель Scans при успехе, массив ошибок или строка с ошибкой при неудаче
	 */
	public function actionUpload()
	{
		$model = new Scans();
		$model->scanFile = UploadedFile::getInstanceByName('scanFile');
		$model->contracts_id = Yii::$app->request->post('contracts_id');
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
