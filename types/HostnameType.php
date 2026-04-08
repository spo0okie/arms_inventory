<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use app\models\Domains;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения hostname (FQDN или NetBIOS имя).
 *
 * Формат: hostname или hostname.domain.example
 * Пример: "server01", "dc01.domain.local", "pc-001"
 */
class HostnameType implements AttributeTypeInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'hostname';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? 'server01';
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if ($value === null || $value === '') {
			return '<span class="text-muted">—</span>';
		}
		return Html::encode((string)$value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function apiSchema(): array
	{
		return [
			'type' => 'string',
			'format' => 'hostname',
			'example' => 'server01.domain.local',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function gridColumnClass(): ?string
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function samples(): array
	{
		return [
			'server01',
			'dc01.domain.local',
			'pc-001',
			'web-srv-01',
			'mail.example.com',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		// Префиксы для имён хостов
		$prefixes = [
			'server', 'srv', 'host', 'pc', 'ws',
			'dc', 'dc01', 'filesrv', 'mail', 'web',
			'db', 'db01', 'app', 'app01', 'vpn',
		];

		// Домены
		$domains = [
			'domain.local',
			'company.local',
			'corp.example.com',
			'office.local',
			'data.local',
		];

		$prefix = AttributeContext::pickRandomValue($prefixes, $rng);
		$domain = AttributeContext::pickRandomValue($domains, $rng);

		// Номер (1-99)
		$number = $rng->getInt(1, 999999);

		// NetBIOS имя (без домена) или FQDN (с доменом)
		if ($rng->getInt(0, 1) === 0) {
			return $prefix . sprintf('%02d', $number);
		} else {
			return $prefix . sprintf('%02d', $number) . '.' . $domain;
		}
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
			new RuleDefinition('filter', ['filter' => function($value) use ($context) {
				return Domains::validateHostname($value,$context->model);
			}]),
		];
	}
}
