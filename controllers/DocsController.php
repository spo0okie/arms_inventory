<?php

namespace app\controllers;

use app\helpers\DocsHelper;
use app\helpers\ModelHelper;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Встроенная документация: оглавление и рендер MD-страниц из docs/help
 * (+ каталог переопределений заказчика params['docsOverridePath']).
 * Конвенция ведения документации: docs/help/README.md, план: plans/help-docs.md.
 */
class DocsController extends ArmsBaseController
{
	/**
	 * Returns acceptance test data map.
	 */
	public function getTestData(): array {return [];}

	/**
	 * @inheritdoc
	 */
	public function accessMap()
	{
		return [
			static::PERM_VIEW => ['index', 'page', 'img', 'model'],
		];
	}

	/**
	 * @inheritdoc
	 * editable и прочие CRUD-действия базового контроллера здесь не нужны
	 */
	public function actions() {return [];}

	public function disabledActions()
	{
		return ['create','update','delete','item','item-by-name','ttip','validate','view','async-grid'];
	}

	/**
	 * Оглавление документации: свободные страницы (docs/help + override заказчика)
	 * и генерируемый справочник всех сущностей (полнота гарантируется генерацией,
	 * а не наличием MD-страниц).
	 *
	 * Требует права 'view'. GET: нет параметров.
	 *
	 * @return string HTML оглавления
	 */
	public function actionIndex()
	{
		return $this->render('index', [
			'pages' => DocsHelper::pagesList(),
			'models' => static::modelsReference(),
		]);
	}

	/**
	 * Справочник сущностей: все модели ArmsModel кроме служебных зеркал
	 * (*History повторяет мастер-модель, *Search наследует её метаданные).
	 * @return array [['classId','titles','description','hasPage'],...]
	 */
	protected static function modelsReference(): array
	{
		$reference = [];
		foreach (ModelHelper::getModelClasses() as $class) {
			if (preg_match('/(History|Search)$/', $class)) continue;
			$classId = DocsHelper::modelClassId($class);
			$reference[$classId] = [
				'classId' => $classId,
				'titles' => $class::$titles,
				'description' => $class::modelDescription(),
				'hasPage' => DocsHelper::pageExists(DocsHelper::modelPagePath($classId)),
			];
		}
		ksort($reference);
		return array_values($reference);
	}

	/**
	 * Класс модели по kebab-case идентификатору документации:
	 * общий резолвер — {@see DocsHelper::findDocClass()}.
	 */
	protected static function findDocClass(string $classId): ?string
	{
		return DocsHelper::findDocClass($classId);
	}

	/**
	 * Acceptance test data for Index.
	 *
	 * Проверяет отображение оглавления документации без параметров.
	 * GET: нет. Ожидается HTTP 200.
	 */
	public function testIndex(): array
	{
		return [[
			'name' => 'default',
			'response' => 200,
		]];
	}

	/**
	 * Отображает страницу документации (MD-файл).
	 *
	 * GET:
	 *   path (string) — относительный путь страницы внутри docs/help,
	 *   например 'README.md' или 'models/comps.md'.
	 *
	 * @param string $path
	 * @return string HTML страницы
	 * @throws NotFoundHttpException если страница не найдена (в т.ч. не-md путь)
	 */
	public function actionPage(string $path)
	{
		$file = DocsHelper::findPage($path);
		if (!$file) throw new NotFoundHttpException('Страница документации не найдена');

		return $this->defaultRender('page', [
			'title' => DocsHelper::pageTitle($file),
			'html' => DocsHelper::renderPage($file, $path),
		]);
	}

	/**
	 * Acceptance test data for Page.
	 *
	 * Сценарии: существующая страница (README.md всегда в репозитории),
	 * отсутствующая страница и попытка выйти за пределы каталога документации —
	 * оба последних должны дать 404.
	 */
	public function testPage(): array
	{
		return [
			[
				'name' => 'readme',
				'GET' => ['path' => 'README.md'],
				'response' => 200,
			],
			[
				'name' => 'missing',
				'GET' => ['path' => 'no-such-page.md'],
				'response' => 404,
			],
			[
				'name' => 'traversal',
				'GET' => ['path' => '../../config/params.php'],
				'response' => 404,
			],
		];
	}

	/**
	 * Страница документации сущности: короткое описание (modelDescription),
	 * подробное описание из docs/help/models/<class-id>.md (если есть)
	 * и справочник атрибутов с их подсказками из attributeData.
	 *
	 * GET:
	 *   class (string) — kebab-case идентификатор модели (comps, tech-models).
	 *
	 * @param string $class
	 * @return string HTML страницы
	 * @throws NotFoundHttpException если модель не найдена
	 */
	public function actionModel(string $class)
	{
		$modelClass = static::findDocClass($class);
		if (!$modelClass)
			throw new NotFoundHttpException('Нет такой сущности');

		$pagePath = DocsHelper::modelPagePath($class);
		$file = DocsHelper::findPage($pagePath);

		return $this->defaultRender('model', [
			'classId' => $class,
			'model' => new $modelClass(),
			//H1 документа отбрасываем: заголовок странице даёт вьюха (titles модели)
			'html' => $file ? DocsHelper::renderPage($file, $pagePath, true) : '',
		]);
	}

	/**
	 * Acceptance test data for Model.
	 *
	 * Сценарии: существующая модель (comps), несуществующий id и попытка
	 * подсунуть класс вне models (base — не наследник ArmsModel) — 404.
	 */
	public function testModel(): array
	{
		return [
			[
				'name' => 'comps',
				'GET' => ['class' => 'comps'],
				'response' => 200,
			],
			[
				'name' => 'missing',
				'GET' => ['class' => 'no-such-model'],
				'response' => 404,
			],
		];
	}

	/**
	 * Отдаёт картинку документации.
	 *
	 * GET:
	 *   path (string) — относительный путь внутри docs/help,
	 *   например 'img/passport.png'. Допустимы только расширения
	 *   из DocsHelper::IMG_EXTENSIONS.
	 *
	 * @param string $path
	 * @return \yii\web\Response файл картинки (inline)
	 * @throws NotFoundHttpException если файл не найден или расширение недопустимо
	 */
	public function actionImg(string $path)
	{
		$file = DocsHelper::findImage($path);
		if (!$file) throw new NotFoundHttpException('Изображение не найдено');

		return Yii::$app->response->sendFile($file, basename($file), ['inline' => true]);
	}

	/**
	 * Acceptance test data for Img.
	 *
	 * Картинок в документации может не быть, поэтому проверяются только
	 * отказы: отсутствующий файл и недопустимое расширение — 404.
	 */
	public function testImg(): array
	{
		return [
			[
				'name' => 'missing',
				'GET' => ['path' => 'img/no-such-image.png'],
				'response' => 404,
			],
			[
				'name' => 'bad extension',
				'GET' => ['path' => 'README.md'],
				'response' => 404,
			],
		];
	}
}
