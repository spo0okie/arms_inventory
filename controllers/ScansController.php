<?php

namespace app\controllers;

use app\generation\ModelFactory;
use app\models\Places;
use app\models\Soft;
use app\models\TechModels;
use app\models\Techs;
use PHPUnit\Framework\Assert;
use Throwable;
use Yii;
use app\models\Scans;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap5\ActiveForm;

/**
 * ScansController implements the CRUD actions for Scans model.
 *
 * Управляет отсканированными документами и изображениями (Scans).
 * Поддерживает загрузку файлов, установку thumbnail для связанных объектов
 * (TechModels, Techs, Places, Soft) и отвязку скана от всех объектов вместо удаления.
 * Действия item-by-name и update отключены (см. disabledActions).
 */
class ScansController extends ArmsBaseController
{
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['thumb'],
		]);
	}
	
	public function disabledActions()
	{
		return ['item-by-name','update'];
	}

	/**
	 * AJAX-валидация формы скана (ActiveForm::validate).
	 *
	 * Загружает данные из POST в модель Scans (существующую или новую),
	 * применяет UploadedFile для поля scanFile и возвращает JSON с ошибками валидации.
	 *
	 * GET-параметры:
	 * - id (int|null, опционально): ID существующего скана для валидации обновления;
	 *   если не указан — валидируется новая запись.
	 *
	 * POST-параметры (через Scans::load):
	 * - Scans[scanFile] (file, опционально): загружаемый файл скана
	 * - прочие поля модели Scans
	 *
	 * @param int|null $id GET: ID скана (null = новый)
	 * @return mixed JSON с ошибками валидации или null
	 * @throws NotFoundHttpException если $id указан, но запись не найдена
	 */
	public function actionValidate($id=null)
	{
		if (!is_null($id))
			$model = $this->findModel($id);
		else
			$model = new Scans();

		if ($model->load(Yii::$app->request->post())) {
			$model->scanFile = UploadedFile::getInstance($model, 'scanFile');
			Yii::$app->response->format = Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
		
		return null;
	}

    /**
     * Загружает новый скан и сохраняет его в БД.
     *
     * Принимает multipart POST-запрос, валидирует файл и поля формы.
     * При ошибках возвращает JSON-объект с полем 'error' и 'validation'.
     * При успехе возвращает JSON-массив с сохранённой моделью Scans.
     *
     * POST-параметры (через Scans::load, scenario='create'):
     * - Scans[scanFile]      (file, обязательно): загружаемый файл скана
     * - Scans[contracts_id]  (int, опционально):  ID договора
     * - Scans[places_id]     (int, опционально):  ID помещения
     * - Scans[techs_id]      (int, опционально):  ID оборудования
     * - прочие поля модели Scans
     *
     * Ответ:
     * - JSON-массив [Scans] при успехе
     * - JSON-объект {'error': string, 'validation': {...}} при ошибке валидации
     * - строка '{"error":"..."}' при иных ошибках
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Scans(['scenario' => 'create']);

	    if ($model->load(Yii::$app->request->post())) {
		    $model->scanFile = UploadedFile::getInstance($model, 'scanFile');
		    if (!$model->validate()) {
		    	$errors=[];
				$flattenedErrors=[];
			    foreach ($model->getErrors() as $attribute => $error) {
				    $errors[yii\helpers\Html::getInputId($model, $attribute)] = $error;
					$flattenedErrors=array_merge($flattenedErrors,$error);
			    }
			    Yii::$app->response->format = Response::FORMAT_JSON;
			    return (object)[
			    	'error'=>'не прошло валидацию: '.implode(';',array_values($flattenedErrors)),
				    'validation'=>$errors
			    ];

		    }
		    if (!$model->upload()) return "{\"error\":\"не удалось загрузить\"}";
		    if ($model->save(false)) {
			    Yii::$app->response->format = Response::FORMAT_JSON;
			    return [$model];
		    }
		    return '{"error":"ошибка сохранения модели"}';
	    }
	    return '{"error":"ошибка получения данных"}';

    }
	
	/**
	 * Заменяет файл существующего скана.
	 *
	 * Загружает новый файл через UploadedFile и сохраняет модель.
	 * При успехе редиректит на страницу просмотра скана.
	 *
	 * GET-параметры:
	 * - id (int, обязательно): ID скана для замены файла
	 *
	 * POST-параметры (через Scans::load):
	 * - Scans[scanFile] (file, обязательно): новый файл скана
	 * - прочие поля модели Scans
	 *
	 * @param int $id GET: ID скана
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate(int $id)
	{
		/** @var Scans $model */
		$model = $this->findModel($id);
		
		if ($model->load(Yii::$app->request->post())) {
			$model->scanFile = UploadedFile::getInstance($model, 'scanFile');
			if ($model->upload()&&$model->save())
				return $this->redirect(['view', 'id' => $model->id]);
		}
		
		return $this->render('update', [
			'model' => $model,
		]);
	}
	
	/**
	 * Устанавливает скан как thumbnail (превью) для указанного объекта.
	 *
	 * Находит объект по типу связи ($link) и ID ($link_id), записывает
	 * ID скана в поле scans_id объекта и сохраняет без валидации.
	 * Возвращает JSON {'code': '0'} при успехе.
	 *
	 * GET-параметры:
	 * - id      (int, обязательно):    ID скана (Scans)
	 * - link    (string, обязательно): тип связи:
	 *                                  'tech_models_id' — TechModels,
	 *                                  'techs_id'       — Techs,
	 *                                  'places_id'      — Places,
	 *                                  'soft_id'        — Soft
	 * - link_id (int, обязательно):    ID связанного объекта
	 *
	 * @param int    $id      GET: ID скана
	 * @param string $link    GET: тип связи (tech_models_id|techs_id|places_id|soft_id)
	 * @param int    $link_id GET: ID связанного объекта
	 * @return mixed JSON {'code': '0'}
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionThumb(int $id, string $link, int $link_id)
	{
		switch ($link) {
			case 'tech_models_id':
				$model = TechModels::findOne($link_id);
				break;
			case 'techs_id':
				$model = Techs::findOne($link_id);
				break;
			case 'places_id':
				$model = Places::findOne($link_id);
				break;
			case 'soft_id':
				$model = Soft::findOne($link_id);
				break;
			default:
				$model=null;
		}
		if ($model === null)
			throw new NotFoundHttpException('The requested page does not exist.');
		
		$model->scans_id=$id;
		$model->save(false);
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		return (object)['code'=>'0'];
	}
	
	
	/**
	 * Acceptance test data for actionThumb.
	 *
	 * Что делает тестируемый action `thumb`:
	 * - принимает GET-параметры `id` (Scans), `link` (имя FK-поля у целевой модели)
	 *   и `link_id` (идентификатор целевой модели);
	 * - по значению `$link` выбирает класс целевой модели:
	 *   `tech_models_id` → {@see TechModels}, `techs_id` → {@see Techs},
	 *   `places_id` → {@see Places}, `soft_id` → {@see Soft};
	 * - находит целевую модель через `findOne($link_id)`, записывает в её поле
	 *   `scans_id` значение `$id` и сохраняет без валидации (`save(false)`);
	 * - при неизвестном `$link` или отсутствии целевой модели бросает
	 *   {@see NotFoundHttpException} (HTTP 404);
	 * - при успехе возвращает JSON `{"code":"0"}` (HTTP 200).
	 *
	 * Физический файл скана action НЕ читает — меняется только FK в БД,
	 * поэтому для acceptance достаточно валидных моделей Scans/Techs/Places
	 * без реального файла на диске.
	 *
	 * Что именно проверяет каждый сценарий:
	 * 1) `techs link` — создаём Scans и Techs через ModelFactory, вызываем
	 *    `/scans/thumb?id=scan.id&link=techs_id&link_id=tech.id`, ожидаем HTTP 200.
	 *    В assert-колбэке перечитываем Techs из БД и проверяем, что `scans_id`
	 *    равен id созданного скана — то есть бизнес-эффект action действительно
	 *    зафиксирован в БД.
	 * 2) `places link` — аналогично для Places (второй класс target-моделей).
	 *    Подтверждает, что switch по `$link` корректно обрабатывает не только
	 *    Techs, но и другие ветки.
	 * 3) `invalid link` — передаём неизвестное значение `link=unknown_fk`.
	 *    Ожидаем HTTP 404: switch попадает в default, `$model=null`,
	 *    action бросает NotFoundHttpException.
	 * 4) `missing link_id` — передаём корректный `link=techs_id` и
	 *    несуществующий `link_id` (max(id) + 1000). `findOne()` возвращает null,
	 *    action бросает NotFoundHttpException, ожидаем HTTP 404.
	 *
	 * Почему этого достаточно для acceptance-контракта:
	 * - покрываются оба «успешных» ветвления switch (Techs, Places), что
	 *   даёт уверенность в стабильности остальных аналогичных веток
	 *   (TechModels, Soft) без дублирования сценариев;
	 * - покрыты оба пути ухода в 404 — неизвестный link и отсутствующий link_id;
	 * - assert-проверки через БД гарантируют, что 200-й код действительно
	 *   соответствует успешной записи FK, а не просто отсутствию исключений.
	 *
	 * @return array
	 */
	public function testThumb(): array
	{
		$scan = ModelFactory::create(Scans::class, ['empty' => true]);
		$tech = ModelFactory::create(Techs::class, ['empty' => true]);
		$place = ModelFactory::create(Places::class, ['empty' => true]);

		$scanId = (int)$scan->id;
		$techId = (int)$tech->id;
		$placeId = (int)$place->id;
		$missingId = (int)(Techs::find()->max('id')) + 1000;

		return [
			[
				'name' => 'techs link',
				'GET' => ['id' => $scanId, 'link' => 'techs_id', 'link_id' => $techId],
				'response' => 200,
				'assert' => static function () use ($scanId, $techId) {
					$reloaded = Techs::findOne($techId);
					Assert::assertNotNull($reloaded, 'Techs row must still exist after thumb');
					Assert::assertSame(
						$scanId,
						(int)$reloaded->scans_id,
						'thumb must persist scans_id on linked Techs row'
					);
				},
			],
			[
				'name' => 'places link',
				'GET' => ['id' => $scanId, 'link' => 'places_id', 'link_id' => $placeId],
				'response' => 200,
				'assert' => static function () use ($scanId, $placeId) {
					$reloaded = Places::findOne($placeId);
					Assert::assertNotNull($reloaded, 'Places row must still exist after thumb');
					Assert::assertSame(
						$scanId,
						(int)$reloaded->scans_id,
						'thumb must persist scans_id on linked Places row'
					);
				},
			],
			[
				'name' => 'invalid link',
				'GET' => ['id' => $scanId, 'link' => 'unknown_fk', 'link_id' => $techId],
				'response' => 404,
			],
			[
				'name' => 'missing link_id',
				'GET' => ['id' => $scanId, 'link' => 'techs_id', 'link_id' => $missingId],
				'response' => 404,
			],
		];
	}
	public $modelClass=Scans::class;
	/**
	 * Отвязывает скан от всех объектов (вместо физического удаления).
	 *
	 * Обнуляет все внешние ключи скана (contracts_id, places_id, tech_models_id,
	 * material_models_id, lic_types_id, lic_items_id, arms_id, techs_id, soft_id)
	 * и сохраняет модель. Физическое удаление файла намеренно отключено —
	 * "осиротевший" скан сохраняется для истории в журналах.
	 *
	 * При AJAX-запросе возвращает JSON {'code': '0'};
	 * иначе — редиректит на actionIndex.
	 *
	 * GET или POST-параметры:
	 * - id (int|null, опционально): ID скана;
	 *   если не передан в GET — берётся из POST['key'] (для AJAX-таблиц)
	 *
	 * @param int|null $id GET/POST: ID скана
	 * @return mixed JSON {'code': '0'} или редирект на index
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id=null)
    {
    	if (is_null($id)) $id=Yii::$app->request->post('key');
    	
    	/** @var Scans $model */
    	$model=$this->findModel($id);
    	$model->contracts_id=null;
		$model->places_id=null;
		$model->tech_models_id=null;
		$model->material_models_id=null;
		$model->lic_types_id=null;
		$model->lic_items_id=null;
		$model->arms_id=null;
		$model->techs_id=null;
		$model->soft_id=null;
        $model->save();

        //вместо удаления отвязываем ото всех и сохраняем. Осиротевший скан может понадобиться при работе с журналами
        //if (file_exists($_SERVER['DOCUMENT_ROOT'].$model->fullFname))
        //    unlink($_SERVER['DOCUMENT_ROOT'].$model->fullFname);

	    if (Yii::$app->request->isAjax) {
		    Yii::$app->response->format = Response::FORMAT_JSON;
		    return (object)['code'=>'0'];
	    }
	    return $this->redirect(['index']);
    }
    
}
