<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Techs $model
 */

//список оборудования начинаем с самого АРМ
$techs=[
	$model
];
//добавляем оборудование из состава АРМ
foreach ($model->armTechs as $tech) $techs[]=$tech;
foreach ($model->installedTechs as $tech) $techs[]=$tech;

//передаем пользователю АРМ
$user_to=$model->user;

//от ИТ обслуживающего АРМ
$user_from=$model->itStaff;

//организация
$org=$user_to->org;

?>
<div class="arm_act">
	<div class="text-center">
		<?= $org->uname ?>
		<hr>
	</div>
	<div class="row">
		<div class="col-10"></div>
		<div class="col-2">
			<table class="w-100 table table-bordered table-sm border-dark">
				<tr>
					<td class="w-50 text-center">
						Номер документа
					</td>
					<td class="w-50 text-center">
						Дата составления
					</td>
				</tr>
				<tr>
					<td></td>
					<td class="w-50 text-center">
						<?= Yii::$app->formatter->asDate(time()); ?>
					</td>
				</tr>
			</table>
		</div>
	</div>
    <div class="text-center my-2">
        <h1>AKT</h1>
		<strong>передачи во временное пользование учетных единиц, принадлежащих компании <?= $org->uname ?></strong>
    </div>
	<table class="table table-bordered border-dark w-100 text-center my-5">
		<tr>
			<td rowspan="2">
				Номер
			</td>
			<td rowspan="2">
				Отдел
			</td>
			<td rowspan="2">
				Получатель (ФИО)
			</td>
			<td colspan="4">
				Объект основных средств и ТМЦ
			</td>
			<td colspan="2">
				Получено во временное пользование
			</td>
			<td rowspan="2">
				Комментарий (указание дефектов)
			</td>
		</tr>
		<tr>
			<td>
				Наименование ОС или ТМЦ
			</td>
			<td>
				Количество
			</td>
			<td>
				Инвентарный номер
			</td>
			<td>
				Серийный номер
			</td>
			<td>
				Подпись
			</td>
			<td>
				Дата
			</td>
			<td>
			
			</td>
		</tr>
		<tr>
			<td rowspan="<?= count ($techs) ?>">
				1
			</td>
			<td rowspan="<?= count ($techs) ?>">
				<?= $user_to->orgStruct->name ?>
			</td>
			<td rowspan="<?= count ($techs) ?>">
				<?= $user_to->Ename ?>
			</td>
			<?php foreach ($techs as $tech) { ?>
				<td>
					<?= $tech->model->type->name ?>
					<?= $tech->model->nameWithVendor ?>
				</td>
				<td>1</td>
				<td>
					<?= $tech->num ?>
				</td>
				<td>
					<?= $tech->sn ?>
				</td>
				<td></td>
				<td><?= Yii::$app->formatter->asDate(time()); ?></td>
				<td></td>
			</tr><tr>
			<?php } ?>
		</tr>
		
	</table>

	<table class="table table-borderless text-center  w-50">
		<tr>
			<td class="py-4">
				Передал
			</td>
			<td class="py-4">
				<?= $user_from->Doljnost ?>
			</td>
			<td class="py-4">
				_________________
			</td>
			<td class="py-4">
				<?= $user_from->Ename ?>
			</td>
			<td class="py-4">
				<?= Yii::$app->formatter->asDate(time()); ?>
			</td>
		</tr>
		<tr >
			<td class="py-4">
				Принял
			</td>
			<td class="py-4">
				<?= $user_to->Doljnost ?>
			</td>
			<td class="py-4">
				_________________
			</td>
			<td class="py-4">
				<?= $user_to->Ename ?>
			</td>
			<td class="py-4">
				<?= Yii::$app->formatter->asDate(time()); ?>
			</td>
		</tr>
	</table>
	
</div>

