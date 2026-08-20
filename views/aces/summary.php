<?php
/**
 * Однострочная сводка записи доступа (ACE) для карточек связанных объектов.
 *
 * Перспектива задается параметром $show:
 *  - 'resource': [расписание: ]ресурс — типы доступа: пояснение
 *    («Имеет доступ к» на карточках субъектов: пользователей, ОС, IP)
 *  - 'subjects': субъекты — типы доступа: пояснение
 *    («Предоставлен доступ» на карточках ресурсов; расписание не выводится —
 *    оно уже в заголовке карточки ACL, см. acls/list-item.php)
 *
 * Пояснение — ссылка на саму запись доступа: в нем живет конкретика
 * («зачем доступ», номер ваучера гостевого WiFi и т.п.), поэтому оно
 * должно быть видно с обеих сторон, а не только в списке ACE.
 */

use app\components\ItemObjectWidget;
use app\components\ModelFieldWidget;
use app\components\widgets\page\ModelWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $show string перспектива: 'resource'|'subjects' */
/* @var $show_schedule bool выводить ли расписание префиксом строки (false, когда
 *      строки уже сгруппированы под заголовком-расписанием, см. aces/list.php) */
/* @var $static_view bool */
/* @var $show_archived bool|null */

if (!isset($show)) $show='resource';
if (!isset($show_schedule)) $show_schedule=true;
if (!isset($static_view)) $static_view=false;
if (!isset($show_archived)) $show_archived=null;

$parts=[];

if ($show==='resource') {
	if (is_object($acl=$model->acl)) {
		$resource='';
		if ($show_schedule && is_object($acl->schedule))
			$resource.=ModelWidget::widget(['model'=>$acl->schedule,'options'=>['static_view'=>true]]).': ';
		$resource.=ModelWidget::widget(['model'=>$acl,'options'=>['static_view'=>$static_view]]);
		$parts[]=$resource;
	}
} else {
	//гетерогенный список субъектов (включая текстовое «Прочее») отдает атрибут subjects
	$parts[]=ModelFieldWidget::renderFieldValue($model,'subjects',[
		'item_options'=>[
			'static_view'=>true,
			'icon'=>true,
			'short'=>true,
			'show_ips'=>$model->hasIpAccess(),
			'show_phone'=>$model->hasPhoneAccess(),
		],
		'lineBr'=>false,
		'glue'=>', ',
	]);
}

//типы доступа с IP-параметрами записи (подача как в списке ACE, см. aces/columns.php)
$types=[];
foreach ($model->accessTypes as $type) {
	$params=$model->getIpParams()[$type->id]??null;
	$types[]=$type->renderItem($this,['static_view'=>true,'suffix'=>$params?': '.$params:'']);
}
$access=implode(', ',$types);

//пояснение: ссылка на запись доступа, выводится только когда заполнено
if (strlen($model->name??''))
	$access=(strlen($access)?$access.': ':'')
		.$this->render('/aces/item',['model'=>$model,'static_view'=>$static_view,'modal'=>true]);

if (strlen($access)) $parts[]=$access;

//архивность строки (уволенные субъекты, мертвый ACL) виджет возьмет из модели
echo ItemObjectWidget::widget([
	'model'=>$model,
	'link'=>implode(' - ',array_filter($parts,'strlen')),
	'show_archived'=>$show_archived,
]);
