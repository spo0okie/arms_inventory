<?php
/**
 * Список пробросов (форвард-записей доступа) — общий для карточек ОС, оборудования,
 * IP-адреса и DNS-имени, чтобы проброс выглядел везде одинаково
 * (docs/dev/access-chains.md, §3).
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
		$ignoreHints = [];
		foreach ($ip->dnsNames as $dnsName) {
			$names[] = $this->render('/dns-names/item', ['model' => $dnsName, 'static_view' => true]);
			$ignoreHints[]=$dnsName->fqdn;
			$ignoreHints[]=$dnsName->name;
		}
		$entries[] = ModelWidget::widget([
			'model' => $ip,
			'rendered_comment' => $ignoreHints,
			'options' => ['static_view' => true]]
		)
			. (count($names) ? ' <span class="small">(' . implode(', ', $names) . ')</span>' : '');
	}
	//субъект задан не адресом (сеть, ОС, сервис, текст) — показываем как есть
	if (!count($entries)) foreach ($ace->subjects as $subject) {
		$entries[] = is_object($subject)
			? ModelWidget::widget(['model' => $subject, 'options' => ['static_view' => true]])
			: Html::encode($subject);
	}

	$resource = $ace->acl->resource ?? null;
	//назначение задано адресом — показываем и узел, которому он принадлежит
	if ($resource instanceof NetIps) {
		$nodes = [];
		$ignoreHints = [];
		foreach ($resource->comps as $node) {
			$nodes[] = ModelWidget::widget(['model' => $node, 'options' => ['static_view' => true]]);
			$ignoreHints[] = $node->fqdn;
			$ignoreHints[] = $node->name;
		}
		foreach ($resource->techs as $node) {
			$nodes[] = ModelWidget::widget(['model' => $node, 'options' => ['static_view' => true]]);
			if ($node->fqdn) $ignoreHints[] = $node->fqdn;
			if ($node->name) $ignoreHints[] = $node->name;
			if ($node->num) $ignoreHints[] = $node->num;
		}
		$target = ModelWidget::widget([
			'model' => $resource,
			'options' => ['static_view' => true],
			'rendered_comment' => $ignoreHints,
		])
			.' <span class="small">(' . implode(', ', $nodes) . ')</span>';
	} else	$target = is_object($resource)
		? ModelWidget::widget(['model' => $resource, 'options' => ['static_view' => true]])
		: Html::encode((string)$resource);

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
