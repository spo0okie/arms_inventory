<?php

namespace app\controllers;

use app\models\Users;
use Exception;
use Throwable;
use Yii;
use app\models\Contracts;
use yii\db\StaleObjectException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;


/**
 * ContractsController implements the CRUD actions for Contracts model.
 */
class ContractsController extends ArmsBaseController
{
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['hint-arms','hint-parent','scans'],
			'edit'=>['update-form','unlink','link','link-tech','scan-upload']
		]);
	}
	
	
	
	/**
	 * Возвращает HTML-подсказку со списком ARM-объектов для выбора в форме.
	 *
	 * Используется в autocomplete/hint-виджетах форм. Делегирует выборку
	 * методу Contracts::fetchArmsHint() и возвращает сырой HTML через formatter.
	 *
	 * GET-параметры:
	 * @param mixed $ids  Список идентификаторов ARM-объектов через запятую
	 * @param mixed $form Имя формы, для которой формируется подсказка
	 *
	 * @return mixed Сырой HTML-ответ
	 */
	public function actionHintArms($ids,$form)
	{
		return Yii::$app->formatter->asRaw(Contracts::fetchArmsHint($ids,$form));
	}
	
	/**
	 * Тестовые данные приёмочного теста для actionHintArms.
	 *
	 * Тест передаёт ids=7 и form=test, ожидает HTTP 200.
	 * Внимание: тест завязан на конкретный id=7, который должен существовать
	 * в БД. Для более надёжного теста следует заменить захардкоженный id=7
	 * на id записи, созданной через getTestData().
	 *
	 * @return array массив тестовых сценариев
	 */
	public function testHintArms(): array
	{
		return [[
			'name' => 'default',
			'GET' => ['ids' => '7', 'form' => 'test'],
			'response' => 200,
		]];
	}
	/**
	 * Возвращает HTML-подсказку со списком родительских договоров для выбора в форме.
	 *
	 * Используется в autocomplete/hint-виджетах форм при выборе
	 * родительского договора. Делегирует выборку Contracts::fetchParentHint()
	 * и возвращает сырой HTML.
	 *
	 * GET-параметры:
	 * @param mixed $ids  Список идентификаторов договоров через запятую
	 * @param mixed $form Имя формы, для которой формируется подсказка
	 *
	 * @return mixed Сырой HTML-ответ
	 */
	public function actionHintParent($ids,$form)
	{
		return Yii::$app->formatter->asRaw(Contracts::fetchParentHint($ids,$form));
	}
	
	/**
	 * Тестовые данные приёмочного теста для actionHintParent.
	 *
	 * Аналогично testHintArms: тест завязан на конкретный id=7.
	 * Для устойчивого теста рекомендуется заменить ids=7 на идентификатор
	 * договора, созданного через getTestData().
	 *
	 * @return array массив тестовых сценариев
	 */
	public function testHintParent(): array
	{
		return [[
			'name' => 'default',
			'GET' => ['ids' => '7', 'form' => 'test'],
			'response' => 200,
		]];
	}
	
	/**
	 * Отображает список сканов (вложений) для заданного договора.
	 *
	 * Рендерит представление scans через renderAjax — используется
	 * для отображения в модальных окнах и AJAX-блоках карточки договора.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор договора (contracts.id)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если договор с заданным id не найден
	 */
	public function actionScans(int $id)
	{
		return $this->renderAjax('scans', [
			'model' => $this->findModel($id),
		]);
	}
	
		
	/**
	 * Тестовые данные приёмочного теста для actionScans.
	 *
	 * Тест пропущен (skip): для проверки необходим договор с прикреплёнными
	 * сканами. Нужно создать запись Contracts и связанные с ней записи сканов
	 * (ContractScans), передать id договора, затем проверить наличие сканов
	 * в возвращаемом HTML.
	 *
	 * @return array сценарий skip
	 */
	public function testScans(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Создаёт новый договор (Contracts).
	 *
	 * Поддерживает три режима работы:
	 * - Обычный POST: сохраняет модель, перенаправляет на view или update (если GET apply).
	 * - AJAX POST: возвращает JSON с результатом валидации или успехом.
	 * - AJAX GET: рендерит форму создания через renderAjax для модального окна.
	 *
	 * GET-параметры:
	 *   - apply      mixed  Если передан — после сохранения перенаправляет на update вместо view
	 *   - Contracts[*]     Поля для предзаполнения формы (родительский договор и т.д.)
	 *
	 * POST-параметры:
	 *   - Contracts[*]     Поля модели Contracts для сохранения
	 *
	 * @return mixed Редирект, JSON-ответ или рендер формы создания
	 */
    public function actionCreate()
    {
	    $model = new Contracts();
	
		//передали родительский документ
		$model->load(Yii::$app->request->get());
	
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return ['error'=>'OK','code'=>0,'model'=>$model];
			} else {
				return $this->redirect([Yii::$app->request->get('apply')?'update':'view', 'id' => $model->id]);
			}
		} elseif (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$result = [];
			foreach ($model->getErrors() as $attribute => $errors) {
				$result[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
			}
			return ['error'=>'ERROR','code'=>1,'validation'=>$result];
			
		} elseif (Yii::$app->request->isAjax) {
			return $this->renderAjax('create', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]);
		}
	
	
		return $this->render('create', [
			'model' => $model,
		]);


    }


	/**
	 * Редактирует существующий договор (Contracts).
	 *
	 * Поддерживает три режима работы:
	 * - AJAX POST: сохраняет и возвращает JSON (успех или ошибки валидации).
	 * - AJAX GET: рендерит форму редактирования для модального окна.
	 * - Обычный POST: сохраняет, перенаправляет на view или update (если GET apply).
	 *
	 * GET-параметры:
	 * @param int  $id    Идентификатор договора (contracts.id)
	 *
	 * Дополнительный GET-параметр:
	 *   - apply  mixed  Если передан — после сохранения перенаправляет на update вместо view
	 *
	 * POST-параметры:
	 *   - Contracts[*]  Поля модели Contracts для обновления
	 *
	 * @return mixed Редирект, JSON-ответ или рендер формы редактирования
	 * @throws NotFoundHttpException если договор с заданным id не найден
	 */
	public function actionUpdate(int $id)
	{
		$model = $this->findModel($id);

		//обработка аякс запросов
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;

			if ($model->load(Yii::$app->request->post()) && $model->save()) {
				return ['error'=>'OK','code'=>0,'model'=>$model];
			} elseif(Yii::$app->request->isPost) {
				$result = [];
				foreach ($model->getErrors() as $attribute => $errors) {
					$result[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
				}
				return ['error'=>'ERROR','code'=>1,'validation'=>$result];
			}
			return $this->renderAjax('update', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]);
		}

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect([Yii::$app->request->get('apply')?'update':'view', 'id' => $model->id]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}

	/**
	 * Рендерит форму редактирования договора для использования в AJAX/модальном контексте.
	 *
	 * Возвращает только разметку формы (_form) без layout через renderAjax.
	 * Используется при динамической загрузке формы в модальное окно.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор договора (contracts.id)
	 *
	 * @return mixed HTML-разметка формы
	 * @throws NotFoundHttpException если договор с заданным id не найден
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function actionUpdateForm(int $id)
	{
		return $this->renderAjax('_form', [
			'model' => $this->findModel($id),
		]);
	}

		
	/**
	 * Тестовые данные приёмочного теста для actionUpdateForm.
	 *
	 * Тест пропущен (skip): действие рендерит форму через renderAjax и
	 * требует корректного AJAX-контекста с заголовком X-Requested-With.
	 * Для включения теста необходим существующий договор и AJAX-запрос.
	 * Рекомендуется создать запись Contracts через getTestData() и
	 * передать её id с AJAX-заголовками.
	 *
	 * @return array сценарий skip
	 */
	public function testUpdateForm(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Удаляет существующий договор (только для администраторов).
	 *
	 * Перед удалением каскадно удаляет все прикреплённые сканы договора.
	 * Доступно только для пользователей с правами admin (Users::isAdmin()).
	 * После удаления перенаправляет на страницу списка договоров.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор договора для удаления (contracts.id)
	 *
	 * POST-параметры: отсутствуют.
	 *
	 * @return mixed Перенаправление на index
	 * @throws NotFoundHttpException если договор с заданным id не найден
	 * @throws ForbiddenHttpException если текущий пользователь не admin
	 * @throws Exception
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
	    if (!Users::isAdmin()) {throw new  ForbiddenHttpException('Access denied');}
	
	    $model=$this->findModel($id);

    	//ищем и удаляем все привязанные сканы
    	$scans=$model->scans;
    	if (is_array($scans) && count($scans)) {
    		foreach ($scans as $scan) {
    			$scan->delete();
		    }
	    }
        $this->findModel($id)->delete();


        return $this->redirect(['index']);
    }

	/**
	 * Загружает скан (файл-вложение) и прикрепляет его к договору.
	 *
	 * Принимает multipart POST-запрос с файлом и идентификатором договора.
	 * Если contracts_id не передан — возвращает JSON с ошибкой (договор
	 * ещё не сохранён). Примечание: текущая реализация является заглушкой,
	 * фактическое сохранение файла не реализовано.
	 *
	 * POST-параметры:
	 *   - contracts_id  int   Идентификатор договора (contracts.id)
	 *   - file          file  Загружаемый файл скана (multipart/form-data)
	 *
	 * @return string JSON-строка с результатом операции
	 */
	public function actionScanUpload()
	{
		$id=Yii::$app->request->post('contracts_id');
		if (is_null($id))
			return "{\"error\":\"Невозможно прикрепить сканы к еще не созданному документу. Нажмите сначала кнопку &quot;Применить&quot;\"}";
		else
			return "{\"error\":\"Якобы сохранено в модель $id\"}";	}

		
	/**
	 * Тестовые данные приёмочного теста для actionScanUpload.
	 *
	 * Тест пропущен (skip): действие требует multipart/form-data POST-запроса
	 * с файлом. Стандартный HTTP-тест фреймворка не поддерживает загрузку файлов
	 * без специальной настройки. Для включения теста необходимо реализовать
	 * передачу UploadedFile через фиктивный файловый массив в $_FILES и
	 * создать договор через getTestData() для получения корректного contracts_id.
	 *
	 * @return array сценарий skip
	 */
	public function testScanUpload(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'POST'     => ['contracts_id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Отвязывает связанный объект от договора.
	 *
	 * Убирает model_id из many-to-many поля договора, указанного через $link.
	 * Возвращает JSON с кодом результата: 0 — успешно отвязано, 1 — ошибка
	 * сохранения, 2 — связь не найдена.
	 *
	 * GET-параметры:
	 * @param int    $id       Идентификатор договора (contracts.id)
	 * @param int    $model_id Идентификатор объекта, который нужно отвязать
	 * @param string $link     Имя поля-связи в модели Contracts (например, 'techs_ids')
	 *
	 * @return mixed JSON-ответ с полями error, code, Message
	 * @throws NotFoundHttpException если договор с заданным id не найден
	 */
	public function actionUnlink(int $id, int $model_id, string $link)
	{
		//признак что документ был связан с объектом
		$usage=false;
		//признак что был отвязан в результате
		$usage_deleted=false;

		$contract=$this->findModel($id);
		$model_ids=$contract->$link;
		
		if (array_search($model_id,$model_ids)!==false) {
			$usage=true;
			$contract->$link=array_diff($model_ids,[$model_id]);
			if ($contract->save()) $usage_deleted=true;
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		if ($usage) {
			if ($usage_deleted) {
				return ['error'=>'OK','code'=>'0','Message'=>'Usage removed'];
			} else {
				return ['error'=>'ERROR','code'=>'1','Message'=>'Link removing error'];
			}
		} else {
			return ['error'=>'OK','code'=>'2','Message'=>'Requested usage not found ['.implode(',',$model_ids).']'];
		}
	}
	
		
	/**
	 * Тестовые данные приёмочного теста для actionUnlink.
	 *
	 * Тест пропущен (skip): для проверки необходимы два связанных объекта —
	 * договор (Contracts) и связанная с ним запись (например, Techs).
	 * Нужно создать договор с предзаполненным полем $link (например, techs_ids),
	 * передать id договора, id связанного объекта и имя поля, затем проверить,
	 * что model_id исчез из поля $link после запроса.
	 *
	 * @return array сценарий skip
	 */
	public function testUnlink(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id, 'model_id' => 0, 'link' => 'techs_ids'],
			'response' => 200,
		]];
	}
	public $modelClass='\app\models\Contracts';
	/**
	 * Привязывает объект к договору через указанное поле-связь.
	 *
	 * Добавляет model_id в many-to-many поле договора $link, если он
	 * там ещё не присутствует. Возвращает JSON с кодом 0 и сообщением 'Added'.
	 *
	 * GET-параметры:
	 * @param int    $id       Идентификатор договора (contracts.id)
	 * @param int    $model_id Идентификатор объекта для привязки
	 * @param string $link     Имя поля-связи в модели Contracts (например, 'arms_ids')
	 *
	 * @return mixed JSON-ответ с полями error, code, Message
	 * @throws NotFoundHttpException если договор с заданным id не найден
	 */
	public function actionLink(int $id, int $model_id, string $link)
	{
		
		$contract=$this->findModel($id);
		$model_ids=$contract->$link;
		if (array_search($model_id,$model_ids)===false) {
			$model_ids[]=$model_id;
			$contract->$link=$model_ids;
			$contract->save();
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		return ['error'=>'OK','code'=>'0','Message'=>'Added'];

	}

		
	/**
	 * Тестовые данные приёмочного теста для actionLink.
	 *
	 * Тест пропущен (skip): для проверки необходимо создать договор (Contracts)
	 * и объект для привязки. Нужно передать id договора, id объекта и имя
	 * поля $link, затем проверить, что model_id появился в соответствующем
	 * поле договора. Создать тестовые данные можно через getTestData().
	 *
	 * @return array сценарий skip
	 */
	public function testLink(): array
	{
		$testData = $this->getTestData();
		$tech = \app\generation\ModelFactory::create(\app\models\Techs::class, ['empty' => true]);
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id, 'model_id' => $tech->id, 'link' => 'techs_ids'],
			'response' => 200,
		]];
	}
	/**
	 * Привязывает оборудование (Techs) к договору.
	 *
	 * Добавляет techs_id в поле techs_ids модели Contracts, если он
	 * там ещё не присутствует. Специализированная версия actionLink
	 * для связи с оборудованием. Возвращает JSON с кодом 0 и сообщением 'Added'.
	 *
	 * GET-параметры:
	 * @param int $id       Идентификатор договора (contracts.id)
	 * @param int $techs_id Идентификатор оборудования (techs.id) для привязки
	 *
	 * @return mixed JSON-ответ с полями error, code, Message
	 * @throws NotFoundHttpException если договор с заданным id не найден
	 */
	public function actionLinkTech(int $id, int $techs_id)
	{
		$model=$this->findModel($id);
		$techs_ids=$model->techs_ids;
		if (array_search($techs_id,$techs_ids)===false) {
			$techs_ids[]=$techs_id;
			$model->techs_ids=$techs_ids;
			$model->save();
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		return ['error'=>'OK','code'=>'0','Message'=>'Added'];

	}

		
	/**
	 * Тестовые данные приёмочного теста для actionLinkTech.
	 *
	 * Тест пропущен (skip): для проверки необходимо создать договор (Contracts)
	 * и запись оборудования (Techs). Нужно передать id договора и techs_id,
	 * затем проверить, что techs_id появился в поле techs_ids договора.
	 * Оба объекта можно создать через getTestData().
	 *
	 * @return array сценарий skip
	 */
	public function testLinkTech(): array
	{
		$testData = $this->getTestData();
		$tech = \app\generation\ModelFactory::create(\app\models\Techs::class, ['empty' => true]);
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id, 'techs_id' => $tech->id],
			'response' => 200,
		]];
	}
/**
     * Finds the Contracts model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return Contracts the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id)
    {
        if (($model = Contracts::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
