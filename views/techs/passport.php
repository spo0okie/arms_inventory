<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Techs $model
 */

use yii\bootstrap5\Modal;

\yii\helpers\Url::remember();

$manufacturers=\app\models\Manufacturers::fetchNames();
?>
<div class="arm_passport">

    <?php
    $unknown=[];
    $unagreed=[];
    $agreed=[];
    $free=[];
    $ignored=[];
    $signed=[];


    foreach ($model->comps as $comp) {


        if (count($comp->swList->items)) {
            $comp->swList->sortByName();
            foreach ($comp->swList->items as $item) {
	            if ($item['ignored']) {
		            $ignored[] = $this->render('/swlist/item', ['model' => $comp, 'item' => $item]);
	            } elseif ($item['free']) {
		            $free[] = $this->render('/swlist/item', ['model' => $comp, 'item' => $item]);
                } else {
		            if ($item['saved']) {
			            $signed[] = $this->render('/swlist/item', ['model' => $comp, 'item' => $item]);
		            } else {
			            if ($item['agreed'])
				            $agreed[] = $this->render('/swlist/item', ['model' => $comp, 'item' => $item]);

			            if (!$item['agreed'])
				            $unagreed[] = $this->render('/swlist/item', ['model' => $comp, 'item' => $item]);
		            }

	            }
            }
        }
        if (is_object($comp->swList) && is_array($comp->swList->data)) foreach ($comp->swList->data as $item) {
            $unknown[] = $this->render('/swlist/item_unrecognized',
                ['model' => $comp, 'item' => $item]
            );
        }
    }

?>

    <div class="header">
        <h1>ПАСПОРТ</h1>
        персонального компьютера<br />
        <h3>Номер:</h3> <span class="underlined"><?= $model->num ?></span>
    </div>

    <br />

    <div class="comp">
        <h3>Компьютер:</h3>
        <table>
            <tr>
                <td>Домен\имя:</td>
                <td><?= $model->name ?></td>
                <td>Инвентарный номер:</td>
                <td><?= $model->inv_num ?></td>
            </tr>
            <tr>
                <td>Модель:</td>
                <td><?= $this->render('/tech-models/item',['model'=>$model->model]) ?></td>
                <td>Серийный номер:</td>
                <td><?= $model->sn ?></td>
            </tr>
        </table>
    </div>

    <br />

    <div class="user">
        <h3>Пользователь:</h3>
        <table>
            <tr>
                <td>ФИО:</td>
                <td><?= is_object($model->user)?$model->user->fullName:'-не назначен-' ?></td>
                <td>Учетная запись:</td>
                <td><?= is_object($model->user)?$model->user->login:'-не назначен-'  ?></td>
            </tr>
            <tr>
                <td>Подразделение:</td>
                <td><?= is_object($model->user)?$model->user->structName:'-не назначен-'  ?></td>
                <td>Примечание:</td>
                <td></td>
            </tr>
        </table>
    </div>

    <br />

    <div class="soft"><?php

	    if (count($ignored)) { ?>
            <div class="soft_ignored passport_tools">
                <h3 id="ignored_hdr">Игнорируемое ПО</h3>
                <div id="ignored_items"><?= $this->render('/swlist/2cols-list',['items'=>$ignored]) ?></div>
	            <?php foreach ($model->comps as $comp) {
		            if ($comp->swList->hasSavedIgnored()) echo \yii\helpers\Html::a('Убрать из паспорта игнорируемое ПО на '.$comp->name,
			            ['/comps/rmsw','id'=>$comp->id,'items'=>implode(',',$comp->swList->getSavedIgnored())],
			            [
				            'class'=>'btn btn-warning passport_tools',
				            'title'=>'Убрать из паспорта все игнорируемое ПО на ОС '.$comp->name,
				            'data' => [
					            'confirm' => 'Убираем все игнорируемое ПО обнаруженное на ОС '.$comp->name.'?',
				            ]
			            ]
		            );
	            } ?>
            </div>
        <?php }

        if (count($free)) { ?>
            <div class="soft_ignored passport_tools">
                <h3 id="free_hdr">Бесплатное ПО</h3>
                <div id="free_items"><?= $this->render('/swlist/2cols-list',['items'=>$free]) ?></div>
	            <?php foreach ($model->comps as $comp) {
		            if ($comp->swList->hasSavedFree()) echo \yii\helpers\Html::a('Убрать из паспорта бесплатное ПО на '.$comp->name,
			            ['/comps/rmsw','id'=>$comp->id,'items'=>implode(',',$comp->swList->getSavedFree())],
			            [
				            'class'=>'btn btn-warning passport_tools',
				            'title'=>'Убрать из паспорта все бесплатное ПО на ОС '.$comp->name,
				            'data' => [
					            'confirm' => 'Убираем все бесплатное ПО обнаруженное на ОС '.$comp->name.'?',
				            ]
			            ]
		            );
	            } ?>
            </div>
        <?php }

        if (count($unknown)) { ?>
        <div class="soft_unknown passport_tools">
            <h3 id="unknown_hdr">На компьютере обнаружено неизвестное ПО</h3>
            <div id="unknown_items"><?= $this->render('/swlist/list',['items'=>$unknown]) ?></div>
        </div>
        <?php }

        if (count($unagreed)) { ?>
            <div class="soft_unagreed passport_tools">
                <h3 id="unagreed_hdr">На компьютере обнаружено ПО не из реестра</h3>
                <div id="unagreed_items"><?= $this->render('/swlist/2cols-list',['items'=>$unagreed]) ?></div>
            </div>
        <?php }

        if (count($agreed)) { ?>
            <div class="soft_agreed passport_tools">
                <h3 id="agreed_hdr">На компьютере обнаружено платное ПО не из паспорта</h3>
                <div id="agreed_items">
                    <?= $this->render('/swlist/2cols-list',['items'=>$agreed]) ?>
                    <?php foreach ($model->comps as $comp) {
                        if ($comp->swList->hasUnsavedAgreed()) echo \yii\helpers\Html::a('Внести в паспорт ПО на '.$comp->name,
                            ['/comps/addsw','id'=>$comp->id,'items'=>'sign-all'],
                            [
                                'class'=>'btn btn-warning passport_tools',
                                'title'=>'Добавить в паспорт все согласованное ПО на ОС '.$comp->name,
                                'data' => [
                                    'confirm' => 'Вы уверены, что в паспорт нужно внести все согласованное ПО обнаруженное на ОС '.$comp->name.'? Текущее состояние паспорта нигде не будет сохранено!',
                                ]
                            ]
                        );
                    } ?>
                </div>
            </div>
        <?php } ?>

        <h3>Программное обеспечение:</h3>
        <?= $this->render('/swlist/2cols-list',['items'=>$signed]) ?>
    </div>

    <br />

    <div class="hardware">
        <h3>Аппаратное обеспечение:</h3>
        <?= $this->render('hw',compact('model','manufacturers')) ?>
        <?php if ($model->hwList->hasUnsaved()) { ?>
            <?= \yii\helpers\Html::a('Согласовать оборудование',
                ['updhw','id'=>$model->id,'uid'=>'sign-all'],
                [
                    'class'=>'btn btn-warning passport_tools',
                    'title'=>'Добавить в паспорт все недобавленное оборудование',
                    'data' => [
                        'confirm' => 'Вы уверены, что в паспорт нужно внести все оборудование, которое еще не внесено?',
                    ]
                ]
           ) ?>
        <?php } ?>
    </div>

    <br />

    <div class="techs">
        <h3>Доп. оборудование:</h3>
        <?php if ($model->armTechsCount) { ?>
        <table>
            <thead>
            <th>Тип</th>
            <th>Модель</th>
            <th>Идентификатор</th>
            <th>Серийный номер</th>
            <th>Инвентарный номер</th>
            </thead>
            <?php foreach ($model->armTechs as $tech) echo '<tr>'.$this->render('/techs/passport-row',['model'=>$tech]).'</tr>' ?>
        </table>

        <?php } else { ?>
            <p>Отсутствует</p>
        <?php }

        echo \yii\helpers\Html::a(
			'Внести доп. оборудование',
			[
				'/techs/create',
				'Techs[arms_id]'=>$model->id
			],[
				'class'=>'open-in-modal-form btn btn-success passport_tools',
				'data-reload-page-on-submit'=>1,
			]
		);?>

    </div>

    <br />

    <div class="passport_footer">
        <p>
            Сотрудник дирекции ИТ:_________________ <?= is_object($model->itStaff)?$model->itStaff->fullName:'________________________________________' ?>
        </p>
        <p>
            Ознакомлены с правилами использования апаратно-программного обеспечения,
            Реестром разрешенного программного обеспечения и подтверждаем информацию в данном Паспорте персонального компьютера:
        </p>
        <p>
            Руководитель структурного подразделения: _________________ <?= is_object($model->head)?$model->head->fullName:'________________________________________' ?>
        </p>
        <p>
            Сотрудник: _________________ <?= is_object($model->user)?$model->user->fullName:'________________________________________' ?>
        </p>
        <?php if  (is_object($model->responsible)) {?>
        <p>
            <i>
            Установку и изменение аппаратно-программной конфигурации данном ПК осуществляет:
            Сотрудник: _________________ <?= $model->responsible->fullName ?>
            </i>
        </p>
        <?php } ?>
        <p>
            Дата "___"_______________ 202_г.
        </p>
    </div>

</div>

