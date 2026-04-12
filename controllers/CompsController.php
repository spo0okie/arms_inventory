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
	 * Тестовые данные приёмочного теста для actionDupes.
	 *
	 * Тест пропущен (skip): для проверки необходимо наличие как минимум двух
	 * записей Comps с одинаковым полем name в БД. Автоматическая генерация
	 * таких дублей через getTestData() нетривиальна, т.к. модель Comps
	 * обычно создаётся с уникальными именами. Тест оставлен в skip до
	 * реализации фикстуры с дублирующимися именами.
	 *
	 * @return array сценарий skip
	 */
	public function testDupes(): array
	{
		return self::skipScenario('default', 'requires at least two Comps records with identical name field; auto-generation via getTestData() is non-trivial');
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
		return self::skipScenario('default', 'requires two persisted Comps records with unique names; pass their ids as id and absorb_id');
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
	 * Тестовые данные приёмочного теста для actionTtipHw.
	 *
	 * Тест пропущен (skip): шаблон ttip-hw требует полностью заполненной
	 * модели Comps с данными об аппаратном обеспечении (CPU, RAM, диски и т.д.).
	 * Может быть заменён реальным тестом, если getTestData() создаёт модель
	 * Comps в режиме 'full' с заполненными HW-атрибутами.
	 *
	 * @return array сценарий skip
	 */
	public function testTtipHw(): array
	{
		return self::skipScenario('default', 'requires Comps model with fully populated hardware attributes; replace skip when getTestData() supports full HW context');
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
		return self::skipScenario('default', 'requires existing Comps record and at least one Softs record; pass comp id and soft ids via GET items');
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
		return self::skipScenario('default', 'requires existing Comps record with pre-populated soft_ids; pass comp id and soft ids to remove via GET items');
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
		return self::skipScenario('default', 'requires existing Comps record; pass its id and a test IP address, then verify IP appears in ip_ignore field');
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
		return self::skipScenario('default', 'requires existing Comps record with pre-populated ip_ignore; pass its id and the IP to remove, then verify IP is absent from ip_ignore');
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
