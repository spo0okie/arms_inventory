<?php

namespace tests\unit\help;

use app\helpers\DocsHelper;
use app\models\base\ArmsModel;
use app\types\AttributeTypeInterface;
use Codeception\Test\Unit;
use Yii;
use yii\helpers\Inflector;

/**
 * Сторож привязки слоя 2 к коду (plans/help-docs.md, этап 4).
 *
 * MD-страницы привязываются к коду только именем файла
 * (models/<class-id>.md, models/<class-id>/<attr>.md, types/<type>.md),
 * поэтому переименование/удаление модели, атрибута или типа оставляет
 * «осиротевшие» файлы. Тест ловит их списком.
 */
class HelpOrphanTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Имена всех типов атрибутов (types/*.php -> name()).
	 * @return string[]
	 */
	protected function typeNames(): array
	{
		$names = [];
		foreach (glob(Yii::getAlias('@app/types/*.php')) as $file) {
			$class = 'app\\types\\' . pathinfo($file, PATHINFO_FILENAME);
			if (!class_exists($class)) continue;
			$reflection = new \ReflectionClass($class);
			if ($reflection->isAbstract() || !$reflection->implementsInterface(AttributeTypeInterface::class)) continue;
			$names[] = $class::name();
		}
		return $names;
	}

	public function testNoOrphanPages()
	{
		\Helper\Yii2::initFromFileName('test-console.php');

		//только каталог репозитория (override заказчика в CI не участвует)
		$roots = [Yii::getAlias('@app/docs/help')];
		$typeNames = $this->typeNames();
		$orphans = [];

		foreach (array_keys(DocsHelper::pagesMap($roots)) as $path) {
			$tokens = explode('/', $path);

			if ($tokens[0] === 'models') {
				//models/<class-id>.md или models/<class-id>/<attr>.md
				$classId = pathinfo($tokens[1], PATHINFO_FILENAME);
				$class = 'app\\models\\' . Inflector::id2camel($classId, '-');
				if (!class_exists($class) || !is_subclass_of($class, ArmsModel::class)) {
					$orphans[] = "$path: нет модели $class";
					continue;
				}
				if (count($tokens) === 3) {
					$attr = pathinfo($tokens[2], PATHINFO_FILENAME);
					$model = new $class();
					if (
						!array_key_exists($attr, $model->attributeData())
						&& !$model->hasAttribute($attr)
						&& !$model->canGetProperty($attr)
					) {
						$orphans[] = "$path: у модели $class нет атрибута $attr";
					}
				}
				continue;
			}

			if ($tokens[0] === 'types' && count($tokens) === 2) {
				$typeName = pathinfo($tokens[1], PATHINFO_FILENAME);
				if (!in_array($typeName, $typeNames, true)) {
					$orphans[] = "$path: нет типа с name()='$typeName'";
				}
			}

			//README, guides/, admin/ и прочие свободные страницы к коду не привязаны
		}

		$this->assertEmpty(
			$orphans,
			"Осиротевшие страницы документации (модель/атрибут/тип переименованы или удалены — "
			."переименуйте/удалите файлы):\n".implode("\n", $orphans)
		);
	}
}
