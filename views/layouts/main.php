<?php

/* @var $this \yii\web\View */
/* @var $content string */

use a1inani\yii2ModalAjax\ModalAjax;
use app\components\Alert;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
//use app\assets\FontAwesomeAsset;
use yii\helpers\Html;

	
AppAsset::register($this);
//FontAwesomeAsset::register($this);

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
		<?= \yii\bootstrap5\Breadcrumbs::widget([
			'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
		]) ?>
		<?= Alert::widget() ?>
		<?= $content ?>
	</div>
<?php } else echo $this->render('/site/access-denied') ?>

</div>

<footer class="footer">
	<div class="container container-large">
		<span class="float-start">&copy; Инвентаризация <?= date('Y') ?></span>

		<span class="float-end"><?= Yii::powered() ?></span>
	</div>
</footer>

<?php

$js = <<<JS
function(event, data, status, xhr, selector) {
    console.log('Got modal commit ('+status+')');
	if (status!=='success') {
	    if (data) {
			$(this)
				.find('div.modal-body')
				.html(data);
	    }
		$(this)
			.find('div.for-alert')
			.html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
				'Не удалось сохранить'+
				'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'+
			'</div>').show();
	} else {
		if (selector.attr('data-update-element') && selector.attr('data-update-url')) {
			console.log('url:'+selector.attr('data-update-url'));
			$.ajax({
				url: selector.attr('data-update-url'),
				success: function(data,status,xhr) {
					$(selector.attr('data-update-element')).replaceWith(data);
				}
			});
		}
		
		if (selector.attr('data-click-on-submit')) {
			$(selector.attr('data-click-on-submit')).click();
		}
		
		if (selector.attr('data-reload-page-on-submit')) {
		    console.log('reloading page')
			window.location.reload();
		}

		$(this).modal('toggle');
	}
}
JS;


echo ModalAjax::widget([
	'id' => 'modal_form_loader',
	'bootstrapVersion' => ModalAjax::BOOTSTRAP_VERSION_5,
	'header' => 'Правка',
	'selector' => 'a.open-in-modal-form',
	'ajaxSubmit' => true, // Submit the contained form as ajax, true by default
	'size' => ModalAjax::SIZE_EXTRA_LARGE,
	'options' => ['class' => 'header-secondary text-black text-left'],
	'autoClose' => true,
	'events'=>[
		ModalAjax::EVENT_MODAL_SHOW => new \yii\web\JsExpression("
            	function(event, data, status, xhr, selector) {
                	selector.addClass('modal-open');
            	}
       		"),
		ModalAjax::EVENT_MODAL_SUBMIT => new \yii\web\JsExpression($js),
	],
]); ?>

<?php $this->endBody();
$js = <<<JS
$('.modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
JS;
$this->registerJs($js);
?>
</body>
</html>
<?php $this->endPage() ?>
