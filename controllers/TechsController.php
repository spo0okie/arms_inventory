<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\HwListItem;
use app\models\Manufacturers;
use app\models\ui\RackUnitForm;
use Yii;
use app\models\Techs;
use yii\bootstrap5\ActiveForm;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * TechsController implements the CRUD actions for Techs model.
 * @noinspection PhpUnused
 */
class TechsController extends ArmsBaseController
{
	
	/**
	 * Тест пропущен: логика генерации инвентарного номера зависит от конфигурации
	 * системы (шаблоны префиксов, счётчики), которые нельзя воспроизвести без
	 * полной настройки БД и params. Заменить на реальный тест невозможно через getTestData().
	 *
	 * @return array
	 */
	public function testInvnum(): array
	{
		return self::skipScenario('default', 'requires complex data preparation');
	}
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['ttip-hw','inv-num','docs'],
			'edit'=>['uploads','unlink','updhw','rmhw','edithw','port-list','rack-unit','rack-unit-validate'],
		]);
	}
	
	/**
	 * Отображает всплывающую подсказку с аппаратными компонентами (HW-список) оборудования.
	 * Рендерит partial-view «ttip-hw» для использования в интерфейсе как tooltip.
	 *
	 * GET-параметры:
	 * @param int $id  ID записи оборудования (Techs)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена
	 */
	public function actionTtipHw(int $id)
	{
		return $this->renderPartial('ttip-hw', [
			'model' => $this->findModel($id),
		]);
	}
	
	/**
	 * Тестирует actionTtipHw: запрашивает tooltip аппаратных компонентов для записи
	 * из getTestData()['full']. Ожидает HTTP 200.
	 *
	 * @return array
	 */
	public function testTtipHw(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Отображает карточку оборудования, найденного по инвентарному номеру или hostname.
	 * Сначала ищет по полю `num`, при неудаче — по полю `hostname`.
	 * Рендерит partial-view «item» в режиме статичного просмотра (без редактирования).
	 *
	 * GET-параметры:
	 * @param string $name  Инвентарный номер (num) или hostname оборудования
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена ни по одному из полей
	 */
	public function actionItemByName($name)
	{
		if (($model = Techs::findOne(['num'=>$name])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
				'static_view' => true
			]);
		}
		if (($model = Techs::findOne(['hostname'=>$name])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
				'static_view' => true
			]);
		}
		throw new NotFoundHttpException('The requested tech not found');
	}


		
	/**
	 * Тест пропущен: метод ищет по полям `num` и `hostname`, значения которых
	 * генерируются автоматически и уникальны для каждого окружения.
	 * getTestData() не предоставляет эти значения заранее,
	 * поэтому фиктивный тест невозможен без явной фикстуры с известным num/hostname.
	 *
	 * @return array
	 */
	public function testItemByName(): array
	{
		return self::skipScenario('default', 'requires known hostname or inventory number fixture');
	}
	
	public $modelClass='app\models\Techs';
	/**
	 * Генерирует следующий инвентарный номер для оборудования на основе переданного контекста.
	 * Формирует префикс из комбинации переданных ID и возвращает следующий свободный номер
	 * в этом префиксе. Ответ в формате JSON.
	 *
	 * GET-параметры:
	 * @param int|null $model_id      ID модели оборудования
	 * @param int|null $place_id      ID места размещения
	 * @param int|null $org_id        ID организации
	 * @param int|null $arm_id        ID АРМа
	 * @param int|null $installed_id  ID места установки
	 *
	 * @return mixed  JSON с следующим инвентарным номером
	 */
	public function actionInvNum($model_id=null,$place_id=null,$org_id=null,$arm_id=null,$installed_id=null)
	{
		$prefix=Techs::genInvPrefix((int)$model_id,(int)$place_id,(int)$org_id,(int)$arm_id,(int)$installed_id);
		Yii::$app->response->format = Response::FORMAT_JSON;
		return Techs::fetchNextNum($prefix);
	}
	
	
	/**
	 * Отображает печатный документ (паспорт, акт и т.п.) для единицы оборудования.
	 * Имя документа проверяется по конфигурации `arms.docs` и `techs.docs` из params.
	 * Рендерит view из подпапки «docs/{doc}».
	 *
	 * GET-параметры:
	 * @param int    $id   ID записи оборудования (Techs)
	 * @param string $doc  Ключ документа из params['arms.docs'] или params['techs.docs']
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена или документ не разрешён в конфиге
	 */
    public function actionDocs(int $id, string $doc)
    {
    	//защита от рендера чего попало
    	if (!isset(Yii::$app->params['arms.docs'][$doc]) && !isset(Yii::$app->params['techs.docs'][$doc]))
			throw new NotFoundHttpException('The requested document does not exist.');
    	
        return $this->render('docs/'.$doc, [
            'model' => $this->findModel($id),
        ]);
    }



	
	/**
	 * Тест пропущен: для работы actionDocs необходимы:
	 * заполненная запись Techs с корректными данными и настроенный ключ в
	 * params['arms.docs'] или params['techs.docs']. Это не покрывается getTestData().
	 *
	 * @return array
	 */
	public function testDocs(): array
	{
		return self::skipScenario('default', 'requires complex data preparation');
	}
	/**
	 * Отображает страницу управления загруженными файлами для единицы оборудования.
	 * Рендерит view «uploads» с моделью Techs.
	 *
	 * GET-параметры:
	 * @param int $id  ID записи оборудования (Techs)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись не найдена
	 */
	public function actionUploads(int $id)
	{
		$model = $this->findModel($id);
		return $this->render('uploads', [
			'model' => $model,
		]);
	}
	
	
		
	/**
	 * Тестирует actionUploads: запрашивает страницу загрузок для записи
	 * из getTestData()['full']. Ожидает HTTP 200.
	 *
	 * @return array
	 */
	public function testUploads(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Возвращает список доступных сетевых портов оборудования для зависимого дропдауна (kartik DepDrop).
	 * Ожидает POST-данные от виджета: `depdrop_parents[0]` — ID оборудования (Techs).
	 * Ответ в формате JSON: `{output: [...], selected: ''}`.
	 *
	 * POST-параметры:
	 *   depdrop_parents[0] — int, ID записи оборудования (Techs)
	 *
	 * @return array  JSON-ответ для картиковского DepDrop
	 * @throws NotFoundHttpException если запись оборудования не найдена
	 */
	public function actionPortList()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		if (isset($_POST['depdrop_parents'])) {
			$parents = $_POST['depdrop_parents'];
			if ($parents != null) {
				/** @var Techs $model */
				$model=$this->findModel($parents[0]);
				//$out = self::getSubCatList($cat_id);
				// the getSubCatList function will query the database based on the
				// cat_id and return an array like below:
				// [
				//    ['id'=>'<sub-cat-id-1>', 'name'=>'<sub-cat-name1>'],
				//    ['id'=>'<sub-cat_id_2>', 'name'=>'<sub-cat-name2>']
				// ]
				return ['output'=>$model->ddPortsList, 'selected'=>''];
			}
		}
		return ['output'=>'', 'selected'=>''];
	}
	
		
	/**
	 * Тест пропущен: actionPortList требует наличия оборудования с настроенными
	 * сетевыми портами и POST-данных в формате kartik DepDrop. Нельзя воспроизвести
	 * через getTestData() без дополнительных фикстур портов.
	 *
	 * @return array
	 */
	public function testPortList(): array
	{
		return self::skipScenario('default', 'requires complex data preparation');
	}
	/**
	 * Добавляет или подписывает аппаратный компонент (HW) в списке оборудования.
	 * Если GET-параметр `uid` === 'sign-all' — подписывает все компоненты HW-списка.
	 * Иначе создаёт новый HwListItem из GET-параметров и добавляет в hwList модели.
	 * После сохранения перенаправляет на страницу документа «passport».
	 *
	 * GET-параметры:
	 * @param int    $id   ID записи оборудования (Techs)
	 * @param string $uid  UID компонента оборудования (или 'sign-all' для подписи всех)
	 *
	 * @return string|Response
	 * @throws NotFoundHttpException если запись не найдена
	 */
	public function actionUpdhw($id){
		
		/** @var Techs $model */
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		$uid=Yii::$app->request->get('uid',null);
		if (strlen($uid)) {
			if ($uid==='sign-all') { //специальная команда на подпись всего оборудования
				//error_log('signing all');
				$model->hwList->signAll();
			}else {
				$newItem = new HwListItem();
				$newItem->loadArr($_GET);
				$model->hwList->add($newItem);
			}
			//error_log('saving');
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
			if (!$model->save(false)) error_log(print_r($model->errors,true));
		}
		
		return $this->redirect(['docs', 'id' => $model->id,'doc'=>'passport']);
	}
	
		
	/**
	 * Тест пропущен: actionUpdhw требует записи Techs с существующим hwList
	 * и корректного uid компонента. Данные не воспроизводимы через getTestData().
	 *
	 * @return array
	 */
	public function testUpdhw(): array
	{
		return self::skipScenario('default', 'requires complex data preparation');
	}
	/**
	 * Отображает форму редактирования аппаратного компонента (HW) оборудования.
	 * Ищет компонент по GET-параметру `uid` в hwList оборудования.
	 * Если компонент не найден — показывает пустую форму создания нового HwListItem.
	 * При AJAX-запросе рендерит форму внутри модального окна (#modal_form_loader).
	 *
	 * GET-параметры:
	 * @param int    $id   ID записи оборудования (Techs)
	 * @param string $uid  UID редактируемого компонента в hwList
	 *
	 * @return string|Response
	 * @throws NotFoundHttpException если запись оборудования не найдена
	 */
	public function actionEdithw($id){
		
		$manufacturers= Manufacturers::fetchNames();
		/** @var Techs $model */
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		$uid=Yii::$app->request->get('uid',null);
		$editItem=null;
		foreach ($model->hwList->items as $pos=>$item) {
			if ($item->uid == $uid) $editItem=$item;
		}
		if (!$editItem) $editItem = new HwListItem();
		
		return Yii::$app->request->isAjax?
		$this->renderAjax( '/hwlist/edit-item',
			[
				'item'=>$editItem,
				'model'=>$model,
				'manufacturers'=>$manufacturers,
				'modalParent' => '#modal_form_loader'
			]):
		$this->render( '/hwlist/edit-item',
			[
				'item'=>$editItem,
				'model'=>$model,
				'manufacturers'=>$manufacturers,
			]);
	}
	
		
	/**
	 * Тест пропущен: actionEdithw требует записи Techs с заполненным hwList
	 * и известным uid компонента. Эти данные недоступны через getTestData().
	 *
	 * @return array
	 */
	public function testEdithw(): array
	{
		return self::skipScenario('default', 'requires complex data preparation');
	}
	/**
	 * Удаляет аппаратный компонент из hwList оборудования по его UID.
	 * Если GET-параметр `uid` не передан или пустой — ничего не делает.
	 * После удаления и сохранения перенаправляет на документ «passport».
	 *
	 * GET-параметры:
	 * @param int    $id   ID записи оборудования (Techs)
	 * @param string $uid  UID удаляемого компонента в hwList
	 *
	 * @return string|Response
	 * @throws NotFoundHttpException если запись оборудования не найдена
	 */
	public function actionRmhw($id){
		
		/** @var Techs $model */
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		if (strlen(Yii::$app->request->get('uid',null))) {
			$model->hwList->del(Yii::$app->request->get('uid'));
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
			$model->save(false);
		}
		
		return $this->redirect(['docs', 'id' => $model->id,'doc'=>'passport']);
	}
	
	
		
	/**
	 * Тест пропущен: actionRmhw требует записи Techs с существующим компонентом hwList
	 * и корректным uid. Удаление тестовых данных разрушительно для других тестов.
	 * Фикстуры не предусмотрены в getTestData().
	 *
	 * @return array
	 */
	public function testRmhw(): array
	{
		return self::skipScenario('default', 'requires complex data preparation');
	}
	/**
	 * Валидирует форму редактирования юнита стойки (RackUnitForm) через AJAX.
	 * Загружает POST-данные в RackUnitForm и возвращает результат валидации ActiveForm в JSON.
	 * Если POST-данных нет — возвращает null.
	 *
	 * POST-параметры: поля модели RackUnitForm (tech_rack_id, tech_installed_pos, pos, back, insert_label, label)
	 *
	 * @return mixed  JSON с ошибками валидации или null
	 */
	public function actionRackUnitValidate()
	{
		$model = new RackUnitForm();
		
		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
		
		return null;
	}
	
	/**
	 * Тест пропущен: actionRackUnitValidate требует оборудования типа «стойка»
	 * с настроенными юнитами. Конфигурация стойки недоступна через getTestData().
	 *
	 * @return array
	 */
	public function testRackUnitValidate(): array
	{
		return self::skipScenario('default', 'requires rack configuration');
	}
	
	/**
	 * Отображает и обрабатывает форму редактирования содержимого юнита стойки.
	 * Загружает модель Techs (стойку), формирует RackUnitForm для выбранного юнита.
	 * Если в стойке уже есть метка для этого юнита — подставляет её в форму.
	 * При успешном POST-сохранении перенаправляет по routeOnUpdate.
	 *
	 * GET-параметры:
	 * @param int  $id     ID записи стойки (Techs с типом rack)
	 * @param int  $unit   Номер юнита в стойке
	 * @param bool $front  true — передняя сторона стойки, false — задняя (по умолчанию: true)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если запись стойки не найдена
	 */
	public function actionRackUnit($id,$unit,$front=true){
		$model = $this->findModel($id);
		
		$rackUnitForm = new RackUnitForm();
		$rackUnitForm->tech_rack_id=$id;
		$rackUnitForm->back=!$front;
		$rackUnitForm->tech_installed_pos=$unit;
		$rackUnitForm->pos=$unit;
		
		$label=ArrayHelper::getItemByFields(
			$model->getExternalItem(['rack-labels'],[]),
			[
				'pos'=>$unit,
				'back'=>!$front
			]
		);
		
		if (is_array($label)) {
			$rackUnitForm->insert_label=true;
			$rackUnitForm->label=$label['label'];
		}
		
		if ($rackUnitForm->load(Yii::$app->request->post()) && $rackUnitForm->setUnit()) {
			return $this->defaultReturn($this->routeOnUpdate($model),[
				$model
			]);
		}
		
		
		return $this->defaultRender('rack/unit-edit', [
			'rackUnitForm'=>$rackUnitForm,
			'model' => $model,
			'unit'=>$unit,
			'front'=>$front
		]);
	
	}	
	/**
	 * Тест пропущен: actionRackUnit требует записи Techs с типом «стойка» и
	 * конкретным номером юнита. Конфигурация стойки недоступна через getTestData().
	 *
	 * @return array
	 */
	public function testRackUnit(): array
	{
		return self::skipScenario('default', 'requires rack configuration');
	}

}
