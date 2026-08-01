<?php

namespace app\modules\schedules\controllers;

use app\components\DynaGridWidget;
use app\generation\ModelFactory;
use app\helpers\StringHelper;
use app\models\Aces;
use app\models\Acls;
use app\modules\schedules\models\ScheduledAccess;
use app\modules\schedules\models\SchedulesAclSearch;
use Yii;
use app\modules\schedules\models\Schedules;
use yii\web\NotFoundHttpException;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class ScheduledAccessController extends \app\controllers\ArmsBaseController
{
	//обёртка над Schedules: те же данные, но своя подача страниц
	//(titles/modelDescription/справка «Временные доступы»), см. ScheduledAccess
	public $modelClass=ScheduledAccess::class;
	public function accessMap()
	{
		$map=array_merge_recursive(parent::accessMap(),[
			'view'=>['status']
		]);
		//ScheduledAccess — обёртка для подачи страниц, а не отдельная сущность прав:
		//гранулярные полномочия остаются view-/edit-schedules, как до появления обёртки
		foreach ([static::PERM_VIEW,static::PERM_EDIT] as $perm) {
			if (isset($map[$perm.'-scheduled-access'])) {
				$map[$perm.'-schedules']=$map[$perm.'-scheduled-access'];
				unset($map[$perm.'-scheduled-access']);
			}
		}
		return $map;
	}


	/**
	 * Отображает список расписаний доступа (ACL-расписания) с поиском и фильтрацией.
	 * Использует SchedulesAclSearch и DynaGrid для построения таблицы.
	 * Поддерживает переключение отображения архивных записей.
	 *
	 * GET-параметры: стандартные параметры поиска SchedulesAclSearch через queryParams.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		//Services::cacheAllItems();
		//Places::cacheAllItems();
		$searchModel = new SchedulesAclSearch();
		$model= new $this->modelClass();
		$columns=DynaGridWidget::fetchVisibleAttributes($model,StringHelper::class2Id($this->modelClass).'-index');
		$this->archivedSearchInit($searchModel,$dataProvider,$switchArchivedCount,$columns);

		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount??null,
		]);
	}

	/**
	 * Возвращает текущий статус расписания доступа: активно ли оно прямо сейчас.
	 * Вычисляет текущее время с учётом сдвига часового пояса из params['schedulesTZShift']
	 * и вызывает метод isWorkTime() модели Schedules.
	 *
	 * GET-параметры:
	 * @param int $id  ID расписания доступа (Schedules)
	 *
	 * @return mixed  Результат isWorkTime(): true/false или строка статуса
	 * @throws NotFoundHttpException если расписание не найдено
	 */
	public function actionStatus(int $id)
	{
		/** @var Schedules $model */
		$model=$this->findModel($id);
		return $model->isWorkTime(
			gmdate('Y-m-d',time()+Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+Yii::$app->params['schedulesTZShift'])
		);
	}

	/**
	 * Тестирует actionStatus: запрашивает статус расписания для записи
	 * из getTestData()['full']. Ожидает HTTP 200.
	 *
	 * @return array
	 */
	public function testStatus(): array
	{
		$testData=$this->getTestData();

		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}

	/**
	 * Отображает страницу просмотра расписания доступа.
	 * Если расписание является override (isOverride === true), перенаправляет
	 * на просмотр родительского расписания (override_id), передавая все GET-параметры.
	 *
	 * GET-параметры:
	 * @param int $id  ID расписания доступа (Schedules)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если расписание не найдено
	 */
    public function actionView(int $id)
    {
    	/** @var Schedules $model */
    	$model=$this->findModel($id);
    	if ($model->isOverride) {
    		$params=Yii::$app->request->get();
    		$params['id']=$model->override_id;
    		return $this->redirect(array_merge(['view'],$params));
		}

        return $this->render('view', [
            'model' => $model,
        ]);
    }

	/**
	 * actionView редиректит на оригинал расписания, если у текущего выставлен override_id.
	 * `full` из getTestData() может иметь override_id → 302, а не 200. Поэтому проверяем
	 * на `empty`-модели, у которой override_id гарантированно null.
	 */
	public function testView(): array
	{
		$testData = $this->getTestData();

		//расписание, где два ACL имеют ОДИНАКОВЫЙ набор ACE → одна группа (компактный рендер)
		$groupedId=$this->buildScheduleWithAces(['общий доступ','общий доступ']);
		//расписание, где два ACL имеют РАЗНЫЕ ACE → группы нет (каждый ACL отдельно)
		$ungroupedId=$this->buildScheduleWithAces(['доступ-1','доступ-2']);

		$scenarios=[[
			'name'     => 'default',
			'GET'      => ['id' => $testData['empty']->id],
			'response' => 200,
		]];

		if ($groupedId) {
			$scenarios[]=[
				'name'     => 'grouped acls',
				'GET'      => ['id' => $groupedId],
				'response' => 200,
				//одинаковые ACE → на странице присутствует групповой рендер
				'assert'   => static function (\AcceptanceTester $I) {
					$I->seeResponseContains('acl-group-resources');
				},
			];
			$scenarios[]=[
				'name'     => 'detailed acls',
				//переключатель «Детально» (group=0) → плоский рендер без группировки
				'GET'      => ['id' => $groupedId, 'group' => 0],
				'response' => 200,
				'assert'   => static function (\AcceptanceTester $I) {
					$I->dontSeeResponseContains('acl-group-resources');
				},
			];
		}

		if ($ungroupedId) {
			$scenarios[]=[
				'name'     => 'ungrouped acls',
				'GET'      => ['id' => $ungroupedId],
				'response' => 200,
				//разные ACE → группового рендера быть не должно
				'assert'   => static function (\AcceptanceTester $I) {
					$I->dontSeeResponseContains('acl-group-resources');
				},
			];
		}

		return $scenarios;
	}

	/**
	 * Создаёт расписание доступа с набором ACL (по одному на элемент $aceComments),
	 * у каждого — отдельный ресурс (Acls.comment) и один ACE с заданным Aces.comment.
	 * ACL с одинаковым Aces.comment попадут в одну группу.
	 *
	 * @param string[] $aceComments комментарий ACE для каждого создаваемого ACL
	 * @return int|null id созданного расписания или null, если фикстуру создать не удалось
	 */
	protected function buildScheduleWithAces(array $aceComments): ?int
	{
		try {
			$schedule=ModelFactory::create(Schedules::class,['empty'=>true]);
			if (!$schedule) return null;

			foreach ($aceComments as $i=>$aceComment) {
				$acl=new Acls();
				$acl->schedules_id=$schedule->id;
				$acl->comment='ресурс-'.($i+1);	//отдельный ресурс на каждый ACL
				if (!$acl->save()) return null;

				$ace=new Aces();
				$ace->acls_id=$acl->id;
				$ace->comment=$aceComment;		//определяет «одинаковость» ACE
				if (!$ace->save()) return null;
			}

			return $schedule->id;
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * Дополняет базовый testTtip сценарием с привязанными ACL: тултип временного доступа
	 * показывает группы доступов (кому/куда), и эта ветка не покрывается моделями full/empty,
	 * у которых списков доступа нет.
	 *
	 * @return array
	 */
	public function testTtip(): array
	{
		$scenarios=parent::testTtip();

		//два ACL с одинаковым набором ACE → в тултипе группа ресурсов одним блоком
		if ($groupedId=$this->buildScheduleWithAces(['общий доступ','общий доступ'])) {
			$scenarios[]=[
				'name'     => 'ttip with acls',
				'GET'      => ['id' => $groupedId],
				'response' => 200,
				'assert'   => static function (\AcceptanceTester $I) {
					$I->seeResponseContains('scheduled-access-ttip');
					//оба ресурса группы присутствуют в тултипе
					$I->seeResponseContains('ресурс-1');
					$I->seeResponseContains('ресурс-2');
				},
			];
		}

		return $scenarios;
	}

	/**
	 * Глубокая копия временного доступа («повторить служебку» / применить шаблон,
	 * plans/access-defaults-and-copy.md, итерация 3).
	 *
	 * GET: форма — название/заметки нового доступа (предзаполнены образцом) и
	 * необязательная подмена субъектов (пользователей). POST: в одной транзакции
	 * создаются копия расписания, всех его ACL (ресурсы + notepad) и всех ACE
	 * (через {@see Aces::copyContentTo()}). Если в форме выбраны пользователи —
	 * в каждой скопированной ACE субъекты-пользователи заменяются на них,
	 * а персональные IP-субъекты образца очищаются. Периоды действия образца
	 * не копируются — новые периоды задаются на странице нового доступа.
	 *
	 * Сценарий шаблонов: временный доступ-образец без реальных субъектов
	 * (заглушка в comment ACE) + копия с подменой субъектов.
	 *
	 * @param int $id id временного доступа-образца
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionCopy(int $id)
	{
		/** @var Schedules $source */
		$source=$this->findModel($id);
		$model=new ScheduledAccess();
		$model->copyPrefillFrom($source);
		$ace=new Aces();	//носитель поля подмены субъектов (Aces[users_ids])

		if ($model->load(Yii::$app->request->post())) {
			$ace->load(Yii::$app->request->post());
			$replaceUsers=array_filter((array)($ace->users_ids?:[]));

			$transaction=Yii::$app->db->beginTransaction();
			try {
				if (!$model->save()) {
					throw new \RuntimeException('Не удалось сохранить временный доступ');
				}
				foreach ($source->acls as $srcAcl) {
					$acl=new Acls();
					$acl->schedules_id=$model->id;
					$acl->notepad=$srcAcl->notepad;
					foreach (['services_id','comps_id','techs_id','ips_id','networks_id','comment'] as $field) {
						$acl->$field=$srcAcl->$field;
					}
					if (!$acl->save()) {
						throw new \RuntimeException('Не удалось скопировать ACL '.$srcAcl->sname);
					}
					foreach ($srcAcl->aces as $srcAce) {
						$newAce=new Aces();
						$srcAce->copyContentTo($newAce);
						$newAce->acls_id=$acl->id;
						if ($replaceUsers) {
							//подмена субъектов: доступ получают указанные в форме пользователи,
							//персональные IP прежних субъектов не переносятся
							$newAce->users_ids=$replaceUsers;
							$newAce->ips='';
						}
						if (!$newAce->save()) {
							throw new \RuntimeException('Не удалось скопировать запись доступа');
						}
					}
				}
				$transaction->commit();
				return $this->redirect(['view','id'=>$model->id]);
			} catch (\Throwable $e) {
				$transaction->rollBack();
				Yii::$app->session->setFlash('error',$e->getMessage());
			}
		}

		return $this->render('copy',[
			'model'=>$model,
			'source'=>$source,
			'ace'=>$ace,
		]);
	}

	/**
	 * Тест для {@see actionCopy()}: форма копии и глубокое копирование.
	 *
	 * Сценарии:
	 *  1. 'form load' — GET id образца с ACL/ACE: форма открывается (200).
	 *  2. 'copy post' — POST с новым названием без подмены субъектов:
	 *     создаётся расписание с тем же числом ACL и содержимым ACE (302),
	 *     проверяется ассертом по БД.
	 *
	 * @return array
	 */
	public function testCopy(): array
	{
		$sourceId=$this->buildScheduleWithAces(['копия доступ-1','копия доступ-2']);
		if (!$sourceId) return parent::testCopy();

		return [
			[
				'name' => 'form load',
				'GET' => ['id' => $sourceId],
				'response' => 200,
			],
			[
				'name' => 'copy post',
				'GET' => ['id' => $sourceId],
				'POST' => ['Schedules' => ['name' => 'копия временного доступа #204']],
				'response' => [200,302],
				'assert' => static function (\AcceptanceTester $I) {
					$copy=Schedules::find()->where(['name'=>'копия временного доступа #204'])->one();
					\PHPUnit\Framework\Assert::assertNotNull($copy,'Копия временного доступа должна создаться');
					$acls=Acls::find()->where(['schedules_id'=>$copy->id])->all();
					\PHPUnit\Framework\Assert::assertCount(2,$acls,'Должны скопироваться оба ACL образца');
					$aceComments=[];
					foreach ($acls as $acl) {
						foreach ($acl->aces as $ace) $aceComments[]=$ace->comment;
					}
					sort($aceComments);
					\PHPUnit\Framework\Assert::assertEquals(
						['копия доступ-1','копия доступ-2'],$aceComments,
						'Содержимое ACE должно скопироваться'
					);
				},
			],
		];
	}

	/**
	 * Создание нового временного доступа.
	 *
	 * Раньше здесь создавалось «пустое» расписание + пустой ACL (который не проходил валидацию
	 * и не создавался), из-за чего расписание оставалось без ACL и выпадало из списка доступов
	 * без пути назад (issue #214). Теперь перенаправляем на форму создания ACL с режимом нового
	 * расписания: расписание, ACL и ACE создаются вместе и атомарно.
	 *
	 * @return \yii\web\Response
	 */
	public function actionCreate()
	{
		return $this->redirect(['/acls/create','newSchedule'=>1]);
	}

	/**
	 * Тест для {@see actionCreate()}: перенаправление на форму создания ACL (новый временный доступ).
	 *
	 * @return array
	 */
	public function testCreate(): array
	{
		return [[
			'name' => 'redirect to acl form',
			'response' => 302,
		]];
	}

}
