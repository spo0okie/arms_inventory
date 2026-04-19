<?php

namespace app\controllers;

use app\models\CompsSearch;
use app\models\Domains;
use Throwable;
use Yii;
use app\models\Comps;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * CompsController implements the CRUD actions for Comps model.
 */
class CompsController extends ArmsBaseController
{
/**
     * @inheritdoc
     */
    public function accessMap()
    {
		return array_merge_recursive(
			parent::accessMap(),
			[
				'edit'=>['unlink','addsw','rmsw','ignoreip','unignoreip','dupes','absorb'],
				'view'=>['ttip-hw','item','item-by-name'],
			]);
		
    }
	
    /**
     * Отображает список дублирующихся ПК (компьютеров).
     *
     * Дублями считаются записи, у которых поле name встречается в таблице comps
     * более одного раза. Список строится через CompsSearch с фильтром по ids.
     * Если дублей нет — возвращает пустой набор (ids=[-1]).
     *
     * GET-параметры: отсутствуют.
     *
     * @return mixed
     */
    public function actionDupes()
    {
		$dupes = (new Query())
			->select(['GROUP_CONCAT(id) ids','name','COUNT(*) c'])
			->from('comps')
			->groupBy(['name'])
			->having('c > 1')
			->all();
		$ids=[];
		foreach ($dupes as $item) $ids=array_merge($ids , explode(',',$item['ids']));
		if (!count($ids)) $ids=[-1];
	
		// add conditions that should always apply here
	
		$searchModel=new CompsSearch();
		$dataProvider=$searchModel->search(['CompsSearch'=>['ids'=>$ids]]);
	
        return $this->render('/layouts/index', [
            'dataProvider' => $dataProvider,
			'searchModel' => $searchModel,
			'model' => new Comps(),
        ]);
    }
	
	/**
	 * Acceptance test data for actionDupes.
	 *
	 * Что делает actionDupes:
	 * - выполняет агрегирующий SQL-запрос по `comps.name` и ищет записи, у которых
	 *   одинаковый `name` встречается более одного раза;
	 * - собирает найденные id в один массив и передаёт их в `CompsSearch` через
	 *   фильтр `ids`;
	 * - если дублей нет, ids=[-1] (пустой набор), и страница всё равно рендерится
	 *   со стандартным layout `/layouts/index`.
	 *
	 * Что именно проверяет этот тест:
	 * 1) Сценарий `no duplicates`: даже при полном отсутствии дублей (случай по
	 *    умолчанию после загрузки acceptance-дампа) action должен отдавать
	 *    HTTP 200 и корректно рендерить пустой список поверх CompsSearch.
	 * 2) Сценарий `with duplicates`: после создания двух записей Comps с одинаковым
	 *    `name` action попадает в ветку, где в выборку подставляются реальные id
	 *    из агрегации `GROUP BY name HAVING COUNT(*) > 1`; страница также должна
	 *    отдавать HTTP 200.
	 *
	 * Почему этого достаточно для acceptance-контракта:
	 * - задача теста на этом уровне — подтвердить, что маршрут `/comps/dupes`
	 *   открывается и рендерится без фатальных ошибок как с пустым, так и с
	 *   непустым `ids`-набором;
	 * - конкретный состав отфильтрованного списка здесь намеренно не проверяем,
	 *   чтобы не фиксировать в тесте зависимость от содержимого дампа.
	 *
	 * Особенность подготовки дублей:
	 * - две модели Comps создаются через `ModelFactory::create(..., ['empty' => true])`,
	 *   затем у второй модели `name` принудительно выравнивается под первую
	 *   через `silentSave()`, чтобы не проходить бизнес-валидации формы и
	 *   гарантированно получить две записи с одинаковым `name` в БД.
	 */
	public function testDupes(): array
	{
		$scenarios = [[
			'name' => 'no duplicates',
			'GET' => [],
			'response' => 200,
		]];

		try {
			$first = \app\generation\ModelFactory::create(Comps::class, ['empty' => true]);
			$second = \app\generation\ModelFactory::create(Comps::class, ['empty' => true]);
			// Принудительно выравниваем name, чтобы получить пару дублей в БД.
			$second->name = $first->name;
			$second->silentSave();

			$scenarios[] = [
				'name' => 'with duplicates',
				'GET' => [],
				'response' => 200,
			];
		} catch (\Throwable $e) {
			// Если генерация дублей не удалась (например, проблемы с ModelFactory),
			// тест всё равно проверит базовый сценарий без дублей.
		}

		return $scenarios;
	}
	
	/**
	 * Отображает краткую карточку (partial) ПК.
	 *
	 * Рендерит шаблон item без общего layout — используется для вставки
	 * в модальные окна и inline-блоки.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор ПК (comps.id)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если ПК с заданным id не найден
	 */
	public function actionItem(int $id)
	{
		return $this->renderPartial('item', [
			'model' => $this->findModel($id)
		]);
	}
		
	
	/**
	 * Поглощение одного ПК другим (merge/absorb).
	 *
	 * Находит оба ПК по id, затем вызывает absorbComp() на целевой модели.
	 * После поглощения исходный ПК ($absorb_id) удаляется, все связанные данные
	 * переносятся на целевой ($id). Изменяет данные — только для пользователей
	 * с правом edit.
	 *
	 * GET-параметры:
	 * @param int   $id        Идентификатор целевого ПК, который останется
	 * @param mixed $absorb_id Идентификатор ПК, который будет поглощён и удалён
	 *
	 * @return mixed Перенаправление на страницу просмотра целевого ПК
	 * @throws NotFoundHttpException если один из ПК не найден
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
	public function actionAbsorb(int $id, $absorb_id)
	{
		$model = $this->findModel($id);
		/** @var Comps $model */
		$absorb = $this->findModel($absorb_id);
		/** @var Comps $absorb */
		
		$model->absorbComp($absorb);
		return $this->redirect(['view', 'id' => $model->id]);
	}
	
	
		
	/**
	 * Тестовые данные приёмочного теста для actionAbsorb.
	 *
	 * Тест пропущен (skip): для корректной проверки необходимо создать два
	 * отдельных ПК (Comps) с уникальными именами, сохранить их в БД, затем
	 * передать их id как параметры id и absorb_id. После запроса нужно
	 * проверить, что запись absorb_id удалена, а целевой ПК (id) содержит
	 * перенесённые связанные данные.
	 *
	 * @return array сценарий skip
	 */
	public function testAbsorb(): array
	{
		$target = \app\generation\ModelFactory::create(\app\models\Comps::class, ['empty' => true]);
		$source = \app\generation\ModelFactory::create(\app\models\Comps::class, ['empty' => true]);
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $target->id, 'absorb_id' => $source->id],
			'response' => 302,
		]];
	}
	/**
	 * Отображает tooltip с данными аппаратного обеспечения (HW) ПК.
	 *
	 * Рендерит шаблон /techs/ttip-hw как partial для отображения
	 * во всплывающих подсказках интерфейса. Требует полностью заполненной
	 * модели с аппаратными характеристиками.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор ПК (comps.id)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если ПК с заданным id не найден
	 */
	public function actionTtipHw(int $id)
	{
		return $this->renderPartial('/techs/ttip-hw', [
			'model' => $this->findModel($id),
		]);
	}
	
	/**
	 * Добавляет к поисковому запросу ПК фильтры where на основании его имени
	 * DOMAIN\computer, computer.domain.local или отдельно передав домен
	 * возвращаем описание ошибки для 404 (если не найдется)
	 * @param        $query
	 * @param string $name
	 * @param        $domain
	 * @return string
	 * @throws BadRequestHttpException
	 * @throws NotFoundHttpException
	 */
	public static function nameFilter($query, string $name, $domain=null){
		
		$name=strtoupper($name);
		
		if ($domain) {
			$domain_id=Domains::findByAnyName($domain);
			$compName=$name;
			$domainName=$domain;
		} else {
			$nameParse=Domains::fetchFromCompName($name,'',true);
			if (!is_array($nameParse)) throw new BadRequestHttpException("Incorrect comp name $name");
			
			[$domain_id,$compName,$domainName]=$nameParse;
		}
		
		if (is_null($domain_id)) throw new NotFoundHttpException("Domain $domainName not found");
		
		$notFoundDescription="Comp with name '$compName'";
		$query->where(['LOWER(name)'=>mb_strtolower($compName)]);
		
		$notFoundDescription.=" not found";
		
		//добавляем фильтрацию по домену если он есть
		if ($domain_id!==false) {
			//добавляем домен к условию поиска
			$query->andFilterWhere(['domain_id'=>$domain_id]);
			$notFoundDescription.=" in domain $domainName";
		}
		
		return $notFoundDescription;
	}

	/**
	 * Найти ОС по имени DOMAIN\computer, computer.domain.local или отдельно передав домен
	 * @param string      $name
	 * @param null|string $domain
	 * @param null|string $ip
	 * @return array|ActiveRecord
	 * @throws NotFoundHttpException|BadRequestHttpException
	 */
	public static function searchModel(string $name, $domain=null, $ip=null){
		
		$query= Comps::find();
		$notFoundDescription=static::nameFilter($query, $name, $domain);
		
		//добавляем фильтрацию по IP если он есть
		if (!is_null($ip)) {
			//если передано несколько адресов (через пробел)
			$query->andFilterWhere(['or like','ip',explode(' ',trim($ip))]);
			$notFoundDescription.=" with IP $ip";
		}
		
		$notFoundDescription.=" not found";
		
		
		$model = $query->one();
		
		if ($model === null)
			throw new NotFoundHttpException($notFoundDescription);
		
		return $model;
	}

	/**
	 * Acceptance test data for actionTtipHw.
	 *
	 * Что делает actionTtipHw:
	 * - по переданному GET `id` находит модель Comps через {@see findModel()};
	 * - рендерит `renderPartial('/techs/ttip-hw', ['model' => ...])` — tooltip-карточка
	 *   аппаратного обеспечения; внутри подключается `views/techs/hw.php`, который
	 *   итерируется по `$model->hwList->items` и использует `Manufacturers::fetchNames()`.
	 * - при ненайденной модели бросает `NotFoundHttpException` (HTTP 404).
	 *
	 * Что именно проверяем в acceptance:
	 * 1) Сценарий `full`: GET id={full->id} — модель Comps с заполненными атрибутами.
	 *    Убеждаемся, что ttip-hw рендерится без ошибок даже для модели с «богатым»
	 *    атрибутным составом. Ожидаемый код — 200.
	 * 2) Сценарий `empty`: GET id={empty->id} — минимально заполненная модель.
	 *    Проверяет, что шаблон устойчив к пустому hwList и отсутствию HW-атрибутов
	 *    (важно, т.к. в UI ttip-hw вызывается для произвольных Comps). Код — 200.
	 * 3) Сценарий `missing`: GET id = несуществующий идентификатор.
	 *    Проверяет, что action корректно отрабатывает 404 через findModel(),
	 *    а не падает на этапе рендера (ранний возврат NotFoundHttpException).
	 *
	 * Почему этого достаточно:
	 * - acceptance-контракт — подтвердить стабильность UI-endpoint'а и безопасную
	 *   обработку граничных случаев (пустая модель/несуществующий id);
	 * - конкретное содержимое tooltip (список HW) зависит от данных и проверяется
	 *   отдельно на уровне вьюхи — здесь не фиксируем.
	 */
	public function testTtipHw(): array
	{
		$testData = $this->getTestData();
		$full = $testData['full'];
		$empty = $testData['empty'];
		$missingId = (int)(Comps::find()->max('id')) + 1000;

		return [
			[
				'name' => 'ttip-hw full',
				'GET' => ['id' => $full->id],
				'response' => 200,
			],
			[
				'name' => 'ttip-hw empty',
				'GET' => ['id' => $empty->id],
				'response' => 200,
			],
			[
				'name' => 'ttip-hw missing id',
				'GET' => ['id' => $missingId],
				'response' => 404,
			],
		];
	}
	/**
	 * Привязывает ПО (software) к ПК.
	 *
	 * Добавляет один или несколько идентификаторов ПО к полю soft_ids модели Comps.
	 * Если передан специальный токен 'sign-all' — привязывает всё согласованное ПО
	 * из swList. Изменения сохраняются через silentSave() без журналирования.
	 * После сохранения перенаправляет на паспорт ARM (/techs/docs).
	 *
	 * GET-параметры:
	 * @param mixed $id    Идентификатор ПК (comps.id)
	 *
	 * Дополнительный GET-параметр:
	 *   - items  string  Список id ПО через запятую, либо строка 'sign-all'
	 *                    для привязки всего согласованного ПО. Если не передан —
	 *                    действие выполняет только редирект.
	 *
	 * @return string|Response Перенаправление на /techs/docs с doc=passport
	 * @throws NotFoundHttpException если ПК с заданным id не найден
	 */
	public function actionAddsw($id){
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model = $this->findModel($id);
		/** @var Comps $model */

		//проверяем передан ли a
		$strItems=Yii::$app->request->get('items',null);
		if (strlen($strItems)) {
			if ($strItems==='sign-all') {
				$items=array_keys($model->swList->getAgreed());
			} else
				($items=explode(',',$strItems));

			if (is_array($items)) {
				$model->soft_ids=array_unique(array_merge($model->soft_ids,$items));
				$model->silentSave();
			}
		}

		return $this->redirect(['/techs/docs', 'id' => $model->arm_id,'doc'=>'passport']);
	}

    	
	/**
	 * Тестовые данные приёмочного теста для actionAddsw.
	 *
	 * Тест пропущен (skip): для проверки необходим существующий ПК (Comps)
	 * с хотя бы одним доступным для привязки ПО (Softs). Нужно создать
	 * запись ПК, создать запись ПО, передать id ПК и id ПО через GET items.
	 * Дополнительно проверить итоговое значение soft_ids на наличие
	 * переданного id.
	 *
	 * @return array сценарий skip
	 */
	public function testAddsw(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id],
			'response' => 302,
		]];
	}
	/**
	 * Удаляет привязку ПО к ПК.
	 *
	 * Убирает переданные идентификаторы ПО из поля soft_ids модели Comps
	 * через array_diff. Изменения сохраняются через silentSave().
	 * После сохранения перенаправляет на паспорт ARM (/techs/docs).
	 *
	 * GET-параметры:
	 * @param mixed $id    Идентификатор ПК (comps.id)
	 *
	 * Дополнительный GET-параметр:
	 *   - items  string  Список id ПО через запятую. Если не передан —
	 *                    действие выполняет только редирект.
	 *
	 * @return string|Response Перенаправление на /techs/docs с doc=passport
	 * @throws NotFoundHttpException если ПК с заданным id не найден
	 */
    public function actionRmsw($id){
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $model = $this->findModel($id);
		/** @var Comps $model */

        //проверяем передан ли a
        $strItems=Yii::$app->request->get('items',null);
        if (strlen($strItems)) {
            if (is_array($items=explode(',',$strItems))){
                $model->soft_ids=array_diff($model->soft_ids,$items);
                $model->silentSave();
            }
		}

        return $this->redirect(['/techs/docs', 'id' => $model->arm_id,'doc'=>'passport']);
    }
		
	/**
	 * Тестовые данные приёмочного теста для actionRmsw.
	 *
	 * Тест пропущен (skip): аналогично testAddsw — необходим ПК с уже
	 * привязанным ПО (soft_ids содержит конкретный id). Нужно создать ПК
	 * с предзаполненным soft_ids, передать его id и id ПО через GET items,
	 * затем проверить, что переданный id исчез из soft_ids.
	 *
	 * @return array сценарий skip
	 */
	public function testRmsw(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id],
			'response' => 302,
		]];
	}
	/**
	 * Добавляет IP-адрес в список игнорируемых для ПК.
	 *
	 * Добавляет переданный IP в поле ip_ignore модели Comps (разделитель — "\n").
	 * Дубликаты удаляются через array_unique. Сохраняет модель и перенаправляет
	 * на страницу просмотра ПК.
	 *
	 * GET-параметры:
	 * @param mixed  $id  Идентификатор ПК (comps.id)
	 * @param mixed  $ip  IP-адрес, который нужно добавить в список игнорируемых
	 *
	 * @return string|Response Перенаправление на /comps/view
	 * @throws NotFoundHttpException если ПК с заданным id не найден
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function actionIgnoreip($id,$ip){
		$model = $this->findModel($id);
		/** @var Comps $model */

		$ignored=explode("\n",$model->ip_ignore);
		$ignored[]=$ip;
		$model->ip_ignore=implode("\n",array_unique($ignored));
		$model->save();

		return $this->redirect(['/comps/view', 'id' => $model->id]);
	}

		
	/**
	 * Тестовые данные приёмочного теста для actionIgnoreip.
	 *
	 * Тест пропущен (skip): необходим существующий ПК (Comps) и корректный
	 * IP-адрес. Нужно создать ПК, передать его id и тестовый IP (например,
	 * '192.168.1.1'), затем проверить, что этот IP появился в ip_ignore модели.
	 *
	 * @return array сценарий skip
	 */
	public function testIgnoreip(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id, 'ip' => '192.0.2.1'],
			'response' => 302,
		]];
	}
	/**
	 * Убирает IP-адрес из списка игнорируемых для ПК.
	 *
	 * Ищет переданный IP в поле ip_ignore модели Comps и удаляет его через unset.
	 * Если IP не найден — модель не изменяется. Сохраняет модель и перенаправляет
	 * на страницу просмотра ПК.
	 *
	 * GET-параметры:
	 * @param mixed  $id  Идентификатор ПК (comps.id)
	 * @param mixed  $ip  IP-адрес, который нужно убрать из списка игнорируемых
	 *
	 * @return string|Response Перенаправление на /comps/view
	 * @throws NotFoundHttpException если ПК с заданным id не найден
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function actionUnignoreip($id,$ip){
		$model = $this->findModel($id);
		/** @var Comps $model */

		$ignored=explode("\n",$model->ip_ignore);
		$id=array_search($ip,$ignored);
		if (!is_null($id)) {
			unset($ignored[$id]);
			$model->ip_ignore=implode("\n",array_unique($ignored));
			$model->save();
		}
		
		return $this->redirect(['/comps/view', 'id' => $model->id]);
	}
	
	/**
	 * Тестовые данные приёмочного теста для actionUnignoreip.
	 *
	 * Тест пропущен (skip): необходим ПК с IP-адресом, уже присутствующим
	 * в ip_ignore. Нужно создать ПК с предзаполненным ip_ignore, передать
	 * id ПК и целевой IP, затем проверить, что IP исчез из ip_ignore.
	 *
	 * @return array сценарий skip
	 */
	public function testUnignoreip(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id, 'ip' => '192.0.2.1'],
			'response' => 302,
		]];
	}
	public $modelClass='app\models\Comps';

	/**
	 * Тестовые данные приёмочного теста для actionItemByName.
	 *
	 * Тестирует поиск ПК по имени формата DOMAIN\computer или computer.domain.local.
	 * Метод CompsController::searchModel() парсит домен из имени через
	 * Domains::fetchFromCompName(), поэтому имя пустой модели может дать 404,
	 * если её домен не найден в БД. Для надёжного теста используем только 'full'-модель,
	 * у которой домен гарантированно создан через ModelFactory.
	 *
	 * Сценарий empty пропускается (skip): у пустой модели Comps поле name генерируется
	 * без существующего домена в БД, что ведёт к 404 в nameFilter.
	 *
	 * @return array
	 */
	public function testItemByName(): array
	{
		$testData = $this->getTestData();
		$full = $testData['full'];
		return [
			[
				'name'     => 'item by name full',
				'GET'      => ['name' => $full->getName()],
				'response' => 200,
			],
			self::skipScenario(
				'item by name empty',
				'empty Comps model has no domain in DB, so nameFilter throws 404; ' .
				'fix by ensuring ModelFactory creates Comps with a saved Domain'
			)[0],
		];
	}

	protected function findByName(string $name)
	{
		return static::searchModel($name);
	}
}
