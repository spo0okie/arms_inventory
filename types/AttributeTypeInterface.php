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
	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed;

	/**
	 * Типовой рендер значения атрибута в местах вывода (карточки view/item,
	 * свободная вёрстка). Контракт результата:
	 * - string — готовый HTML значения (рендер сам управляет переносами);
	 * - array — список готовых HTML-элементов, разделители расставляет
	 *   потребитель.
	 * Тип рендерит только значение; подача (карточка/заголовок/список) —
	 * забота потребителя. Пустое значение тоже подача: потребитель сам
	 * решает show_empty/message_on_empty и рендер типа НЕ вызывает.
	 * Ссылочные атрибуты значением не рендерятся: потребитель уводит их
	 * на объектный путь (renderItem) по метаданным, LinkType на попытку
	 * рендера бросает исключение.
	 * Дефолтная реализация (экранированное значение) — в BaseType.
	 * @param View $view куда рендерить
	 * @param ArmsModel $model какая модель
	 * @param string $attribute какой атрибут
	 * @param array $options параметры рендера (напр. outerClass)
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed;

	/**
	 * Схема для API (OpenAPI/Swagger).
	 * возвращает массив ['type' => 'string', 'format' => 'date', 'example'=>'2023-12-12']
	 */
	public function apiSchema(): array;

	/**
	 * Класс колонки для Grid, либо null, чтобы оставить выбор вызывающему коду.
	 */
	public function gridColumnClass(): ?string;

	/**
	 * Примеры значений для документации.
	 */
	public function samples(): array;

	/**
	 * Типовая часть подсказки заполнения («как вводить») — лёгкий слой
	 * документации, см. ui-sources.md §0.1. Это данные типа (один текст на
	 * все атрибуты этого типа), а не метаданные атрибута. null — нет.
	 * Дефолтная реализация — в BaseType.
	 */
	public function inputHint(): ?string;

	/**
	 * Типовая часть подсказки поиска («как искать»): только отличия от
	 * общего синтаксиса поиска. null — нет. Дефолтная реализация — в BaseType.
	 */
	public function searchHint(): ?string;

	/**
	 * Генератор для данного типа атрибута (совместим с GeneratorInterface).
	 */
	public function generate(AttributeContext $context): mixed;

	/**
     * @return RuleDefinition[]
     */
    public function rules(AttributeRuleContext $context): array;
}
