<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\GeneratorInterface;
use app\models\base\ArmsModel;
use yii\web\View;

/**
 * Attribute type interface.
 *
 * IMPORTANT:
 * - Не смешиваем данные типа и метаданные конкретного атрибута (label/hint).
 * - Метаданные атрибута должны обрабатываться на уровне модели/формы,
 *   сюда передаются только параметры типа (typeParams).
 */
interface AttributeTypeInterface extends GeneratorInterface
{
	/**
	 * Уникальное имя типа (для логов/отладки/реестра).
	 */
	public static function name(): string;

	/**
	 * Рендер ввода (input) для атрибута.
	 * @param View $view куда рендерить
	 * @param ArmsModel $model какая модель
	 * @param string $attribute какой атрибут
	 * @param array $options параметры рендера (label/hint и т.д.)
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed;

	/**
	 * Рендер атрибута на форму/grid.
	 * @param View $view куда рендерить
	 * @param ArmsModel $model какая модель
	 * @param string $attribute какой атрибут
	 * @param array $options параметры рендера (label/hint и т.д.)
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed;

	/**
	 * Схема для API (OpenAPI/Swagger).
	 * возвращает массив ['type' => 'string', 'format' => 'date', 'example'=>'2023-12-12']
	 */
	public function apiSchema(): array;

	/**
	 * Класс колонки для Grid, либо null чтобы оставить выбор вызывающему коду.
	 */
	public function gridColumnClass(): ?string;

	/**
	 * Примеры значений для документации.
	 */
	public function samples(): array;

	/**
	 * Генератор для данного типа атрибута (совместим с GeneratorInterface).
	 */
	public function generate(AttributeContext $context): mixed;
}
