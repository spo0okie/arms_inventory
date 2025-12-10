<?php

/* @var $this View */
/* @var $content string */


//use kartik\nav\NavX;
use app\models\AccessTypes;
use app\models\Aces;
use app\models\Acls;
use app\models\Contracts;
use app\models\ContractsStates;
use app\models\Departments;
use app\models\LicGroups;
use app\models\LicTypes;
use app\models\LoginJournal;
use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use app\models\Materials;
use app\models\MaterialsTypes;
use app\models\MaterialsUsages;
use app\models\NetDomains;
use app\models\NetIps;
use app\models\NetVlans;
use app\models\Networks;
use app\models\OrgInet;
use app\models\OrgPhones;
use app\models\Partners;
use app\models\Places;
use app\models\Ports;
use app\models\Sandboxes;
use app\models\Schedules;
use app\models\Segments;
use app\models\Services;
use app\models\Tags;
use app\models\TechModels;
use app\models\Techs;
use app\models\TechTypes;
use app\models\Users;
use yii\bootstrap5\NavBar;
use yii\bootstrap5\Nav;
use kartik\bs5dropdown\Dropdown;
use yii\helpers\Html;
use yii\web\View;

$techTypes=[];
foreach (TechTypes::fetchMenuNames() as $idx=> $typeName)
	$techTypes[]=['label'=>$typeName,'url' => ['/tech-types/view','id'=>$idx]];

if (count($techTypes)) $techTypes[]='-';
$techTypes[]=['label' => 'Список категорий','url' => ['/tech-types/index']];


$places=[];
foreach (Places::fetchNames(1) as $idx=> $placeName)
	$places[]=['label'=>$placeName,'url' => ['/places/view','id'=>$idx]];

if (count($places)) $places[]='-';
$places[]=['label' => 'Список помещений','url' => ['/places/index']];
	

$ipams=[];
foreach (Yii::$app->params['ipamRanges'] as $ipamRange) {
	$ipams[]=[
		'label'=>'IPAM '.$ipamRange['baseIp'].'/'.$ipamRange['maxPrefix'],
		'url'=>array_merge(['/networks/ipam'],$ipamRange)
	];
}

NavBar::begin([
	'brandLabel' => '<i class="fas fa-dice-d6"></i> '.Yii::$app->name,
	'brandUrl' => Yii::$app->homeUrl,
	'options' => [
		'class' => ['navbar-dark', 'bg-dark', 'navbar-expand-md'],
	],
	'innerContainerOptions' => ['class' => 'container-fluid text-center'],
]);
	echo Users::isViewer()?Nav::widget([
		'options' => ['class' => ['nav', 'navbar-nav', 'mx-auto']],
		'dropdownClass' => Dropdown::class,
		'items' => [
			['label' => 'Лицензии',
				'items' => [
					['label' => LicTypes::$titles, 'url' => ['/lic-types/index']],
					['label' => LicGroups::$titles, 'url' => ['/lic-groups/index']],
					['label' => 'Закупки', 'url' => ['/lic-items/index']],
					['label' => 'Ключи', 'url' => ['/lic-keys/index']],
				]
			],
			['label' => 'Контрагенты',
				'items' => [
					['label' => Contracts::$titles, 'url' => ['/contracts/index']],
					['label' => Partners::$titles, 'url' => ['/partners/index']],
					['label' => ContractsStates::$title, 'url' => ['/contracts-states/index']],
				]
			],
			['label' => 'Организация',
				'items' => [
					['label' => Places::$titles, 'items'=>$places, 'class'=>'dropdown-menu dropdown-submenu'],
					['label' => Users::$titles, 'url' => ['/users/index']],
					Yii::$app->params['departments.enable']?['label' => Departments::$titles, 'url' => ['/departments/index']]:'',
					['label' => OrgPhones::$titles, 'url' => ['/org-phones/index']],
					['label' => OrgInet::$titles, 'url' => ['/org-inet/index']],
					//'<li class="divider"></li>',
					['label' => 'Карта рабочих мест', 'url' => ['/places/armmap']],
					Yii::$app->params['departments.enable']?['label' => 'По подразделениям', 'url' => ['/places/depmap']]:'',
				]
			],
			['label' => Services::$titles,
				'items' => [
					['label' => Services::$titles, 'url' => ['/services/index']],
					['label' => MaintenanceReqs::$titles, 'url' => ['/maintenance-reqs/index']],
					['label' => MaintenanceJobs::$titles, 'url' => ['/maintenance-jobs/index']],
					['label' => Schedules::$titles, 'url' => ['/schedules/index']],
					['label' => Tags::$titles, 'url' => ['/tags/index']],
				]
			],
			['label' => 'Доступы',
				'items' => [
					['label' => Aces::$titles, 'url' => ['/aces/index']],
					['label' => Acls::$titles, 'url' => ['/acls/index']],
					['label' => Acls::$scheduleTitles, 'url' => ['/scheduled-access/index']],
					['label' => AccessTypes::$titles, 'url' => ['/access-types/index']],
				]
			],
			['label' => 'Сети',
				'items' => array_merge([
					['label' => Ports::$titles, 'url' => ['/ports/index']],
					['label' => NetIps::$titles, 'url' => ['/net-ips/index']],
					['label' => Networks::$titles, 'url' => ['/networks/index']],
					['label' => NetVlans::$titles, 'url' => ['/net-vlans/index']],
					['label' => NetDomains::$titles, 'url' => ['/net-domains/index']],
					['label' => Segments::$titles, 'url' => ['/segments/index']],
				],$ipams)
			],
			['label' => 'Компьютеры',
				'items' => [
					['label' => 'АРМы', 'url' => ['/arms/index']],
					['label' => 'ОС', 'url' => ['/comps/index']],
					['label' => 'Домены', 'url' => ['/domains/index']],
					['label' => Sandboxes::$titles, 'url' => ['/sandboxes/index']],
					['label' => LoginJournal::$title, 'url' => ['/login-journal/index']],
					['label' => 'Дубликаты', 'url' => ['/comps/dupes?sort=name']],
					['label' => 'Софт',
						'items' => [
							['label' => 'Разработчики', 'url' => ['/manufacturers/index']],
							['label' => 'Продукты', 'url' => ['/soft/index']],
							['label' => 'Списки ПО', 'url' => ['/soft-lists/index']],
						],
					]
				],
			],
			['label' => Techs::$title,
				'items' => [
					['label' => Materials::$title,
						'items' => [
							['label' => Materials::$title, 		 'url' => ['/materials/index']],
							['label' => MaterialsUsages::$title, 'url' => ['/materials-usages/index']],
							['label' => MaterialsTypes::$title,  'url' => ['/materials-types/index']],
						],
					],
					['label' => TechTypes::$title, 'url' => ['/tech-types/index'], 'items'=>$techTypes],
					['label' => TechModels::$titles, 'url' => ['/tech-models/index']],
					['label' => Techs::$title, 'url' => ['/techs/index']],
					['label' => 'Производители', 'url' => ['/manufacturers/index']],
					['label' => 'Игнорируемое', 'url' => ['/hw-ignore/index']],
					['label' => 'Состояния', 'url' => ['/tech-states/index']],
				],
			],
		],
	]):'<div class="mx-auto"></div>';
/** @noinspection PhpPossiblePolymorphicInvocationInspection */
echo Nav::widget([
		'options' => ['class' => ['nav', 'navbar-nav', 'navbar-right']],
		'dropdownClass' => Dropdown::class,
		'items' => [
			Users::isAdmin()?
				['label' => '<i class="fa fa-cog"></i>',
					'encode'=>false,
					'dropdownOptions' => ['class'=>'dropdown-menu-end'],
					'items' => [
						['label' => 'Пользователи', 'url' => ['/users/index']],
						['label' => 'Роли', 		'url' => ['/rbac/role']],
						['label' => 'Правила', 		'url' => ['/rbac/rule']],
						['label' => 'Разрешения', 	'url' => ['/rbac/permission']],
						['label' => 'Документация API', 	'url' => ['/site/api-doc']],
					],
				]:'',
			Yii::$app->user->isGuest ?
				['label' => 'Вход', 'url' => ['/site/login']]:
				['label' => Yii::$app->user->identity->shortName,
					'dropdownOptions' => ['class'=>'dropdown-menu-end'],
					'items'=>[
						(Yii::$app->params['localAuth']??false)?[
							'label' => 'Сменить пароль', 'linkOptions'=>['onclick'=>'open-in-modal-form'],'url'=>['/site/password-set','id'=>Yii::$app->user->identity->id]
						]:'',
						['label' => 'Выход', 'linkOptions'=>['onclick'=>'$("#logout-form").submit();'],'url'=>'#'],
					]
				]
			
		]
	]);
NavBar::end();
echo Html::beginForm(['/site/logout'], 'post',['id'=>'logout-form','style'=>'display:none']);
echo Html::endForm();
