<?php

use kartik\datecontrol\Module;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\View;

$params = ArrayHelper::merge(
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);
$db = ArrayHelper::merge(
	require __DIR__ . '/db.php',
	require __DIR__ . '/db-local.php'
);
$ldap = require __DIR__ . '/ldap.php';

$config = [
    'id' => 'arms',
    'name' => 'Инвентаризация',
	'language' => 'ru-RU',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
	'timeZone' => 'Asia/Yekaterinburg', // : Yii::$app->user->identity->timeZone ,//->php_name,
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
		'assetManager' => [
			'bundles' => [
				'yii\web\JqueryAsset' => [
					'jsOptions' => [ 'position' => View::POS_HEAD ],
				],
				'kartik\select2\Select2Asset' => [
					'jsOptions' => [ 'position' => View::POS_HEAD ],
				],
			]
		],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'pfvenO-s_B_jDeOjN-uM2tJ1eh_TVzyb',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableAutoLogin' => true,
        ],
	    'authManager' => [
		    'class' => 'yii\rbac\DbManager',
	    ],
	    'formatter' => [
	    	'locale' => 'ru-RU',
		    'dateFormat' => 'dd.MM.y',
		    'datetimeFormat' => 'dd.MM.y HH:mm:ss',
		    'currencyCode' => '',
		    'numberFormatterSymbols' => [
			    NumberFormatter::CURRENCY_SYMBOL => '',
		    ],
		    'numberFormatterOptions' => [
			    NumberFormatter::MIN_FRACTION_DIGITS => 0,
			    NumberFormatter::MAX_FRACTION_DIGITS => 2,
		    ]
	    ],
	    'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
	    'ldap' => $ldap,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => [
						'comps'=>'api/comps',
						'contracts'=>'api/contracts',
						'domains'=>'api/domains',
						'lic-groups'=>'api/lic-groups',
						'lic-types'=>'api/lic-types',
						'login-journal'=>'api/login-journal',
						'manufacturers'=>'api/manufacturers',
						'manufacturers-dict'=>'api/manufacturers-dict',
						'net-ips'=>'api/net-ips',
						'org-struct'=>'api/org-struct',
						'partners'=>'api/partners',
						'phones'=>'api/phones',
						'scans'=>'api/scans',
						'services'=>'api/services',
						'soft'=>'api/soft',
						'soft-lists'=>'api/soft-lists',
						'techs'=>'api/techs',
						'tech-models'=>'api/tech-models',
						'tech-types'=>'api/tech-types',
						'users'=>'api/users',
                    ],
					
                    'pluralize' => false,
                    'prefix' => 'api'
                ],
                'api/domains/<id:[\.\w-]+>' => 'api/domains/view',
                'api/comps/<domain:[\w-]+>/<name:[\w-]+>' => 'api/comps/search',
            ],
        ],
	    'i18n' => [
		    'translations' => [
			    'kvgrid' => [
				    'class' => 'yii\i18n\PhpMessageSource',
				    'basePath' => '@vendor/kartik-v/yii2-grid/messages',
			    ],
		    ],
	    ],
    ],
    'modules' => [
        'api'       => ['class' => 'app\modules\api\Rest'],
		'gridview'  => ['class' => 'kartik\grid\Module'],
		'dynagrid'	=> [
			'class' => '\kartik\dynagrid\Module',
			'minPageSize' => 20,
			'maxPageSize' => 1000,
			'defaultPageSize' => 100,
			'dbSettings' => ['tableName'=>'ui_dynagrid'],
			'dbSettingsDtl' => ['tableName'=>'ui_dynagrid_dtl'],
		],
	    'rbac'      => [
		    'class' => 'spo0okie\rbacplus\Module',
		    'userModelLoginField'=>'Login',
			'beforeCreateController'=>function($route){
				/** @var string $route The route consisting of module, controller and action IDs. */
				if (!is_object($user= Yii::$app->user->identity) || !$user->isAdmin())
					throw new ForbiddenHttpException('Access denied');
				return $route;
			}
	    ],
		'markdown' => [
			'class' => 'kartik\markdown\Module',
			'i18n' => [
				'class' => 'yii\i18n\PhpMessageSource',
				'basePath' => '@vendor/kartik-v/yii2-markdown/messages',
				'forceTranslation' => true
			],
		],
		'datecontrol' =>  [
			'class' => 'kartik\datecontrol\Module',
			'displaySettings' => [
				Module::FORMAT_DATE => 'php:Y-m-d',
				Module::FORMAT_TIME => 'php:H:i:s',
				Module::FORMAT_DATETIME => 'php:Y-m-d H:i:s',
			],
			'saveSettings' => [
				Module::FORMAT_DATE => 'php:Y-m-d',
				Module::FORMAT_TIME => 'php:H:i:s',
				Module::FORMAT_DATETIME => 'php:Y-m-d H:i:s',
			],
			'autoWidget' => true,
			'autoWidgetSettings' => [
				Module::FORMAT_DATE => ['type'=>2, 'pluginOptions'=>['autoclose'=>true]], // example
				Module::FORMAT_DATETIME => [], // setup if needed
				Module::FORMAT_TIME => [], // setup if needed
			],
		],
    ],
    'params' => $params,
];

Yii::$container->set('yii\data\Pagination', ['pageSizeLimit' => [0, 10000]]);

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
	    'generators'=> [
		    'crud' => [
			    'class' => 'yii\gii\generators\crud\Generator', // generator class
			    'templates' => [ //setting for out templates
				    'arms' => '@app/templates/crud/arms', // template name => path to template
			    ]
		    ],
	    	'model' => [
				'class' => 'yii\gii\generators\model\Generator', // generator class
				'templates' => [ //setting for out templates
					'arms' => '@app/templates/model/arms', // template name => path to template
				]
			]
	    ]
    ];
}
Yii::$classMap['yii\helpers\Url'] = dirname(__DIR__) . '/helpers/Url.php';
return ArrayHelper::merge(
	$config,
	require __DIR__ . '/web-local.php'
);
