<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\widgets\Alert;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use kartik\nav\NavX;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>

<!--
убрано из виджета в iframe на главной
<script type="text/javascript">
    var reformalOptions = {
        project_id: 980223,
        project_host: "azimutinventory.reformal.ru",
        tab_orientation: "left",
        tab_indent: "50%",
        tab_bg_color: "#34b389",
        tab_border_color: "#FFFFFF",
        tab_image_url: "http://tab.reformal.ru/T9GC0LfRi9Cy0Ysg0Lgg0L%252FRgNC10LTQu9C%252B0LbQtdC90LjRjw==/FFFFFF/4bfb34d91c8d7fb481972ca3c84aec38/left/0/tab.png",
        tab_border_width: 2
    };

    (function() {
        var script = document.createElement('script');
        script.type = 'text/javascript'; script.async = true;
        script.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'media.reformal.ru/widgets/v3/reformal.js';
        document.getElementsByTagName('head')[0].appendChild(script);
    })();
</script><noscript><a href="http://reformal.ru"><img src="http://media.reformal.ru/reformal.png" /></a><a href="http://azimutinventory.reformal.ru">Oтзывы и предложения для База данных инвентаризации корпоративной сети</a></noscript>
-->

<?php
$techTypes=[];
foreach (\app\models\TechTypes::fetchNames() as $idx=>$typeName)  $techTypes[]=['label'=>$typeName,'url' => ['/tech-types/view','id'=>$idx]];
$places=[];
foreach (\app\models\Places::fetchNames(1) as $idx=>$placeName)  $places[]=['label'=>$placeName,'url' => ['/places/view','id'=>$idx]];

$this->beginBody()
?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    try {
	    echo NavX::widget([
		    'options' => ['class' => 'nav navbar-nav navbar-right'],
		    'items' => [
			    ['label' => 'Лицензии',
				    'items' => [
					    ['label' => 'Типы', 'url' => ['/lic-types/index']],
					    ['label' => 'Группы', 'url' => ['/lic-groups/index']],
					    ['label' => 'Закупки', 'url' => ['/lic-items/index']],
					    ['label' => 'Ключи', 'url' => ['/lic-keys/index']],
				    ]
			    ],
			    ['label' => 'Контрагенты',
				    'items' => [
					    ['label' => \app\models\Partners::$title, 'url' => ['/partners/index']],
					    ['label' => \app\models\Contracts::$title, 'url' => ['/contracts/index']],
					    ['label' => \app\models\ProvTel::$title, 'url' => ['/prov-tel/index']],
				    ]
			    ],
			    ['label' => 'Организация',
				    'items' => [
					    ['label' => \app\models\Places::$title, 'url' => ['/places/index'], 'items'=>$places],
					    ['label' => \app\models\OrgPhones::$title, 'url' => ['/org-phones/index']],
					    ['label' => \app\models\OrgInet::$title, 'url' => ['/org-inet/index']],
					    ['label' => \app\models\Services::$title, 'url' => ['/services/index']],
					    ['label' => 'Карта рабочих мест', 'url' => ['/places/armmap']],
				    ]
			    ],
			    ['label' => 'Люди',
				    'items' => [
					    ['label' => \app\models\OrgStruct::$title, 'url' => ['/org-struct/index']],
					    ['label' => \app\models\Users::$title, 'url' => ['/users/index']],
					    ['label' => 'Пользователи', 'url' => ['/users/logins']],
					    ['label' => \app\models\UserGroups::$title, 'url' => ['/user-groups/index']],
				    ]
			    ],
			    ['label' => 'Софт',
				    'items' => [
					    ['label' => 'Разработчики', 'url' => ['/manufacturers/index']],
					    ['label' => 'Продукты', 'url' => ['/soft/index']],
					    ['label' => 'Списки ПО', 'url' => ['/soft-lists/index']],
				    ],
			    ],
			    ['label' => 'Компьютеры',
				    'items' => [
					    ['label' => 'АРМы', 'url' => ['/arms/index']],
					    ['label' => 'ОС', 'url' => ['/comps/index']],
					    ['label' => 'Домены', 'url' => ['/domains/index']],
					    ['label' => \app\models\LoginJournal::$title, 'url' => ['/login-journal/index']],
				    ],
			    ],
                ['label' => \app\models\Techs::$title,
				    'items' => [
					    ['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index'], 'items'=>$techTypes],
					    ['label' => \app\models\TechModels::$title, 'url' => ['/tech-models/index']],
					    ['label' => \app\models\Techs::$title, 'url' => ['/techs/index']],
					    ['label' => \app\models\MaterialsTypes::$title, 'url' => ['/materials-types/index']],
					    ['label' => \app\models\Materials::$title, 'url' => ['/materials/index']],
					    ['label' => \app\models\MaterialsUsages::$title, 'url' => ['/materials-usages/index']],
					    ['label' => 'Производители', 'url' => ['/manufacturers/index']],
					    ['label' => 'Игнорируемое', 'url' => ['/hw-ignore/index']],
					    ['label' => 'Состояния', 'url' => ['/tech-states/index']],
				    ],
			    ],
			    /*Yii::$app->user->isGuest ? (
			    ['label' => 'Login', 'url' => ['/site/login']]
			    ) : (
				    '<li>'
				    . Html::beginForm(['/site/logout'], 'post')
				    . Html::submitButton(
					    'Logout (' . Yii::$app->user->identity->username . ')',
					    ['class' => 'btn btn-link logout']
				    )
				    . Html::endForm()
				    . '</li>'
			    )*/
		    ],
	    ]);
    } catch (Exception $e) {
    }
    NavBar::end();
    ?>

    <div class="container container-large">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container container-large">
        <p class="pull-left">&copy; Инвентаризация <?= date('Y') ?></p>

        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php $this->endBody();
$js = <<<JS
    $('.modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
JS;
$this->registerJs($js);
?>
</body>
</html>
<?php $this->endPage() ?>
