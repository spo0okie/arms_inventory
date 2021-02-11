<?php

/* @var $this \yii\web\View */
/* @var $content string */


use yii\bootstrap\NavBar;
use kartik\nav\NavX;

$techTypes=[];
foreach (\app\models\TechTypes::fetchNames() as $idx=>$typeName)
	$techTypes[]=['label'=>$typeName,'url' => ['/tech-types/view','id'=>$idx]];

$places=[];
foreach (\app\models\Places::fetchNames(1) as $idx=>$placeName)
	$places[]=['label'=>$placeName,'url' => ['/places/view','id'=>$idx]];
	

NavBar::begin([
	'brandLabel' => Yii::$app->name,
	'brandUrl' => Yii::$app->homeUrl,
	'options' => [
		'class' => 'navbar-inverse navbar-fixed-top',
	],
	'innerContainerOptions' => ['class' => 'container container-large'],
]);
	echo NavX::widget([
		'options' => ['class' => 'nav navbar-nav navbar-right'],
		'items' => [
			\app\models\Users::isViewer()?
			['label' => 'Лицензии',
				'items' => [
					['label' => \app\models\LicTypes::$title, 'url' => ['/lic-types/index']],
					['label' => 'Группы', 'url' => ['/lic-groups/index']],
					['label' => 'Закупки', 'url' => ['/lic-items/index']],
					['label' => 'Ключи', 'url' => ['/lic-keys/index']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Контрагенты',
				'items' => [
					['label' => \app\models\Partners::$title, 'url' => ['/partners/index']],
					['label' => \app\models\ContractsStates::$title, 'url' => ['/contracts-states/index']],
					['label' => \app\models\Contracts::$title, 'url' => ['/contracts/index']],
					['label' => \app\models\ProvTel::$title, 'url' => ['/prov-tel/index']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Организация',
				'items' => [
					['label' => \app\models\Places::$title, 'url' => ['/places/index'], 'items'=>$places],
					['label' => \app\models\Orgs::$title, 'url' => ['/orgs/index']],
					['label' => \app\models\Departments::$title, 'url' => ['/departments/index']],
					['label' => \app\models\OrgPhones::$title, 'url' => ['/org-phones/index']],
					['label' => \app\models\OrgInet::$title, 'url' => ['/org-inet/index']],
					['label' => \app\models\Services::$title, 'url' => ['/services/index']],
					['label' => \app\models\Schedules::$title, 'url' => ['/schedules/index']],
					'<li class="divider"></li>',
					['label' => 'Карта рабочих мест', 'url' => ['/places/armmap']],
					['label' => 'По подразделениям', 'url' => ['/places/depmap']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Люди',
				'items' => [
					//['label' => \app\models\OrgStruct::$title, 'url' => ['/org-struct/index']],
					['label' => \app\models\Users::$title, 'url' => ['/users/index']],
					['label' => 'Пользователи', 'url' => ['/users/logins']],
					['label' => \app\models\UserGroups::$title, 'url' => ['/user-groups/index']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Софт',
				'items' => [
					['label' => 'Разработчики', 'url' => ['/manufacturers/index']],
					['label' => 'Продукты', 'url' => ['/soft/index']],
					['label' => 'Списки ПО', 'url' => ['/soft-lists/index']],
				],
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Компьютеры',
				'items' => [
					['label' => 'АРМы', 'url' => ['/arms/index']],
					['label' => 'ОС', 'url' => ['/comps/index']],
					['label' => 'Домены', 'url' => ['/domains/index']],
					['label' => \app\models\Segments::$title, 'url' => ['/segments/index']],
					['label' => \app\models\LoginJournal::$title, 'url' => ['/login-journal/index']],
					['label' => 'Дубликаты', 'url' => ['/comps/dupes']],
				],
			]:'',
			\app\models\Users::isViewer()?
			['label' => \app\models\Techs::$title,
				'items' => [
					['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index'], 'items'=>$techTypes],
					['label' => \app\models\TechModels::$title, 'url' => ['/tech-models/index']],
					['label' => \app\models\Techs::$title, 'url' => ['/techs/index']],
					['label' => 'Производители', 'url' => ['/manufacturers/index']],
					['label' => 'Игнорируемое', 'url' => ['/hw-ignore/index']],
					['label' => 'Состояния', 'url' => ['/tech-states/index']],
				],
			]:'',
			\app\models\Users::isViewer()?
			['label' => \app\models\Materials::$title,
				'items' => [
					['label' => \app\models\MaterialsTypes::$title, 'url' => ['/materials-types/index']],
					['label' => \app\models\Materials::$title, 'url' => ['/materials/index']],
					['label' => \app\models\MaterialsUsages::$title, 'url' => ['/materials-usages/index']],
				],
			]:'',
			\app\models\Users::isAdmin()?
				['label' => 'Администрирование',
				'items' => [
					['label' => 'Пользователи', 'url' => ['/users/index']],
					['label' => 'Роли', 'url' => ['/rbac/role']],
					['label' => 'Правила', 'url' => ['/rbac/rule']],
					['label' => 'Разрешения', 'url' => ['/rbac/permission']],
					//['label' => 'Назначение', 'url' => ['/rbac/assignment']],
				],
			]:'',
			Yii::$app->user->isGuest ? (
			['label' => 'Вход', 'url' => ['/site/login']]
			) : (
				'<li>'
				. \yii\helpers\Html::beginForm(['/site/logout'], 'post')
				. \yii\helpers\Html::submitButton(
					'Выход (' . Yii::$app->user->identity->shortName . ')',
					['class' => 'btn btn-link logout']
				)
				. \yii\helpers\Html::endForm()
				. '</li>'
			)
		],
	]);
NavBar::end();
