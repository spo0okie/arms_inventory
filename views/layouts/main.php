<?php

/* @var $this View */
/* @var $content string */

use spo0okie\yii2ModalAjax\ModalAjax;
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

//Справка по странице — единый тогглер (иконка «?») в правом крае breadcrumbs,
//одинаково на списках и карточках. Иконка и панель развязаны (клик ловится
//делегированно по data-panel-target, см. docsPanel/panel.php), поэтому панель
//рендерится там, где ей место: на карточке — блоком под breadcrumbs (ниже),
//на списке — над таблицей (DynaGridWidget). Класс берём у контроллера.
$docsPanelBlock='';
$pageHelpIcon='';
$ctrl=Yii::$app->controller;
if ($ctrl && isset($ctrl->modelClass) && is_subclass_of($ctrl->modelClass,\app\models\base\ArmsModel::class)) {
	$pageHelpIcon=\app\components\HintIconWidget::widget([
		'model'=>$ctrl->modelClass,
		'action'=>$ctrl->action->id ?? 'index',
		'hintText'=>'Справка по этой странице',
	]);

	//инфопанель «Просмотр» на карточке (plans/help-inline.md): только тело,
	//тогглер — иконка в breadcrumbs выше; пустая секция не рендерит ничего
	if (($ctrl->action->id??null)==='view') {
		$docsPanel=\app\components\DocsPanelWidget::widget([
			'model'=>$ctrl->modelClass,
			'sections'=>['Просмотр'],
		]);
		if ($docsPanel!=='') {
			$docsPanelBlock=Html::tag('div',$docsPanel,['class'=>'px-5 mb-2']);
		}
	}
}

//Авто-крошки: если вьюха не задала $this->params['breadcrumbs'] явно, собираем
//стандартный трейл из модели контроллера + действия + заголовка страницы
//($this->title вьюха ставит ДО рендера layout). Так крошки есть на ВСЕХ
//модельных страницах без дублирования кода по вьюхам; более полный/кастомный
//трейл по-прежнему задаётся явно во вьюхе и перекрывает авто-вывод (напр.
//update-страница добавляет средним звеном ссылку на карточку объекта).
if (empty($this->params['breadcrumbs'])
	&& $ctrl && isset($ctrl->modelClass)
	&& is_subclass_of($ctrl->modelClass,\app\models\base\ArmsModel::class)
) {
	$mc=$ctrl->modelClass;
	//$titles всегда объявлен (базовый ArmsModel) и осмыслен — этого требует
	//сторож tests/unit/models/ModelTitlesTest у всех модельных контроллеров
	$indexTitle=$mc::$titles;
	$autoCrumbs=[];
	//на не-index действиях первым пунктом — ссылка на список
	if (($ctrl->action->id??null)!=='index')
		$autoCrumbs[]=['label'=>$indexTitle,'url'=>['index']];
	//текущая страница — её заголовок (для index это уже плюрал), иначе плюрал
	$autoCrumbs[]=($this->title!==null && $this->title!=='')?$this->title:$indexTitle;
	$this->params['breadcrumbs']=$autoCrumbs;
}

//breadcrumbs с иконкой справки в правом крае (иконка — только если у страницы
//есть docs-модель); используется во всех трёх ветках шапки ниже.
//Иконку кладём ВНУТРЬ ленты (<li>, прижатый вправо через margin-left:auto,
//см. page-header.css), чтобы полоса осталась во всю ширину и симметричной —
//обёртка-флекс ужимала <ol> и ломала бордюрную полосу nav-header.
$crumbsBar=Breadcrumbs::widget([
	'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
]);
if ($pageHelpIcon!=='') {
	$iconLi=Html::tag('li',$pageHelpIcon,['class'=>'docs-help-crumb']);
	$crumbsBar=str_contains($crumbsBar,'</ol>')
		//есть лента крошек — иконку добавляем последним пунктом
		? str_replace('</ol>',$iconLi.'</ol>',$crumbsBar)
		//крошек нет (Breadcrumbs вернул пусто) — строим минимальную ленту с
		//«Главная» (ровно как Yii авто-добавляет на прочих страницах) + иконка,
		//чтобы полоса была цельной, а иконка не висела голым <li> с маркером
		: Html::tag('ol',
			Html::tag('li',Html::a(Yii::t('yii','Home'),Yii::$app->homeUrl),['class'=>'breadcrumb-item'])
			.$iconLi,
			['class'=>'breadcrumb']
		);
}

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

<?php /* Единый тогглер справки страницы. Намеренно отдельным <script> на vanilla JS:
   отдельный тег исполняется независимо (ошибка в чужом скрипте его не отменит),
   делегирование на document не требует DOM-ready и jQuery.ready-очереди, а jQuery
   нужен лишь для анимации панели (к моменту клика уже загружен). Клик по любой иконке
   справки (a[data-panel-target]) переключает подсветку атрибутов (body.help-mode);
   панель показывается/прячется, только если она есть на странице (на формах её нет). */ ?>
<script>
(function(){
	function key(){return 'helpMode:'+location.pathname;}
	document.addEventListener('click',function(e){
		var a=e.target.closest&&e.target.closest('a[data-panel-target]');
		if(!a) return;
		e.preventDefault();
		var on=!document.body.classList.contains('help-mode');
		document.body.classList.toggle('help-mode',on);
		try{localStorage.setItem(key(),on?'on':'off');}catch(_){}
		var sel=a.getAttribute('data-panel-target'), p=sel&&document.querySelector(sel);
		if(p){ if(window.jQuery){window.jQuery(p).slideToggle(150);} else {p.style.display=on?'':'none';} }
	});
	document.addEventListener('DOMContentLoaded',function(){
		try{
			if(localStorage.getItem(key())!=='on') return;
			document.body.classList.add('help-mode');
			var a=document.querySelector('a[data-panel-target]'),
				sel=a&&a.getAttribute('data-panel-target'),
				p=sel&&document.querySelector(sel);
			if(p) p.style.display='';
		}catch(_){}
	});
})();
</script>

<?php /* Подсветка элемента интерфейса по клику на ссылку из встроенной документации.
   Автор дока связывает строку описания с элементом по ключу: в MD — обычной ссылкой
   [текст](#doc-anchor:КЛЮЧ) (её href переживает рендер, DocsHelper::rewriteHtmlLinks
   не трогает якоря) или raw-HTML <a data-doc-anchor="КЛЮЧ">; на самом элементе —
   атрибут data-doc-anchor="КЛЮЧ". Клик по ссылке скроллит к элементу и подсвечивает
   его на пару секунд (.doc-anchor-highlight, site.css). Если элемента на странице нет
   — ссылка ведёт себя как обычная. Отдельный vanilla-<script> по тем же причинам, что
   и тогглер справки выше (независимое исполнение, делегирование без DOM-ready). */ ?>
<script>
(function(){
	var PREFIX='#doc-anchor:';
	function keyOf(a){
		if(a.hasAttribute('data-doc-anchor')) return a.getAttribute('data-doc-anchor');
		var href=a.getAttribute('href')||'';
		return href.indexOf(PREFIX)===0 ? decodeURIComponent(href.slice(PREFIX.length)) : null;
	}
	function inDocs(el){ return !!(el.closest && el.closest('.docs-panel,.docs-page,.modal')); }
	document.addEventListener('click',function(e){
		var a=e.target.closest && e.target.closest('a[href^="'+PREFIX+'"], a[data-doc-anchor]');
		if(!a) return;
		var k=keyOf(a); if(!k) return;
		var esc=(window.CSS&&CSS.escape)?CSS.escape(k):k.replace(/["\\]/g,'\\$&');
		var list=document.querySelectorAll('[data-doc-anchor="'+esc+'"]'), target=null;
		//цель — элемент с тем же ключом, но не сама ссылка и не внутри документации
		for(var i=0;i<list.length;i++){ if(list[i]!==a && !inDocs(list[i])){ target=list[i]; break; } }
		if(!target) return;
		e.preventDefault();
		if(target.scrollIntoView) target.scrollIntoView({behavior:'smooth',block:'center'});
		target.classList.remove('doc-anchor-highlight');
		void target.offsetWidth; //рестарт CSS-анимации при повторном клике
		target.classList.add('doc-anchor-highlight');
		window.setTimeout(function(){ target.classList.remove('doc-anchor-highlight'); },2200);
	});
})();
</script>

<div class="wrap">

	<?= $this->render('menu') ?>
	<?php if (isset($this->params['navTabs'])) { ?>
		<div class="nav-header">
			<?= $crumbsBar ?>
			<?= Alert::widget() ?>
			<?= $docsPanelBlock ?>
			<?= $this->params['headerWidgets']??'' ?>
			<div class="px-5"><?= $this->params['headerContent'] ?></div>
		</div>
		<?= TabsWidget::widget(array_merge([
			'items'=>$this->params['navTabs'],
			'options'=>['class'=>'nav-header'],
			'itemOptions'=>['class'=>'px-5'],	//делаем такой же отступ как в шапке
		],$this->params['tabsParams']??[])); ?>
	<?php } elseif (isset($this->params['headerContent'])) { ?>
		<div class="nav-header">
			<?= $crumbsBar ?>
			<?= Alert::widget() ?>
			<?= $docsPanelBlock ?>
			<div class="px-5"><?= $this->params['headerContent'] ?></div>
		</div>
		<div class="<?= $containerClass ?>">
			<?= $content ?>
		</div>
	<?php } else { ?>
		<div class="<?= $containerClass ?>">
			<?= $crumbsBar ?>
			<?= Alert::widget() ?>
			<?= $docsPanelBlock ?>
			<?= $content ?>
		</div>
	<?php } ?>

</div>

<footer class="footer">
	<div class="container container-large">
		<span class="float-start">&copy; Инвентаризация <?= date('Y') ?></span>

		<span class="float-end"><?= Yii::t('yii', 'Powered by {yii}', [
				'yii' => '<a href="https://www.yiiframework.com/" rel="external">' . Yii::t('yii', 'Yii Framework') . '</a>',
			]) ?></span>
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
]);

$this->endBody();

$js = <<<JS
//$('.modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2

//более универсальный фикс
//https://github.com/kartik-v/yii2-widget-select2/issues/341
bootstrap.Modal.prototype._initializeFocusTrap = function () {
    return {
        activate: function () { },
        deactivate: function () { }
    }
};
JS;
$this->registerJs($js, View::POS_END);


?>
</body>
</html>
<?php $this->endPage() ?>
