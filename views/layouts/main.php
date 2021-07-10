<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\components\Alert;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use yii\helpers\Html;

	
AppAsset::register($this);

if (isset($this->params['layout-container'])) {
	$containerClass=$this->params['layout-container'];
} else {
	$containerClass='container container-large';
}

$request=Yii::$app->urlManager->parseRequest(Yii::$app->request);
if (is_array($request)) $path=$request[0];

$this->beginPage() ?>

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

<?php $this->beginBody() ?>

<div class="wrap">

<?= $this->render('menu') ?>
<?php if ($path=='site/login' || \app\models\Users::isViewer()) { ?>
	<div class="<?= $containerClass ?>">
		<?= Breadcrumbs::widget([
			'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
		]) ?>
		<?= Alert::widget() ?>
		<?= $content ?>
	</div>
<?php } else echo $this->render('/site/access-denied') ?>

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
