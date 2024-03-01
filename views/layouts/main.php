<?php

/* @var $this View */
/* @var $content string */

use a1inani\yii2ModalAjax\ModalAjax;
use app\components\Alert;
use app\components\TabsWidget;
use app\models\Users;
use yii\bootstrap5\Breadcrumbs;
use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;


AppAsset::register($this);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/png', 'href' => Url::to(['/favicon.png'])]);

if (isset($this->params['layout-container'])) {
	$containerClass=$this->params['layout-container'];
} else {
	$containerClass='container container-large';
}

$request=Yii::$app->urlManager->parseRequest(Yii::$app->request);
$path=is_array($request)?$request[0]:'';

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
	<?php if ($path=='site/login' || Users::isViewer()) {
		if (isset($this->params['navTabs'])) { ?>
			<div class="nav-header">
				<?= Breadcrumbs::widget([
					'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
				]) ?>
				<?= Alert::widget() ?>
				<?= $this->params['headerWidgets']??'' ?>
				<div class="px-5"><?= $this->params['headerContent'] ?></div>
			</div>
			<?= TabsWidget::widget(array_merge([
				'items'=>$this->params['navTabs'],
				'options'=>['class'=>'nav-header'],
				'encodeLabels'=>false,
			],$this->params['tabsParams']??[])); ?>
		<?php } elseif (isset($this->params['headerContent'])) { ?>
			<div class="nav-header">
				<?= Breadcrumbs::widget([
					'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
				]) ?>
				<?= Alert::widget() ?>
				<div class="px-5"><?= $this->params['headerContent'] ?></div>
			</div>
			<div class="<?= $containerClass ?>">
				<?= $content ?>
			</div>
		<?php } else { ?>
			<div class="<?= $containerClass ?>">
				<?= Breadcrumbs::widget([
					'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
				]) ?>
				<?= Alert::widget() ?>
				<?= $content ?>
			</div>
		<?php }
	} else { ?>
		<div class="<?= $containerClass ?>">
			<?= $this->render('/site/access-denied') ?>
		</div>
	<?php } ?>

</div>

<footer class="footer">
	<div class="container container-large">
		<span class="float-start">&copy; Инвентаризация <?= date('Y') ?></span>

		<span class="float-end"><?= Yii::powered() ?></span>
	</div>
</footer>

<?php
$js = <<<TXT
function(event, data, status, xhr, selector) {
    console.log('Got modal commit ('+status+')');
    //console.log(selector);
	if (status!=='success') {
	    if (data) {
			$(this)
				.find('div.modal-body')
				.html(data);
			let h1=$(this).find('h1');
			if (h1.length) {
				let title=h1[0].innerHTML;
				$('h5.modal-title#modal_form_loader-label').html(title);
				h1.slice(0).remove();
			}
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

		if (selector.attr('data-modal-pjax-reload')) {
		    let \$pjaxContainer=$(selector).closest('div[data-pjax-container]');
		    //console.log(\$pjaxContainer);
		    if (\$pjaxContainer.length) {
		        let pjaxId='#'+\$pjaxContainer.attr('id');
			    console.log('reloading pjax '+pjaxId);
		        $.pjax.reload({container : pjaxId, timeout : 1000 });
		    } else {
		        console.log('failed found pjax container. reloading page :(')
				window.location.reload();
		    }
		}

		$(this).modal('toggle');
	}
}
TXT;

echo ModalAjax::widget([
	'id' => 'modal_form_loader',
	'bootstrapVersion' => ModalAjax::BOOTSTRAP_VERSION_5,
	'header' => 'Правка',

	'selector' => 'a.open-in-modal-form',
	'ajaxSubmit' => true, // Submit the contained form as ajax, true by default
	'size' => ModalAjax::SIZE_EXTRA_LARGE,
	'options' => ['class' => 'header-secondary text-black text-left',],
	'clientOptions'=>['backdrop'=> 'static',],
	'autoClose' => true,
	'events'=>[
		ModalAjax::EVENT_MODAL_SHOW => new JsExpression("
			function(event, data, status, xhr, selector) {
				selector.addClass('modal-open');
				let h1=$(this).find('h1');
				if (h1.length) {
					let title=h1[0].innerHTML;
					$('h5.modal-title#modal_form_loader-label').html(title);
					h1.slice(0).remove();
				}
			}
		"),
		ModalAjax::EVENT_MODAL_SHOW_COMPLETE => new JsExpression("
            function(event, xhr, textStatus) {
                if (xhr.status == 403) {
                	$('div#modal_form_loader').addClass('border-danger');
                	$('div#modal_form_loader div.modal-header').addClass('card-header bg-danger');
                	$('h5.modal-title#modal_form_loader-label').html('Error');
                	$('div#modal_form_loader div.modal-body').html('Доступ к этой операции отсутствует');
                }
            }
		"),
		ModalAjax::EVENT_BEFORE_SUBMIT => new JsExpression("
			function(event, data, status, xhr, selector) {
				let \$disable=$(this).find('div.disable-on-submit');
				if (\$disable.length) {
					\$disable.find('*').attr('disabled',1);
				}
				let \$spinnerButtons=$(this).find('button.spinner-on-submit');
				if (\$spinnerButtons.length) {
					\$spinnerButtons.append(' <span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span>');
				}
			}
		"),
		ModalAjax::EVENT_MODAL_SUBMIT => new JsExpression($js),
		//ModalAjax::EVENT_MODAL_SUBMIT_COMPLETE => new \yii\web\JsExpression($js3),
	],
]); ?>

<?php $this->endBody();

$js = <<<JS
//$('.modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2

//более универсальный фикс
//https://github.com/kartik-v/yii2-widget-select2/issues/341
bootstrap.Modal.prototype._initializeFocusTrap = function () { return { activate: function () { }, deactivate: function () { } } };
JS;
$this->registerJs($js, View::POS_END);


?>
</body>
</html>
<?php $this->endPage() ?>
