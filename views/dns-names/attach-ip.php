<?php

use app\components\Forms\ArmsForm;
use app\helpers\ArrayHelper;
use app\models\Domains;
use kartik\typeahead\Typeahead;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\DnsNames */
/* @var $ip app\models\NetIps */

//одно поле — два исхода (DnsNamesController::actionAttachIp): выбранное из подсказок
//или введённое вручную имя, которое уже заведено, получает этот адрес; новое — создаётся
$this->title = 'DNS-имя для ' . $ip->text_addr;

$zones = ArrayHelper::map(Domains::getAllItems(true), 'id', 'fqdn');
asort($zones);

$formId = 'dns-names-attach-ip-form';
$statusId = 'dns-names-attach-ip-status';
$searchUrl = Url::to(['/dns-names/search-list']);
?>
<div class="dns-names-attach-ip">
	<h1><?= Html::encode($this->title) ?></h1>

	<?php $form = ArmsForm::begin([
		'model' => $model,
		'options' => ['id' => $formId],
		//AJAX-валидация штатной формы споткнулась бы о unique(domain_id, host),
		//а совпадение с существующим именем тут — не ошибка, а «привязать»
		'validationUrl' => false,
	]); ?>

	<div class="row">
		<div class="col-md-8">
			<?= $form->field($model, 'host')->widget(Typeahead::class, [
				'options' => [
					'placeholder' => 'Выберите имя или введите новое: www.example.com',
					'autocomplete' => 'off',
				],
				'pluginOptions' => ['highlight' => true, 'minLength' => 0],
				'pluginEvents' => [
					'typeahead:select' => 'function(){dnsAttachStatus();}',
				],
				'dataset' => [[
					'display' => 'value',
					'limit' => 30,
					'remote' => [
						'url' => $searchUrl . '?name=%QUERY',
						'wildcard' => '%QUERY',
					],
				]],
			])->label('DNS-имя')->hint('Полное имя. Если такое имя уже заведено — адрес будет к нему '
				. 'привязан, иначе будет создано новое имя с этим адресом') ?>
		</div>
		<div class="col-md-4">
			<?= $form->field($model, 'domain_id')->select2(['data' => $zones])
				->hint('Определяется по суффиксу имени; выбирать — только если зона не распозналась') ?>
		</div>
	</div>

	<div id="<?= $statusId ?>" class="mb-3 text-muted"></div>

	<?= $form->field($model, 'comment')->hint('Только для нового имени') ?>

	<div class="form-group">
		<?= Html::submitButton('Привязать', ['class' => 'btn btn-success']) ?>
	</div>

	<?php ArmsForm::end(); ?>
</div>
<?php
//зона на лету — зеркало Domains::splitFqdn (самый длинный совпавший суффикс);
//подсказка «что произойдёт»: точное совпадение с заведённым именем — привязка
$zonesJson = json_encode(array_map(fn($z) => mb_strtolower(trim((string)$z, '.')), $zones), JSON_UNESCAPED_UNICODE);
$this->registerJs(<<<JS
window.dnsAttachZones=$zonesJson;
window.dnsAttachSplit=function(name){
	var best=null,bestLen=-1;
	jQuery.each(dnsAttachZones,function(id,zone){
		if (!zone) return;
		if ((name===zone || name.endsWith('.'+zone)) && zone.length>bestLen) {best=id;bestLen=zone.length;}
	});
	return best;
};
window.dnsAttachStatus=function(){
	var name=(jQuery('#dnsnames-host').val()||'').trim().toLowerCase().replace(/^\.+|\.+$/g,'');
	var status=jQuery('#$statusId');
	var zoneSelect=jQuery('#dnsnames-domain_id');
	if (!name) {status.html('');return;}
	var zoneId=dnsAttachSplit(name);
	if (zoneId!==null) {
		//суффикс распознан — зона подставляется сама
		if (String(zoneSelect.val())!==String(zoneId)) zoneSelect.val(zoneId).trigger('change.select2');
	} else if (zoneSelect.val() && dnsAttachZones[zoneSelect.val()]) {
		//имя в зоне + зона выбрана вручную: полное имя = имя.зона (как сохранит сервер)
		name=name+'.'+dnsAttachZones[zoneSelect.val()];
	} else {
		status.html('<i class="fas fa-exclamation-triangle"></i> Зона не распознана — выберите её');
		return;
	}
	jQuery.getJSON('$searchUrl',{name:name},function(list){
		var found=list.some(function(item){return item.value===name;});
		status.html(found?
			'<i class="fas fa-link"></i> Имя уже заведено — адрес будет к нему привязан':
			'<i class="fas fa-plus-circle"></i> Будет создано новое имя с этим адресом'
		);
		jQuery('#$formId button[type=submit]').text(found?'Привязать':'Создать и привязать');
	});
};
jQuery('#dnsnames-host').on('input change',function(){
	clearTimeout(window.dnsAttachTimer);
	window.dnsAttachTimer=setTimeout(dnsAttachStatus,300);
});
//ручной выбор зоны меняет полное имя — пересчитываем статус
jQuery('#dnsnames-domain_id').on('select2:select select2:clear',dnsAttachStatus);
JS);
