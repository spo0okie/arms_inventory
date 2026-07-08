<?php

namespace app\helpers;

use kartik\markdown\Markdown;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Работа со встроенной документацией (слой 2, см. docs/help/README.md и plans/help-docs.md).
 *
 * Документация — MD-файлы в @app/docs/help (читаются и с GitHub без приложения).
 * Заказчик может переопределять/дополнять страницы через params['docsOverridePath'] —
 * каталог с той же структурой, который просматривается первым.
 *
 * Привязка страниц к коду — только конвенцией имён файлов
 * (models/<class-id>.md, models/<class-id>/<attr>.md, types/<type-id>.md),
 * поэтому целостность стережётся тестами (HelpOrphanTest и др.).
 */
class DocsHelper
{
	/** каталог документации в репозитории */
	const BASE_PATH = '@app/docs/help';

	/** расширения картинок, которые отдаёт DocsController::actionImg */
	const IMG_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];

	/**
	 * Нормализует относительный путь страницы: прямые слэши, без ведущего /,
	 * схлопывает '.' и '..'. Выход за корень ('..' сверх меры) => null.
	 * @param string $path
	 * @return string|null
	 */
	public static function normalizeRelPath(string $path): ?string
	{
		$path = str_replace('\\', '/', $path);
		$result = [];
		foreach (explode('/', $path) as $token) {
			if ($token === '' || $token === '.') continue;
			if ($token === '..') {
				if (!count($result)) return null;
				array_pop($result);
				continue;
			}
			$result[] = $token;
		}
		return count($result) ? implode('/', $result) : null;
	}

	/**
	 * Корни документации в порядке приоритета: override заказчика, затем репозиторий.
	 * @return string[] абсолютные пути существующих каталогов
	 */
	public static function docRoots(): array
	{
		$roots = [];
		if (!empty(Yii::$app->params['docsOverridePath']))
			$roots[] = Yii::$app->params['docsOverridePath'];
		$roots[] = Yii::getAlias(static::BASE_PATH);
		return array_filter($roots, 'is_dir');
	}

	/**
	 * Ищет файл по относительному пути в цепочке корней.
	 * @param string      $relPath относительный путь (уже или ещё не нормализованный)
	 * @param string[]|null $roots переопределение корней (для тестов)
	 * @return string|null абсолютный путь найденного файла
	 */
	public static function findFile(string $relPath, ?array $roots = null): ?string
	{
		$relPath = static::normalizeRelPath($relPath);
		if ($relPath === null) return null;
		foreach ($roots ?? static::docRoots() as $root) {
			$candidate = rtrim($root, '/\\') . DIRECTORY_SEPARATOR
				. str_replace('/', DIRECTORY_SEPARATOR, $relPath);
			if (is_file($candidate)) return $candidate;
		}
		return null;
	}

	/**
	 * Ищет MD-страницу; не-md пути отвергает.
	 * @return string|null абсолютный путь
	 */
	public static function findPage(string $relPath, ?array $roots = null): ?string
	{
		if (strtolower(pathinfo($relPath, PATHINFO_EXTENSION)) !== 'md') return null;
		return static::findFile($relPath, $roots);
	}

	/**
	 * Ищет картинку документации; допускает только IMG_EXTENSIONS.
	 * @return string|null абсолютный путь
	 */
	public static function findImage(string $relPath, ?array $roots = null): ?string
	{
		$ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
		if (!in_array($ext, static::IMG_EXTENSIONS, true)) return null;
		return static::findFile($relPath, $roots);
	}

	/**
	 * Заголовок страницы — первый H1 ('# ...') файла; иначе имя файла без расширения.
	 */
	public static function pageTitle(string $file): string
	{
		$handle = fopen($file, 'rb');
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				$line = trim($line);
				if ($line === '') continue;
				fclose($handle);
				if (preg_match('/^#\s+(.+)$/u', $line, $matches)) return trim($matches[1]);
				break;
			}
			if (is_resource($handle)) fclose($handle);
		}
		return pathinfo($file, PATHINFO_FILENAME);
	}

	/** канонические H2-секции модельных страниц (plans/help-inline.md) */
	const CANON_SECTIONS = ['Список', 'Просмотр', 'Добавление', 'Редактирование', 'Удаление'];

	/**
	 * Рендерит MD-страницу в HTML: markdown + переписывание относительных ссылок
	 * на маршруты DocsController (страницы -> docs/page, картинки -> docs/img).
	 * Готовый HTML кэшируется до изменения файла (ключ включает filemtime).
	 * @param string $file       абсолютный путь файла
	 * @param string $relPath    его относительный путь (для резолва ссылок соседей)
	 * @param bool   $stripTitle убрать первый H1 (для встраивания страницы
	 *                           в футер страницы сущности со своим заголовком)
	 */
	public static function renderPage(string $file, string $relPath, bool $stripTitle = false): string
	{
		return static::renderCached([$file, filemtime($file), $stripTitle], function () use ($file, $stripTitle, $relPath) {
			$content = file_get_contents($file);
			if ($stripTitle) $content = preg_replace('/^#\s+.*\R/u', '', $content, 1);
			$html = Markdown::convert($content);
			return static::rewriteHtmlLinks($html, static::relDir($relPath));
		});
	}

	/**
	 * Рендерит фрагмент MD-страницы для инфоблока в UI (DocsPanelWidget):
	 * преамбулу (section=null: от начала без H1 до первого H2) либо тело
	 * канонической H2-секции (до следующего H2; H3+ остаются внутри).
	 * Ссылки на другие MD открываются модалкой (класс open-in-modal-form),
	 * чтобы читатель не покидал страницу. Кэш — как у renderPage.
	 * @param string      $file    абсолютный путь файла
	 * @param string      $relPath его относительный путь внутри docs/help
	 * @param string|null $section null - преамбула, иначе точное имя H2-секции
	 * @return string '' если секции нет
	 */
	public static function renderSection(string $file, string $relPath, ?string $section): string
	{
		return static::renderCached([$file, filemtime($file), 'section', $section], function () use ($file, $relPath, $section) {
			$content = static::extractSection(file_get_contents($file), $section);
			if (!strlen(trim($content))) return '';
			$html = Markdown::convert($content);
			return static::rewriteHtmlLinks(
				$html,
				static::relDir($relPath),
				null,
				'class="open-in-modal-form"'
			);
		});
	}

	/**
	 * Вырезает из MD-текста преамбулу (section=null: всё до первого H2,
	 * первый H1 отбрасывается) или тело H2-секции с указанным заголовком.
	 * @return string '' если секции нет
	 */
	public static function extractSection(string $content, ?string $section): string
	{
		$lines = preg_split('/\R/u', $content);
		$result = [];
		$collect = $section === null; //преамбулу собираем сразу
		$skippedTitle = false;
		foreach ($lines as $line) {
			//H2 - граница секций (H3+ не граница, остаются внутри)
			if (preg_match('/^##(?!#)\s*(.+?)\s*$/u', $line, $matches)) {
				if ($collect) break; //секция кончилась
				$collect = ($section !== null && $matches[1] === $section);
				continue; //строка заголовка в тело не входит (заголовок даёт подача)
			}
			if (!$collect) continue;
			//первый H1 преамбулы отбрасываем (заголовок даёт подача)
			if (!$skippedTitle && $section === null && preg_match('/^#(?!#)/u', $line)) {
				$skippedTitle = true;
				continue;
			}
			$result[] = $line;
		}
		return implode("\n", $result);
	}

	/**
	 * Кэш готового HTML: до изменения файла (в ключе filemtime).
	 * Без кэш-компонента (тесты хелпера без приложения) - рендер напрямую.
	 * @param array    $key
	 * @param callable $render
	 */
	protected static function renderCached(array $key, callable $render): string
	{
		$cache = Yii::$app->cache ?? null;
		if (!$cache) return $render();
		return $cache->getOrSet(array_merge(['docsRender'], $key), $render);
	}

	/**
	 * Каталог страницы внутри docs/help ('' для корня).
	 */
	public static function relDir(string $relPath): string
	{
		$dir = dirname(str_replace('\\', '/', $relPath));
		return ($dir === '.' || $dir === '/') ? '' : $dir;
	}

	/**
	 * Переписывает относительные href/src в HTML: *.md -> страница документации,
	 * картинки -> отдача картинки. Абсолютные URL, якоря и mailto не трогает.
	 * @param string        $html
	 * @param string        $pageDir     каталог текущей страницы внутри docs/help
	 * @param callable|null $urlBuilder  fn(string $action, string $relPath, string $anchor): string
	 *                                   (для тестов без Yii-приложения)
	 * @param string        $mdLinkExtra дополнительные HTML-атрибуты ссылкам на MD-страницы
	 *                                   (например 'class="open-in-modal-form"' для инфоблоков)
	 */
	public static function rewriteHtmlLinks(string $html, string $pageDir, ?callable $urlBuilder = null, string $mdLinkExtra = ''): string
	{
		$urlBuilder = $urlBuilder ?? function (string $action, string $relPath, string $anchor) {
			return Url::to(["/docs/$action", 'path' => $relPath]) . $anchor;
		};

		return preg_replace_callback(
			'/(href|src)="([^"]+)"/u',
			function ($matches) use ($pageDir, $urlBuilder, $mdLinkExtra) {
				[$full, $attr, $url] = $matches;

				//не трогаем абсолютные url, якоря, mailto и абсолютные пути
				if (preg_match('~^([a-z][a-z0-9+.-]*:|//|/|#)~i', $url)) return $full;

				$anchor = '';
				if (($pos = strpos($url, '#')) !== false) {
					$anchor = substr($url, $pos);
					$url = substr($url, 0, $pos);
				}
				if ($url === '') return $full;

				$relPath = static::normalizeRelPath(($pageDir === '' ? '' : $pageDir . '/') . rawurldecode($url));
				if ($relPath === null) return $full; //ссылка за пределы документации - оставляем как есть

				$ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
				if ($ext === 'md')
					return ($mdLinkExtra ? $mdLinkExtra . ' ' : '')
						. $attr . '="' . $urlBuilder('page', $relPath, $anchor) . '"';
				if (in_array($ext, static::IMG_EXTENSIONS, true))
					return $attr . '="' . $urlBuilder('img', $relPath, '') . '"';

				return $full;
			},
			$html
		);
	}

	/** @var array|null карта страниц на время запроса [relPath=>absPath] */
	protected static $pagesMap;

	/**
	 * Карта всех MD-страниц документации: override-корень сливается с базовым,
	 * override приоритетнее при совпадении путей. Строится один раз на запрос,
	 * чтобы не делать file_exists на каждое поле формы.
	 * @param string[]|null $roots переопределение корней (для тестов, без кэша)
	 * @return array [относительный путь => абсолютный путь]
	 */
	public static function pagesMap(?array $roots = null): array
	{
		if ($roots === null && static::$pagesMap !== null) return static::$pagesMap;
		$map = [];
		//идем от менее приоритетного к более, чтобы override перезаписал базу
		foreach (array_reverse($roots ?? static::docRoots()) as $root) {
			foreach (static::scanMdFiles($root, '') as $relPath => $file) {
				$map[$relPath] = $file;
			}
		}
		ksort($map);
		if ($roots === null) static::$pagesMap = $map;
		return $map;
	}

	/**
	 * Есть ли такая страница документации (по карте, дешево для массовых проверок).
	 */
	public static function pageExists(string $relPath, ?array $roots = null): bool
	{
		$relPath = static::normalizeRelPath($relPath);
		return $relPath !== null && isset(static::pagesMap($roots)[$relPath]);
	}

	/**
	 * Дерево всех страниц документации.
	 * @param string[]|null $roots переопределение корней (для тестов)
	 * @return array отсортированный список ['path'=>относительный путь, 'title'=>заголовок]
	 */
	public static function pagesList(?array $roots = null): array
	{
		$pages = [];
		foreach (static::pagesMap($roots) as $relPath => $file) {
			$pages[] = ['path' => $relPath, 'title' => static::pageTitle($file)];
		}
		return $pages;
	}

	/**
	 * Путь MD-страницы сущности по конвенции (docs/help/README.md).
	 * @param string $classId kebab-case id модели (comps, tech-models)
	 */
	public static function modelPagePath(string $classId): string
	{
		return "models/$classId.md";
	}

	/**
	 * Путь MD-страницы атрибута по конвенции.
	 */
	public static function attributePagePath(string $classId, string $attribute): string
	{
		return "models/$classId/$attribute.md";
	}

	/**
	 * kebab-case id модели для привязки документации (тот же, что у контроллеров).
	 * Для *Search-моделей документация ищется по базовому классу.
	 * @param object|string $model объект или имя класса
	 */
	public static function modelClassId($model): string
	{
		$class = is_object($model) ? get_class($model) : $model;
		$class = preg_replace('/Search$/', '', $class);
		return StringHelper::class2Id($class);
	}

	/**
	 * Ссылки на второй слой документации (блок «переходы» тултипа атрибута,
	 * см. ui-sources.md §0.1): раздельные подписанные переходы на страницу
	 * атрибута (models/<class-id>/<attr>.md) и на страницу типа (types/<type>.md) —
	 * каждая только если страница существует. Открываются модалкой
	 * (ModalAjax слушает a.open-in-modal-form на всех страницах).
	 * Потребитель — сборщик AttributeTooltip.
	 * @param object|string $model модель (объект или имя класса)
	 * @param string        $attribute
	 * @return string[] HTML-ссылки
	 */
	public static function attributeDetailsLinks($model, string $attribute): array
	{
		$links = [];

		//страница атрибута этой модели
		$attrPath = static::attributePagePath(static::modelClassId($model), $attribute);
		if (static::pageExists($attrPath)) {
			$links[] = Html::a(
				'подробнее: ' . static::attributeCrumb($model, $attribute) . ' »',
				['/docs/page', 'path' => $attrPath],
				['class' => 'open-in-modal-form']
			);
		}

		//страница типа (одна на все модели с этим типом)
		$typePath = static::attributeTypePagePath($model, $attribute);
		if ($typePath && static::pageExists($typePath)) {
			$file = static::findPage($typePath);
			$links[] = Html::a(
				'подробнее о типе: ' . static::pageTitle($file) . ' »',
				['/docs/page', 'path' => $typePath],
				['class' => 'open-in-modal-form']
			);
		}

		return $links;
	}

	/**
	 * Подпись атрибута для ссылки: «Сущности → Атрибут».
	 * @param object|string $model
	 * @param string        $attribute
	 */
	protected static function attributeCrumb($model, string $attribute): string
	{
		$label = $attribute;
		if (is_object($model) && method_exists($model, 'getAttributeLabel')) {
			try {
				$label = $model->getAttributeLabel($attribute);
			} catch (\Throwable $e) {
			}
		}
		$titles = property_exists(is_object($model) ? get_class($model) : $model, 'titles')
			? $model::$titles : '';
		return ($titles ? $titles . ' → ' : '') . $label;
	}

	/**
	 * Путь MD-страницы типа атрибута (types/<type-name>.md) либо null,
	 * если тип атрибута не выводится.
	 * @param object|string $model
	 * @param string        $attribute
	 */
	public static function attributeTypePagePath($model, string $attribute): ?string
	{
		if (!is_object($model) || !method_exists($model, 'getAttributeTypeClass')) return null;
		try {
			$type = $model->getAttributeTypeClass($attribute);
		} catch (\Throwable $e) {
			return null;
		}
		return 'types/' . $type::name() . '.md';
	}

	/**
	 * Рекурсивно собирает MD-файлы каталога.
	 * @return array [относительный путь => абсолютный путь]
	 */
	protected static function scanMdFiles(string $root, string $relDir): array
	{
		$dir = rtrim($root, '/\\') . ($relDir === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir));
		if (!is_dir($dir)) return [];
		$files = [];
		foreach (scandir($dir) as $item) {
			if ($item === '.' || $item === '..') continue;
			$relPath = ($relDir === '' ? '' : $relDir . '/') . $item;
			$absPath = $dir . DIRECTORY_SEPARATOR . $item;
			if (is_dir($absPath)) {
				$files += static::scanMdFiles($root, $relPath);
			} elseif (strtolower(pathinfo($item, PATHINFO_EXTENSION)) === 'md') {
				$files[$relPath] = $absPath;
			}
		}
		return $files;
	}
}
