<?php

namespace app\console\commands;

use app\modules\schedules\models\Schedules;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Обслуживание расписаний: индивидуальные (безымянные) расписания, issue #139.
 *
 * Использование:
 *   yii schedules/cleanup-names [--dryRun=0]   снять шаблонные имена с одноразовых расписаний
 *   yii schedules/gc            [--dryRun=0]   удалить индивидуальные расписания без владельцев
 *
 * Канон индивидуальных расписаний — modules/schedules/README.md.
 */
class SchedulesController extends Controller
{
	/**
	 * @var bool только показать, что будет сделано (ничего не меняя).
	 * По умолчанию включен: обе операции удаляют данные, поэтому сначала отчет.
	 */
	public $dryRun = true;

	/** {@inheritdoc} */
	public function options($actionID)
	{
		return array_merge(parent::options($actionID),['dryRun']);
	}

	/** {@inheritdoc} */
	public function optionAliases()
	{
		return array_merge(parent::optionAliases(),['d'=>'dryRun']);
	}

	/**
	 * Снимает ШАБЛОННЫЕ имена с расписаний, которые используются одним объектом,
	 * превращая их в индивидуальные (issue #139).
	 *
	 * Шаблонное имя — то, которое когда-то предзаполнила форма создания расписания
	 * со страницы владельца («Расписание работы такого-то сервиса»). Такое имя
	 * не несет авторского смысла: оно восстанавливается формулой
	 * {@see Schedules::generateName()} из имени владельца, поэтому операция
	 * не теряет данных — расписание продолжит называться так же
	 * ({@see \app\modules\schedules\models\traits\SchedulesModelCalcFieldsTrait::getDisplayName()}).
	 *
	 * Имена, которые человек написал сам, НЕ трогаются: снять имя у них можно
	 * только руками, через форму редактирования расписания.
	 *
	 * Использование: yii schedules/cleanup-names --dryRun=0
	 *
	 * @return int
	 */
	public function actionCleanupNames()
	{
		$modes=[
			'providing'	=> 'providingServices',
			'support'	=> 'supportServices',
			'job'		=> 'maintenanceJobs',
		];

		$report=['снято'=>[],'авторское имя'=>[],'похоже на шаблон, но разошлось'=>[]];

		/** @var Schedules $schedule */
		foreach (Schedules::find()->where(['override_id'=>null])->andWhere(['<>','name',''])->each() as $schedule) {
			//расписание временного доступа: имя вводит человек, свой раздел UI
			if (count($schedule->acls)) continue;
			//дети не дадут скрыть расписание из выбора - не кандидат
			if (count($schedule->childrenNonOverrides)) continue;
			//индивидуальное - ровно один владелец
			if (count($schedule->usedBy)!=1) continue;

			$template='';
			foreach ($modes as $mode=>$link) {
				foreach ($schedule->$link as $owner) {
					$template=Schedules::generateName($owner,$mode);
					break 2;
				}
			}
			if (!strlen($template)) continue;

			$line='#'.$schedule->id.' '.$schedule->name;

			if ($schedule->name===$template) {
				if (!$this->dryRun) {
					$schedule->name='';
					if (!$schedule->save()) {
						$report['авторское имя'][]=$line.' - ОШИБКА: '.json_encode($schedule->errors,JSON_UNESCAPED_UNICODE);
						continue;
					}
				}
				$report['снято'][]=$line;
			} elseif (mb_strpos($schedule->name,Schedules::$title.' ')===0) {
				$report['похоже на шаблон, но разошлось'][]=$line.' (шаблон: '.$template.')';
			} else {
				$report['авторское имя'][]=$line;
			}
		}

		return $this->printReport($report,'снятие шаблонных имен');
	}

	/**
	 * Удаляет индивидуальные расписания, оставшиеся без владельца (issue #139).
	 *
	 * Штатно такие расписания удаляются сразу — владелец зовет {@see Schedules::gc()}
	 * через {@see \app\modules\schedules\components\ScheduleOwnerBehavior}. Эта команда
	 * подбирает то, что накопилось раньше (до issue #139 расписание удаленного
	 * временного доступа оставалось сиротой и всплывало в общем списке) или то,
	 * на чем gc споткнулся.
	 *
	 * Использование: yii schedules/gc --dryRun=0
	 *
	 * @return int
	 */
	public function actionGc()
	{
		$report=['удалено'=>[],'осталось (вложения/дочерние расписания)'=>[]];

		/** @var Schedules $schedule */
		foreach (Schedules::find()->where(['override_id'=>null])->each() as $schedule) {
			if (count($schedule->usedBy)) continue;
			if (!$schedule->isPrivate) continue;

			$line='#'.$schedule->id.' '.($schedule->displayName?:'(без имени)');

			if (count($schedule->childrenNonOverrides) || count($schedule->attaches)) {
				$report['осталось (вложения/дочерние расписания)'][]=$line;
				continue;
			}

			if (!$this->dryRun && !Schedules::gc($schedule->id)) {
				$report['осталось (вложения/дочерние расписания)'][]=$line.' - ОШИБКА удаления';
				continue;
			}
			$report['удалено'][]=$line;
		}

		return $this->printReport($report,'сборка мусора');
	}

	/**
	 * Печатает отчет по группам.
	 * @param array $report группа => строки
	 * @param string $title заголовок операции
	 * @return int
	 */
	protected function printReport(array $report, string $title): int
	{
		echo $title.($this->dryRun?" (режим отчета, ничего не изменено)":"").":\n";
		foreach ($report as $group=>$lines) {
			echo "\n  ".$group.': '.count($lines)."\n";
			foreach ($lines as $line) echo "    ".$line."\n";
		}
		if ($this->dryRun) echo "\nЧтобы применить: повторить запуск с --dryRun=0\n";
		return ExitCode::OK;
	}
}
