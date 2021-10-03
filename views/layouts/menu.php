<?php

/* @var $this \yii\web\View */
/* @var $content string */


//use kartik\nav\NavX;
use yii\bootstrap5\NavBar;
use yii\bootstrap5\Nav;
use kartik\bs5dropdown\Dropdown;

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
		'class' => ['navbar-dark', 'bg-dark', 'navbar-expand-md'],
	],
	//'innerContainerOptions' => ['class' => 'container container-large'],
]);
	echo Nav::widget([
		'options' => ['class' => ['nav', 'navbar-nav', 'navbar-right']],
		'dropdownClass' => Dropdown::classname(),
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
					['label' => \app\models\Partners::$titles, 'url' => ['/partners/index']],
					['label' => \app\models\ContractsStates::$title, 'url' => ['/contracts-states/index']],
					['label' => \app\models\Contracts::$titles, 'url' => ['/contracts/index']],
					['label' => \app\models\ProvTel::$title, 'url' => ['/prov-tel/index']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Организация',
				'items' => [
					['label' => \app\models\Places::$titles,
						'items'=>$places,
						'class'=>'dropdown-menu dropdown-submenu'
					],
					['label' => \app\models\Orgs::$title, 'url' => ['/orgs/index']],
					['label' => \app\models\Departments::$title, 'url' => ['/departments/index']],
					['label' => \app\models\OrgPhones::$title, 'url' => ['/org-phones/index']],
					['label' => \app\models\OrgInet::$title, 'url' => ['/org-inet/index']],
					['label' => \app\models\Services::$titles, 'url' => ['/services/index']],
					['label' => \app\models\Schedules::$titles, 'url' => ['/schedules/index']],
					//'<li class="divider"></li>',
					['label' => 'Карта рабочих мест', 'url' => ['/places/armmap']],
					['label' => 'По подразделениям', 'url' => ['/places/depmap']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Доступы',
				'items' => [
					['label' => \app\models\Acls::$titles, 'url' => ['/acls/index']],
					['label' => \app\models\Acls::$scheduleTitles, 'url' => ['/schedules/index-acl']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Люди',
				'items' => [
					//['label' => \app\models\OrgStruct::$title, 'url' => ['/org-struct/index']],
					['label' => \app\models\Users::$titles, 'url' => ['/users/index']],
					//['label' => 'Пользователи', 'url' => ['/users/logins']],
					['label' => \app\models\UserGroups::$title, 'url' => ['/user-groups/index']],
				]
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Сети',
				'items' => [
					['label' => \app\models\Ports::$titles, 'url' => ['/ports/index']],
					['label' => \app\models\NetIps::$titles, 'url' => ['/net-ips/index']],
					['label' => \app\models\Networks::$title, 'url' => ['/networks/index']],
					['label' => \app\models\NetVlans::$title, 'url' => ['/net-vlans/index']],
					['label' => \app\models\NetDomains::$title, 'url' => ['/net-domains/index']],
					['label' => \app\models\Segments::$titles, 'url' => ['/segments/index']],
				],
			]:'',
			\app\models\Users::isViewer()?
			['label' => 'Компьютеры',
				'items' => [
					['label' => 'АРМы', 'url' => ['/arms/index']],
					['label' => 'ОС', 'url' => ['/comps/index']],
					['label' => 'Домены', 'url' => ['/domains/index']],
					['label' => \app\models\LoginJournal::$title, 'url' => ['/login-journal/index']],
					['label' => 'Дубликаты', 'url' => ['/comps/dupes?sort=name']],
					['label' => 'Софт',
						'items' => [
							['label' => 'Разработчики', 'url' => ['/manufacturers/index']],
							['label' => 'Продукты', 'url' => ['/soft/index']],
							['label' => 'Списки ПО', 'url' => ['/soft-lists/index']],
						],
					]
				],
			]:'',
			\app\models\Users::isViewer()?
			['label' => \app\models\Techs::$title,
				'items' => [
					['label' => \app\models\Materials::$title,
						'items' => [
							['label' => \app\models\Materials::$title, 'url' => ['/materials/index']],
							['label' => \app\models\MaterialsUsages::$title, 'url' => ['/materials-usages/index']],
							['label' => \app\models\MaterialsTypes::$title, 'url' => ['/materials-types/index']],
						],
					],
					['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index'], 'items'=>$techTypes],
					['label' => \app\models\TechModels::$title, 'url' => ['/tech-models/index']],
					['label' => \app\models\Techs::$title, 'url' => ['/techs/index']],
					['label' => 'Производители', 'url' => ['/manufacturers/index']],
					['label' => 'Игнорируемое', 'url' => ['/hw-ignore/index']],
					['label' => 'Состояния', 'url' => ['/tech-states/index']],
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
				['label' => 'Выход (' . Yii::$app->user->identity->shortName . ')','linkOptions'=>['onclick'=>'$("#logout-form").submit();']]
			)
		],
	]);
NavBar::end();
echo \yii\helpers\Html::beginForm(['/site/logout'], 'post',['id'=>'logout-form','style'=>'display:none']);
echo \yii\helpers\Html::endForm();
