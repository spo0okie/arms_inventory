<?php
/**
 * Список пробросов (форвард-записей доступа) — общий для карточек ОС, оборудования,
 * IP-адреса и DNS-имени, чтобы проброс выглядел везде одинаково
 * (plans/access-chains.md, итерация 2).
 *
 * Строка: «адреса входа (DNS-имена) параметры входа → назначение :порт».
 * Стрелка — ссылка на саму запись доступа (правка/подробности там).
 * Модели должны быть загружены с Aces::FORWARDS_WITH (см. Aces::findForwardsTo/From).
 *
 * @var yii\web\View $this
 * @var app\models\Aces[] $models    форвард-записи
 * @var app\models\base\ArmsModel $owner   чья карточка: заголовок берётся из её атрибута
 * @var string $attribute   атрибут владельца (forwardsIn/forwardsOut) — подпись и подсказка на нём
 * @var bool $static_view
 * @var string $tag тег заголовка
 */

use app\components\ModelFieldWidget;
use app\components\widgets\page\ModelWidget;
use app\models\NetIps;
use yii\helpers\Html;

if (!isset($static_view)) $static_view = false;
if (!isset($tag)) $tag = 'h4';
if (empty($models)) return;

$lines = [];
foreach ($models as $ace) {
	//адреса входа с DNS-именами, которые на них указывают
	$entries = [];
	foreach ($ace->netIps as $ip) {
		$names = [];
		foreach ($ip->dnsNames as $dnsName) $names[] = $this->render('/dns-names/item', ['model' => $dnsName, 'static_view' => true]);
		$entries[] = ModelWidget::widget(['model' => $ip, 'options' => ['static_view' => true]])
			. (count($names) ? ' <span class="small">(' . implode(', ', $names) . ')</span>' : '');
	}
	//субъект задан не адресом (сеть, ОС, сервис, текст) — показываем как есть
	if (!count($entries)) foreach ($ace->subjects as $subject) {
		$entries[] = is_object($subject)
			? ModelWidget::widget(['model' => $subject, 'options' => ['static_view' => true]])
			: Html::encode($subject);
	}

	$resource = $ace->acl->resource ?? null;
	$target = is_object($resource)
		? ModelWidget::widget(['model' => $resource, 'options' => ['static_view' => true]])
		: Html::encode((string)$resource);
	//назначение задано адресом — показываем и узел, которому он принадлежит
	if ($resource instanceof NetIps) {
		$nodes = [];
		foreach ($resource->comps as $node) $nodes[] = ModelWidget::widget(['model' => $node, 'options' => ['static_view' => true]]);
		foreach ($resource->techs as $node) $nodes[] = ModelWidget::widget(['model' => $node, 'options' => ['static_view' => true]]);
		if (count($nodes)) $target .= ' <span class="small">(' . implode(', ', $nodes) . ')</span>';
	}

	//стрелка — ссылка на запись доступа
	$arrow = $this->render('/aces/item', [
		'model' => $ace, 'static_view' => true,
		'name' => '<span class="fas fa-long-arrow-alt-right"></span>',
	]);

	foreach ($ace->forwardRules as $rule) {
		$lines[] = implode(', ', $entries)
			. ($rule['ext'] !== '' ? ' <span class="text-monospace small">' . Html::encode($rule['ext']) . '</span>' : '')
			. ' ' . $arrow . ' ' . $target
			. ($rule['int'] !== '' ? '<span class="text-monospace small">:' . Html::encode($rule['int']) . '</span>' : '');
	}
}

if (!count($lines)) return;

echo ModelFieldWidget::renderFieldTitle($owner, $attribute, null, $tag);
echo Html::tag('p', implode('<br />', $lines), ['class' => 'forwards-list']);
