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

$from_name='';		//кто (ФИО)
$from_position='';	//кто (Должность)
$to_name='';		//кому (ФИО)
$to_position='';	//кому (Должность)
$org_name='';		//организация
$dep_name='';		//подразделение


//передаем пользователю АРМ
$user_to=$model->user;
if (!is_object($user_to)) {
	Yii::$app->session->setFlash('error', "Пользователь оборудования не заполнен. Кому передавать?");
} else {
	$to_name=$user_to->Ename;
	$to_position=$user_to->Doljnost;
	
	//организация
	if (!is_object($user_to->org)) {
		Yii::$app->session->setFlash('error', "Организация пользователя не заполнена");
	} else {
		$org=$user_to->org;
		$org_name=$org->uname;
	}
	
	//подразделение
	if (!is_object($user_to->orgStruct)) {
		Yii::$app->session->setFlash('error', "Подразделение пользователя не заполнено");
	} else {
		$dep=$user_to->orgStruct;
		$dep_name=$dep->name;
	}

}

//от ИТ обслуживающего АРМ
$user_from=$model->itStaff;
if (!is_object($user_from)) {
	Yii::$app->session->setFlash('error', "Сотрудник ИТ отдела не заполнен. Кто будет передавать?");
} else {
	$from_name=$user_from->Ename;
	$from_position=$user_from->Doljnost;
}



?>
<div class="arm_act">
	<div class="text-center">
		<?= $org_name ?>
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
		<strong>передачи во временное пользование учетных единиц, принадлежащих компании <?= $org_name ?></strong>
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
		</tr>
		<tr>
			<td rowspan="<?= count ($techs) ?>">
				1
			</td>
			<td rowspan="<?= count ($techs) ?>">
				<?= $dep_name ?>
			</td>
			<td rowspan="<?= count ($techs) ?>">
				<?= $to_name ?>
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
				<?= $from_position ?>
			</td>
			<td class="py-4">
				_________________
			</td>
			<td class="py-4">
				<?= $from_name ?>
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
				<?= $to_position ?>
			</td>
			<td class="py-4">
				_________________
			</td>
			<td class="py-4">
				<?= $to_name ?>
			</td>
			<td class="py-4">
				<?= Yii::$app->formatter->asDate(time()); ?>
			</td>
		</tr>
	</table>
	
</div>
