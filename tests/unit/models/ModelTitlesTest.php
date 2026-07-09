<?php

namespace tests\unit\models;

use app\controllers\ArmsBaseController;
use app\models\base\ArmsModel;
use Codeception\Test\Unit;
use ReflectionClass;

/**
 * Сторож множественного имени модели ($titles).
 *
 * У каждой модели, для которой есть страничный контроллер (наследник
 * ArmsBaseController с заданным $modelClass), должно быть ОСМЫСЛЕННО объявлено
 * множественное имя `public static $titles`. Оно идёт в заголовок списка,
 * в хлебные крошки (авто-вывод в views/layouts/main.php), в тултип справки и т.п.
 *
 * Единственное имя $title появилось как стандарт раньше, поэтому у части моделей
 * $titles не переопределён и молча падает в дефолт базового ArmsModel («Объекты»).
 * Тест требует объявить $titles везде, где у модели есть страницы, чтобы
 * авто-вывод крошек/заголовков показывал корректный plural, а не «Объекты».
 */
class ModelTitlesTest extends Unit
{
	public function testPageModelsDeclarePluralTitles(): void
	{
		$missing = [];
		foreach ($this->pageModelClasses() as $modelClass => $ctrlClass) {
			$titles = $modelClass::$titles;
			if (!is_string($titles) || $titles === '' || $titles === ArmsModel::$titles) {
				$missing[] = sprintf('%s (контроллер %s): $titles = %s',
					$modelClass, $ctrlClass, var_export($titles, true));
			}
		}
		$this->assertSame([], $missing,
			"У этих моделей есть страницы, но не объявлено осмысленное \$titles (plural) —\n"
			."оно падает в дефолт ArmsModel «Объекты». Добавь в модель\n"
			."`public static \$titles='…';` (см. любую соседнюю модель):\n  "
			.implode("\n  ", $missing));
	}

	/**
	 * [modelClass => controllerClass] для всех контроллеров-наследников
	 * ArmsBaseController с заданным $modelClass (т.е. рендерящих страницы моделей).
	 * @return array<string,string>
	 */
	protected function pageModelClasses(): array
	{
		$root = str_replace('\\', '/', rtrim(codecept_root_dir(), '/\\'));
		$files = array_merge(
			glob($root.'/controllers/*Controller.php') ?: [],
			glob($root.'/modules/*/controllers/*Controller.php') ?: []
		);
		$result = [];
		foreach ($files as $file) {
			$rel = substr(str_replace('\\', '/', $file), strlen($root) + 1); // controllers/XController.php
			$fqcn = 'app\\'.str_replace('/', '\\', substr($rel, 0, -4));
			if (!class_exists($fqcn)) continue;
			$ref = new ReflectionClass($fqcn);
			if ($ref->isAbstract() || !$ref->isSubclassOf(ArmsBaseController::class)) continue;
			$modelClass = $ref->getDefaultProperties()['modelClass'] ?? null;
			if (is_string($modelClass) && class_exists($modelClass)
				&& is_subclass_of($modelClass, ArmsModel::class)) {
				$result[$modelClass] = $fqcn;
			}
		}
		return $result;
	}
}
