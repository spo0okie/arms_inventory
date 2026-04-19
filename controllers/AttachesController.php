<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\Attaches;
use Throwable;
use Yii;
use yii\db\StaleObjectException;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * AttachesController implements the CRUD actions for Scans model.
 */
class AttachesController extends ArmsBaseController
{
	
	public $modelClass=Attaches::class;
	
	public function disabledActions()
	{
		return ['index','update','item','view','ttip','item-by-name',];
	}

    /**
     * Creates a new Scans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Attaches();

	    if ($model->load(Yii::$app->request->get())) {
		    $model->uploadedFile = UploadedFile::getInstance($model, 'uploadedFile');
		    if (is_object($model->uploadedFile) && $model->upload()) $model->save();
		    else Yii::$app->session->setFlash('error', 'Error uploading file');
	    }
		return $this->redirect(Url::previous());

    }


	/**
	 * Acceptance test data for Create.
	 *
	 * Что делает actionCreate:
	 * - создаёт новый экземпляр Attaches;
	 * - пытается заполнить его атрибуты из GET-параметров (`$model->load(request->get())`).
	 *   Нестандартно: данные формы грузятся из GET, а файл — из POST multipart;
	 * - если GET-параметры загружены, ищет файл через `UploadedFile::getInstance()`.
	 *   Если файл есть и `upload()` успешен — сохраняет модель; иначе ставит flash "Error uploading file";
	 * - в любом случае делает redirect на `Url::previous()` (HTTP 302).
	 *
	 * Что именно проверяем:
	 * 1) `'no params'` — GET без атрибутов Attaches. `$model->load()` возвращает false,
	 *    внутренний if пропускается, action выполняет только redirect. Ожидаемый код — 302.
	 *    Подтверждает, что action не падает на пустом запросе и корректно отрабатывает
	 *    «пустой путь» (без файла и без загрузки атрибутов).
	 * 2) `'attrs without file'` — GET с полем `Attaches[techs_id]=<id>`, но без multipart-файла.
	 *    `$model->load()` возвращает true, `UploadedFile::getInstance()` вернёт null,
	 *    action ставит flash-ошибку и делает redirect. Ожидаемый код — 302.
	 *    Дополнительно ассертом проверяем, что запись Attaches НЕ создана в БД
	 *    (upload без файла не должен материализовать модель).
	 *
	 * Почему не проверяем успешную загрузку файла:
	 * - `saveAs()` пишет по пути `$_SERVER['DOCUMENT_ROOT'].'/web/scans/...'` на диск,
	 *   что требует корректно настроенного DOCUMENT_ROOT и прав записи в acceptance-окружении.
	 *   Это перенесено в сценарий отдельного upload-теста, если понадобится.
	 */
	public function testCreate(): array
	{
		$techs = \app\generation\ModelFactory::create(\app\models\Techs::class, ['empty' => true]);

		return [
			[
				'name' => 'no params',
				'GET' => [],
				'response' => 302,
			],
			[
				'name' => 'attrs without file',
				'GET' => ['Attaches' => ['techs_id' => $techs->id]],
				'response' => 302,
				'assert' => static function () use ($techs) {
					// Без multipart-файла ветка `save()` в actionCreate не выполняется —
					// НИ одной новой Attaches с только что сгенерированным techs_id не должно быть.
					$created = Attaches::find()->where(['techs_id' => $techs->id])->count();
					\PHPUnit\Framework\Assert::assertSame(
						0,
						(int)$created,
						'Attaches::create must not persist a record when the multipart file is missing'
					);
				},
			],
		];
	}
/**
	 * Deletes an existing Scans model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	//if (is_null($id)) $id=Yii::$app->request->post('id');
    	$model=$this->findModel($id);
        $model->delete();

        if (file_exists($_SERVER['DOCUMENT_ROOT'].$model->fullFname))
            unlink($_SERVER['DOCUMENT_ROOT'].$model->fullFname);
	
		return $this->redirect(Url::previous());
    }


	/**
	 * Acceptance test data for Delete.
	 *
	 * Что делает actionDelete:
	 * - ищет Attaches по GET `id` через findModel() (404 если не найден);
	 * - выполняет `$model->delete()` — физически удаляет строку в БД;
	 * - если файл скана существует по пути `$_SERVER['DOCUMENT_ROOT'].fullFname`,
	 *   удаляет его через `unlink()`; если файла нет, ветка молча пропускается;
	 * - возвращает redirect на `Url::previous()` (HTTP 302).
	 *
	 * Важно про HTTP-метод:
	 * - `actionDelete` в ArmsBaseController ограничен VerbFilter'ом только POST-запросами
	 *   (`'delete' => ['POST']`). Поэтому сценарии используют POST (пустой массив
	 *   активирует POST-путь в PageAccessCest), `id` по-прежнему передаётся в GET-параметрах
	 *   маршрута.
	 *
	 * Почему не требуется файловая фикстура:
	 * - action использует `file_exists()` как защиту перед unlink, поэтому отсутствие
	 *   физического файла не делает тест нестабильным: удаление записи в БД
	 *   выполняется в любом случае;
	 * - для создания Attaches с гарантированно отсутствующим файлом на диске достаточно
	 *   `ModelFactory::create(Attaches::class, ['empty' => true])` — генератор заполнит
	 *   `filename` случайной строкой, файл которой на диске не существует.
	 *
	 * Что именно проверяем:
	 * 1) `'delete existing'` — POST /attaches/delete?id={existing}. Ожидаемый код — 302
	 *    (redirect). В ассерте подтверждаем, что `Attaches::findOne($id)` после вызова
	 *    возвращает null — то есть запись реально удалена.
	 * 2) `'delete missing'` — POST с заведомо несуществующим id. Ожидаемый код — 404
	 *    (NotFoundHttpException из findModel() до попадания в delete).
	 */
	public function testDelete(): array
	{
		// empty=false гарантирует сгенерированное имя файла.
		// empty=true оставляет filename пустым, а shared fullFname тогда совпадает с директорией
		// /web/scans/, которую actionDelete по ошибке пытается unlink (это приведёт к warning).
		// Задаём явный filename вида "acceptance-delete-<unique>.bin" — физически на диске его нет,
		// поэтому условная ветка unlink не срабатывает.
		$target = \app\generation\ModelFactory::create(Attaches::class, [
			'empty' => true,
			'overrides' => ['filename' => 'acceptance-delete-' . uniqid() . '.bin'],
		]);
		$missingId = (int)(Attaches::find()->max('id')) + 1000;

		return [
			[
				'name' => 'delete existing',
				'GET' => ['id' => $target->id],
				'POST' => [],
				'response' => 302,
				'assert' => static function () use ($target) {
					\PHPUnit\Framework\Assert::assertNull(
						Attaches::findOne($target->id),
						'Attaches::delete must remove the row from db after successful action'
					);
				},
			],
			[
				'name' => 'delete missing',
				'GET' => ['id' => $missingId],
				'POST' => [],
				'response' => 404,
			],
		];
	}
/**
     * Finds the Scans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return Attaches the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id)
    {
        if (($model = Attaches::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
