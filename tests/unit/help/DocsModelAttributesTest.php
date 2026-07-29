<?php

namespace tests\unit\help;

use app\helpers\DocsHelper;
use app\helpers\ModelHelper;
use app\models\base\ArmsModel;
use Codeception\Test\Unit;

/**
 * Сторож автотаблицы «Атрибуты» страниц /docs/models/<class>.
 *
 * attributeData() наследует дефолтные записи ArmsModel (code, notepad, links,
 * archived...), даже когда у таблицы конкретной модели таких колонок нет
 * (пример: absences, notifications). DocsHelper::modelDocAttributes() обязан
 * их отфильтровать, не потеряв при этом ни реальные колонки, ни объявленные
 * моделью вычисляемые поля/связи.
 */
class DocsModelAttributesTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Инварианты на всех моделях:
	 * 1. унаследованный (не переопределённый) дефолт ArmsModel без реальной
	 *    колонки не попадает в справочник;
	 * 2. фильтр не переусердствует: реальные колонки таблицы и собственные
	 *    (не алиасные) записи attributeData модели остаются в справочнике.
	 */
	public function testInheritedDefaultsFiltered()
	{
		\Helper\Yii2::initFromFileName('test-console.php');

		$problems = [];
		foreach (ModelHelper::getModelClasses() as $class) {
			if (preg_match('/(History|Search)$/', $class)) continue;
			try {
				/** @var ArmsModel $model */
				$model = new $class();
				$base = (new \ReflectionMethod(ArmsModel::class, 'attributeData'))->invoke($model);
				$docAttrs = DocsHelper::modelDocAttributes($model);
			} catch (\Throwable $e) {
				continue;
			}

			foreach ($docAttrs as $attr => $data) {
				if (isset($base[$attr]) && $base[$attr] === $data && !$model->hasAttribute($attr)) {
					$problems[] = "$class::$attr — унаследованный дефолт ArmsModel без колонки в таблице попал в справочник";
				}
			}

			foreach ($model->attributeData() as $attr => $data) {
				if (is_array($data) && isset($data['alias'])) continue;
				$declaredByModel = !isset($base[$attr]) || $base[$attr] !== $data;
				if (($declaredByModel || $model->hasAttribute($attr)) && !isset($docAttrs[$attr])) {
					$problems[] = "$class::$attr — собственный атрибут модели пропал из справочника";
				}
			}
		}

		$this->assertEmpty(
			$problems,
			"Проблемы автотаблицы атрибутов /docs/models:\n" . implode("\n", $problems)
		);
	}

	/**
	 * Конкретный случай из отчёта: у absences нет колонок code/notepad —
	 * дефолты ArmsModel не должны показываться, а собственные атрибуты должны.
	 */
	public function testAbsencesCase()
	{
		\Helper\Yii2::initFromFileName('test-console.php');

		$attrs = DocsHelper::modelDocAttributes(new \app\models\Absences());

		$this->assertArrayNotHasKey('code', $attrs, 'у absences нет колонки code');
		$this->assertArrayNotHasKey('notepad', $attrs, 'у absences нет колонки notepad');
		$this->assertArrayHasKey('id', $attrs);
		$this->assertArrayHasKey('user_id', $attrs);
		$this->assertArrayHasKey('type', $attrs);
		$this->assertArrayHasKey('comment', $attrs, 'переопределённый моделью comment должен остаться');
	}
}
