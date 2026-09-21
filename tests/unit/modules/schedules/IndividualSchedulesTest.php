<?php

namespace tests\unit\modules\schedules;

use app\components\Forms\ActiveField;
use app\models\MaintenanceJobs;
use app\models\Services;
use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use Codeception\Test\Unit;

/**
 * Индивидуальные (безымянные) расписания — issue #139.
 *
 * Канон: расписание без имени принадлежит ровно одному объекту-владельцу,
 * не предлагается к выбору, не показывается в общем списке и удаляется,
 * когда владелец его отпустил. См. modules/schedules/README.md.
 */
class IndividualSchedulesTest extends Unit
{
	/** @var int[] */
	private $schedules = [];
	/** @var int[] */
	private $services = [];
	/** @var int[] */
	private $jobs = [];

	protected function _after()
	{
		//владельцев убираем первыми: их удаление уносит индивидуальные расписания
		if ($this->services) Services::deleteAll(['id'=>$this->services]);
		if ($this->jobs) MaintenanceJobs::deleteAll(['id'=>$this->jobs]);
		if ($this->schedules) {
			SchedulesEntries::deleteAll(['schedule_id'=>$this->schedules]);
			Schedules::deleteAll(['id'=>$this->schedules]);
		}
	}

	private function schedule(string $name=''): Schedules
	{
		$model=new Schedules(['name'=>$name]);
		$this->assertTrue($model->save(),'Расписание должно сохраняться'.($name?'':' и без имени'));
		$this->schedules[]=$model->id;
		return $model;
	}

	private function service(string $name, array $attrs=[]): Services
	{
		$model=new Services(array_merge([
			'name'=>$name,
			'description'=>$name,
			'is_end_user'=>0,
		],$attrs));
		$this->assertTrue($model->save(),'Сервис: '.json_encode($model->errors,JSON_UNESCAPED_UNICODE));
		$this->services[]=$model->id;
		return $model;
	}

	private function job(string $name, array $attrs=[]): MaintenanceJobs
	{
		$model=new MaintenanceJobs(array_merge([
			'name'=>$name,
			'description'=>$name,
		],$attrs));
		$this->assertTrue($model->save(),'Регламентная работа: '.json_encode($model->errors,JSON_UNESCAPED_UNICODE));
		$this->jobs[]=$model->id;
		return $model;
	}

	public function testUnnamedScheduleIsPrivate(): void
	{
		$this->assertTrue($this->schedule()->isPrivate,'Расписание без имени — индивидуальное');
		$this->assertFalse($this->schedule('Общее 24/7')->isPrivate,'Именованное — общее');
	}

	public function testIndividualScheduleIsNotOfferedForSelection(): void
	{
		$individual=$this->schedule();
		$shared=$this->schedule('Общее рабочее время (#139)');

		$names=Schedules::fetchNames();
		$this->assertArrayHasKey($shared->id,$names,'Именованное расписание доступно для выбора');
		$this->assertArrayNotHasKey($individual->id,$names,'Индивидуальное выбрать нельзя');
	}

	public function testDisplayNameGeneratedFromOwner(): void
	{
		$schedule=$this->schedule();
		$this->service('Почта (#139)',['providing_schedule_id'=>$schedule->id]);

		$fresh=Schedules::findOne($schedule->id);
		$this->assertSame('Расписание работы почта (#139)',$fresh->displayName,
			'Безымянное расписание называется по владельцу');

		$named=$this->schedule('Свое имя (#139)');
		$this->assertSame('Свое имя (#139)',$named->displayName,'Именованное — своим именем');
	}

	/**
	 * Регресс: расписание сервиса сделали индивидуальным (стерли имя), открыли форму
	 * сервиса - select2 строится из fetchNames(), где безымянных нет, поле рисовалось
	 * пустым, и сохранение формы обнуляло ссылку (а gc удалял расписание).
	 * Текущее значение поля обязано оставаться среди вариантов - с именем по владельцу.
	 */
	public function testOwnerFormKeepsItsIndividualSchedule(): void
	{
		$schedule=$this->schedule('Было общим (#139)');
		$service=$this->service('Телефония (#139)',['providing_schedule_id'=>$schedule->id]);

		$schedule->name='';
		$this->assertTrue($schedule->save(),'Имя единственного владельца стирается');

		$data=ActiveField::withCurrentValues(
			Schedules::fetchNames(),
			Schedules::class,
			$service->providing_schedule_id
		);
		$this->assertArrayHasKey($schedule->id,$data,'Свое индивидуальное расписание есть в вариантах формы владельца');
		$this->assertSame('Расписание работы телефония (#139)',$data[$schedule->id],'и подписано именем по владельцу');

		$foreign=ActiveField::withCurrentValues(Schedules::fetchNames(),Schedules::class,null);
		$this->assertArrayNotHasKey($schedule->id,$foreign,'В чужих формах его по-прежнему нет');
	}

	public function testOwnerDeletionRemovesIndividualSchedule(): void
	{
		$schedule=$this->schedule();
		$job=$this->job('Проверка бэкапов (#139)',['schedules_id'=>$schedule->id]);

		$job->delete();

		$this->assertNull(Schedules::findOne($schedule->id),
			'Индивидуальное расписание удаляется вместе с единственным владельцем');
	}

	public function testOwnerDeletionKeepsNamedSchedule(): void
	{
		$schedule=$this->schedule('Общее (#139)');
		$job=$this->job('Проверка бэкапов 2 (#139)',['schedules_id'=>$schedule->id]);

		$job->delete();

		$this->assertNotNull(Schedules::findOne($schedule->id),
			'Именованное расписание остается в общем пуле');
	}

	public function testSwitchingOwnerToAnotherScheduleRemovesOrphan(): void
	{
		$individual=$this->schedule();
		$other=$this->schedule('Другое (#139)');
		$job=$this->job('Обход серверной (#139)',['schedules_id'=>$individual->id]);

		$job->schedules_id=$other->id;
		$this->assertTrue($job->save());

		$this->assertNull(Schedules::findOne($individual->id),
			'Отвязанное индивидуальное расписание удаляется');
		$this->assertNotNull(Schedules::findOne($other->id));
	}

	public function testScheduleWithOtherOwnersSurvives(): void
	{
		$schedule=$this->schedule();
		//второй владелец появляется не через форму (там выбора нет), а напрямую —
		//проверяем, что gc считает реальные ссылки, а не «владелец отвязался»
		$service=$this->service('Сервис А (#139)',['providing_schedule_id'=>$schedule->id]);
		$service->support_schedule_id=$schedule->id;
		$service->save(false);

		$job=$this->job('Работа (#139)');
		$job->schedules_id=$schedule->id;
		$job->save(false);
		$job->delete();

		$this->assertNotNull(Schedules::findOne($schedule->id),
			'Пока есть хоть один владелец, расписание живет');
	}

	public function testCannotDropNameWhenScheduleIsShared(): void
	{
		$schedule=$this->schedule('Общее (#139)');
		$this->service('Сервис Б (#139)',['providing_schedule_id'=>$schedule->id]);
		$this->job('Работа Б (#139)',['schedules_id'=>$schedule->id]);

		$schedule->name='';
		$this->assertFalse($schedule->save(),'Нельзя сделать индивидуальным общее расписание');
		$this->assertArrayHasKey('name',$schedule->errors);
	}

	public function testCannotDropNameWhenScheduleHasChildren(): void
	{
		$parent=$this->schedule('Родительское (#139)');
		$child=new Schedules(['name'=>'Дочернее (#139)','parent_id'=>$parent->id]);
		$this->assertTrue($child->save());
		$this->schedules[]=$child->id;

		$parent->name='';
		$this->assertFalse($parent->save(),'Нельзя скрыть расписание, на которое ссылаются дочерние');
		$this->assertArrayHasKey('name',$parent->errors);
	}

	public function testCannotLinkToForeignIndividualSchedule(): void
	{
		$schedule=$this->schedule();
		$this->service('Хозяин (#139)',['providing_schedule_id'=>$schedule->id]);

		$intruder=new MaintenanceJobs([
			'name'=>'Чужак (#139)',
			'description'=>'Чужак (#139)',
			'schedules_id'=>$schedule->id,
		]);
		$this->assertFalse($intruder->save(),'Чужое индивидуальное расписание выбрать нельзя');
		$this->assertArrayHasKey('schedules_id',$intruder->errors);
	}

	/**
	 * Регресс: родителя с наследниками удаляли (UI показывал корзину, модель не возражала) -
	 * запрет держался лишь случайно на entries_ids, пока записи не стали удаляемыми.
	 */
	public function testParentWithChildrenCannotBeDeleted(): void
	{
		$parent=$this->schedule('Родитель (#139)');
		$child=new Schedules(['name'=>'Наследник (#139)','parent_id'=>$parent->id]);
		$this->assertTrue($child->save());
		$this->schedules[]=$child->id;

		$fresh=Schedules::findOne($parent->id);
		$this->assertGreaterThan(0,array_sum($fresh->nonDeletableReverseLinks()),'Кнопка удаления родителя заблокирована');
		$this->assertFalse($fresh->delete(),'Модель не дает удалить родителя ни одним путем');
		$this->assertNotNull(Schedules::findOne($parent->id));
	}

	/**
	 * Наследник удаленного родителя (parent_id висит - FK в БД нет) не роняет страницу
	 * и удаляется штатно.
	 */
	public function testChildOfMissingParentWorksAndDeletes(): void
	{
		$parent=$this->schedule('Удаляемый родитель (#139)');
		$child=new Schedules(['name'=>'Сирота-наследник (#139)','parent_id'=>$parent->id]);
		$this->assertTrue($child->save());
		$this->schedules[]=$child->id;
		//имитируем прод: родителя удалили в обход запрета
		Schedules::deleteAll(['id'=>$parent->id]);

		$orphan=Schedules::findOne($child->id);
		$this->assertNull($orphan->parent);
		$this->assertSame([$orphan->id],array_keys($orphan->parentsChain),'Цепочка обрывается на самом расписании');
		$orphan->findExceptions(0);	//то, что падало на странице (7days.php)

		//виджет удаления суммирует количества по типам связей (нулевые тоже приходят)
		$this->assertSame(0,array_sum($orphan->nonDeletableReverseLinks()),'Наследник без своих связей удаляем');
		$this->assertNotFalse($orphan->delete());
		$this->assertNull(Schedules::findOne($child->id));
	}

	public function testDeleteCascadesEntries(): void
	{
		$schedule=$this->schedule('С записями (#139)');
		$entry=new SchedulesEntries([
			'schedule_id'=>$schedule->id,
			'date'=>'def',
			'schedule'=>'08:00-17:00',
		]);
		$this->assertTrue($entry->save());

		$schedule->delete();

		$this->assertNull(SchedulesEntries::findOne($entry->id),
			'Записи расписания удаляются вместе с ним (FK в БД нет)');
	}
}
