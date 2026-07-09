<?php
/**
 * Рендер одного IP-адреса ОС на карточке comps (в отличие от типового
 * net-ips/item.php, который общий для оборудования/пользователей и НЕ несёт
 * per-IP управления). Здесь у адреса ОС появляются: значок удалённого
 * управления remotecontrol://<ip> и «глаз» скрытия/возврата технического
 * адреса (пишет в Comps.ip_ignore через comps/ignoreip|unignoreip).
 *
 * Скрытые адреса помечаются классом archived-item и по умолчанию свёрнуты —
 * их раскрывает тот же переключатель «Архивные» (ShowArchivedWidget), что и
 * прочие скрытые элементы карточки.
 *
 * @var $this        yii\web\View
 * @var $owner       app\models\Comps      ОС, которой принадлежит адрес
 * @var $model       app\models\NetIps     объект адреса (для активного)
 * @var $address     string                текст адреса (для игнорируемого — объекта нет)
 * @var $ignored     bool                  адрес в списке игнорируемых
 * @var $static_view bool                  режим «только чтение» (тултипы/печать) — без действий
 */

use app\components\ShowArchivedWidget;
use yii\helpers\Html;

if (!isset($ignored)) $ignored = false;
if (!isset($static_view)) $static_view = false;

//адрес: у активного берём из объекта NetIps, у игнорируемого — из строки
$addr = $ignored ? ($address ?? '') : $model->text_addr;
if (trim($addr) === '') return;

//действия (значки) — только в интерактивном режиме
$actions = '';
if (!$static_view) {
	if ($ignored) {
		//вернуть адрес из игнорируемых
		$actions = Html::a(
			'<i class="fas fa-eye"></i>',
			['/comps/unignoreip', 'id' => $owner->id, 'ip' => $addr],
			[
				'class' => 'ip-action',
				'qtip_ttip' => 'Вернуть адрес: снова учитывать его у этой ОС',
				'qtip_side' => 'bottom',
			]
		);
	} else {
		//per-IP удалённое управление + скрыть адрес
		$remote = Html::a(
			'<i class="fas fa-sign-in-alt"></i>',
			'remotecontrol://' . $addr,
			[
				'class' => 'ip-action',
				'qtip_ttip' => "Удалённое управление по адресу {$addr}",
				'qtip_side' => 'bottom',
			]
		);
		$hide = Html::a(
			'<i class="fas fa-eye-slash"></i>',
			['/comps/ignoreip', 'id' => $owner->id, 'ip' => $addr],
			[
				'class' => 'ip-action',
				'qtip_ttip' => 'Скрыть технический адрес (перенести в игнорируемые)',
				'qtip_side' => 'bottom',
			]
		);
		$actions = $remote . $hide;
	}
}

if ($ignored) {
	//зачёркнутый приглушённый адрес; по умолчанию свёрнут (раскрывает «Архивные»)
	$name = Html::tag('span', Html::encode($addr), ['class' => 'text-decoration-line-through text-muted']);
	echo Html::tag('span', $name . ' ' . $actions, [
		'class' => 'comp-ip-item archived-item',
		'style' => ShowArchivedWidget::archivedDisplay(true),
	]);
} else {
	//активный адрес: типовой рендер ссылки (как у net-ips/item.php) + действия
	$name = $this->render('/net-ips/item', ['model' => $model, 'static_view' => $static_view]);
	echo Html::tag('span', $name . ' ' . $actions, ['class' => 'comp-ip-item']);
}
