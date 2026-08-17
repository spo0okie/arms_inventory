<?php

namespace app\helpers;

use app\components\Forms\ActiveField;
use app\models\base\ArmsModel;
use app\models\ui\WikiCache;
use app\types\TextType;

/**
 * Сканер интервики-ссылок в текстовых полях инвентаризации
 * (страница /web/wiki/interwiki, интеграция — docs/help/admin/integrations/dokuwiki.md).
 *
 * Что сканируется:
 *  - все атрибуты всех моделей, у которых тип TextType и включен рендер через
 *    DokuWiki (params['textFields'], разрешается через ActiveField::textFieldType,
 *    т.е. учитывается и 'default'=>'dokuwiki');
 *  - тексты страниц wiki, включенных в такое поле директивами плагина include
 *    ({{page>...}} / {{section>...}}) - рекурсивно, до maxIncludeDepth уровней.
 *    Списки включений извлекает WikiCache::extractDependencies() - тот же код,
 *    что ведет учет зависимостей кэша, поэтому синтаксис понимается одинаково.
 *
 * Что считается интервики-ссылкой: ссылка [[shortcut>страница|подпись]], где
 * shortcut - буквы/цифры/точки (так же, как определяет сам парсер DokuWiki).
 *
 * Использование:
 * ```php
 * $scanner=new WikiLinksScanner();
 * $groups=$scanner->scan();               //сгруппированный результат
 * $totals=$scanner->getTotals();          //счетчики
 * $failures=$scanner->getFailures();      //страницы wiki, которые не отдались
 * ```
 *
 * Разбор ссылок и группировка - чистые статические функции (без БД и без wiki),
 * покрыты юнит-тестами tests/unit/helpers/WikiLinksScannerTest.php.
 */
class WikiLinksScanner
{
	/** @var int предельная глубина обхода включений {{page>...}} (защита от глубоких деревьев) */
	public $maxIncludeDepth=3;

	/** @var bool следовать ли за включениями страниц wiki (выключение экономит запросы к wiki) */
	public $followIncludes=true;

	/**
	 * @var callable|null чем получать исходный текст страницы wiki: function(string $page): ?string
	 * (null - через JSON-RPC самой wiki; подменяется в тестах)
	 */
	public $pageFetcher=null;

	/** @var array кэш исходников страниц wiki за прогон: страница => текст|null */
	protected $pages=[];

	/** @var array страницы wiki, которые не удалось получить: страница => откуда включена */
	protected $failures=[];

	/** @var array сквозные счетчики (см. getTotals()) */
	protected $totals=[
		'classes'=>0,		//моделей с dokuwiki-атрибутами
		'attributes'=>0,	//просканировано атрибутов
		'objects'=>0,		//объектов, в чьих полях нашлась хоть одна интервики-ссылка
		'texts'=>0,			//полей с потенциальной разметкой (прошли фильтр запроса)
		'links'=>0,			//всего найдено интервики-ссылок
		'includes'=>0,		//всего загружено включенных страниц wiki
	];

	/**
	 * Разбирает интервики-ссылки в тексте DokuWiki.
	 *
	 * Интервики-ссылка - [[shortcut>страница|подпись]]: shortcut состоит из букв,
	 * цифр и точек (правило самого DokuWiki), поэтому обычные внутренние
	 * ([[namespace:page]]) и внешние (http://...) ссылки сюда не попадают.
	 *
	 * @param string $text текст с разметкой
	 * @return array список ['shortcut','target','title','raw']
	 */
	public static function parseInterwikiLinks(string $text): array
	{
		$links=[];
		if ($text==='') return $links;

		//[[ссылка]] и [[ссылка|подпись]]; в ссылке не может быть '|' и ']'
		if (!preg_match_all('/\[\[([^\]|]+)(?:\|([^\]]*))?]]/u', $text, $matches, PREG_SET_ORDER))
			return $links;

		foreach ($matches as $match) {
			$link=trim($match[1]);
			//правило DokuWiki: shortcut - [a-zA-Z0-9.]+ перед '>'
			if (!preg_match('/^([a-zA-Z0-9.]+)>(.*)$/us', $link, $tokens)) continue;
			$links[]=[
				'shortcut'=>$tokens[1],
				'target'=>trim($tokens[2]),
				'title'=>isset($match[2])?trim($match[2]):'',
				'raw'=>$match[0],
			];
		}

		return $links;
	}

	/**
	 * Возвращает атрибуты моделей, которые рендерятся через DokuWiki.
	 *
	 * Проверяются только реальные колонки таблицы (их можно прочитать запросом)
	 * с типом TextType. Модели без таблицы и атрибуты с невыводимым типом
	 * пропускаются.
	 *
	 * @param array|null $modelClasses список классов (по умолчанию все модели ARMS)
	 * @return array FQCN модели => список атрибутов
	 */
	public static function dokuwikiAttributes(?array $modelClasses=null): array
	{
		if ($modelClasses===null) $modelClasses=ModelHelper::getModelClasses();

		$result=[];
		foreach ($modelClasses as $class) {
			try {
				/** @var ArmsModel $model */
				$model=new $class();
				$attributes=$model->attributes();	//колонки таблицы
			} catch (\Throwable $e) {
				continue;	//модель без таблицы/недоступная схема - сканировать нечего
			}
			foreach ($attributes as $attribute) {
				try {
					$type=$model->getAttributeTypeClass($attribute);
				} catch (\Throwable $e) {
					continue;	//тип не выводится - это не текстовое поле с разметкой
				}
				if (!$type instanceof TextType) continue;
				if (ActiveField::textFieldType($class,$attribute)!=='dokuwiki') continue;
				$result[$class][]=$attribute;
			}
		}

		return $result;
	}

	/**
	 * Сканирует инвентаризацию и возвращает сгруппированный результат.
	 *
	 * @param array|null $attributes что сканировать: FQCN => список атрибутов
	 *                               (по умолчанию dokuwikiAttributes())
	 * @return array результат группировки (см. group())
	 */
	public function scan(?array $attributes=null): array
	{
		if ($attributes===null) $attributes=static::dokuwikiAttributes();

		$usages=[];
		$objects=[];
		$this->totals['classes']=count($attributes);

		foreach ($attributes as $class=>$classAttributes) {
			/** @var ArmsModel $class */
			foreach ($classAttributes as $attribute) {
				$this->totals['attributes']++;
				//в выборку берем только записи, где вообще есть ссылка или включение
				$models=$class::find()
					->where(['or',
						['like',$attribute,'[['],
						['like',$attribute,'{{page>'],
						['like',$attribute,'{{section>'],
					])
					->all();

				foreach ($models as $model) {
					$text=(string)$model->$attribute;
					if ($text==='') continue;
					$this->totals['texts']++;
					$found=$this->scanText($text,[
						'class'=>$class,
						'id'=>$model->id,
						'model'=>$model,
						'attribute'=>$attribute,
					]);
					if (!count($found)) continue;
					$objects[$class.':'.$model->id]=true;
					$usages=array_merge($usages,$found);
				}
			}
		}

		$this->totals['objects']=count($objects);
		$this->totals['links']=count($usages);

		return static::group($usages);
	}

	/**
	 * Ищет интервики-ссылки в одном тексте: и в нем самом, и во всех страницах
	 * wiki, включенных в него через {{page>...}} / {{section>...}} (рекурсивно).
	 *
	 * @param string $text  текст поля
	 * @param array  $context общие данные источника (class,id,model,attribute)
	 * @return array список ссылок, к каждой добавлены данные источника и цепочка
	 *               включений 'via' (пустая - ссылка лежит в самом поле)
	 */
	public function scanText(string $text, array $context=[]): array
	{
		$visited=[];
		return $this->walk($text,'',[],$visited,$context);
	}

	/**
	 * Рекурсивный обход текста и его включений
	 * @param string $text     текст очередной страницы/поля
	 * @param string $page     путь страницы wiki (для относительных включений; '' - поле объекта)
	 * @param array  $via      цепочка включений, по которой мы сюда пришли
	 * @param array  $visited  уже посещенные в этой ветке страницы (защита от циклов)
	 * @param array  $context  общие данные источника
	 * @return array
	 */
	protected function walk(string $text, string $page, array $via, array &$visited, array $context): array
	{
		$found=[];
		foreach (static::parseInterwikiLinks($text) as $link) {
			$found[]=array_merge($context,$link,['via'=>$via]);
		}

		if (!$this->followIncludes) return $found;
		if (count($via)>=$this->maxIncludeDepth) return $found;

		foreach (WikiCache::extractDependencies($text,$page) as $included) {
			if (isset($visited[$included])) continue;	//цикл включений либо повтор
			$visited[$included]=true;

			$includedText=$this->fetchPage($included);
			if ($includedText===null) {
				//страницу не отдали (нет доступа/нет страницы/wiki не настроена)
				$this->failures[$included]=$page===''?
					(($context['class']??'?').'#'.($context['id']??'?').' -> '.($context['attribute']??'?')):
					$page;
				continue;
			}

			$found=array_merge($found,$this->walk(
				$includedText,
				$included,
				array_merge($via,[$included]),
				$visited,
				$context
			));
		}

		return $found;
	}

	/**
	 * Возвращает исходный текст страницы wiki (с кэшированием в пределах прогона)
	 * @param string $page путь страницы
	 * @return string|null null - страницу получить не удалось
	 */
	protected function fetchPage(string $page): ?string
	{
		if (array_key_exists($page,$this->pages)) return $this->pages[$page];

		$fetcher=$this->pageFetcher;
		if (is_callable($fetcher)) {
			$text=$fetcher($page);
		} else {
			$text=WikiHelper::fetchJsonRpc('wiki.getPage',['id'=>$page]);
		}

		//JSON-RPC отдает false при ошибке и пустую строку для несуществующей страницы
		if ($text===false || $text===null || !is_string($text)) return $this->pages[$page]=null;

		$this->totals['includes']++;
		return $this->pages[$page]=$text;
	}

	/**
	 * Группирует плоский список найденных ссылок:
	 * shortcut => ['shortcut','count','targets'=>[страница => ['target','count','usages']]]
	 *
	 * Группы отсортированы по количеству ссылок (по убыванию), страницы внутри
	 * группы - по алфавиту.
	 *
	 * @param array $usages плоский список (см. walk())
	 * @return array
	 */
	public static function group(array $usages): array
	{
		$groups=[];
		foreach ($usages as $usage) {
			$shortcut=$usage['shortcut'];
			$target=$usage['target'];
			if (!isset($groups[$shortcut]))
				$groups[$shortcut]=['shortcut'=>$shortcut,'count'=>0,'targets'=>[]];
			if (!isset($groups[$shortcut]['targets'][$target]))
				$groups[$shortcut]['targets'][$target]=['target'=>$target,'count'=>0,'usages'=>[]];

			$groups[$shortcut]['count']++;
			$groups[$shortcut]['targets'][$target]['count']++;
			$groups[$shortcut]['targets'][$target]['usages'][]=$usage;
		}

		foreach ($groups as $shortcut=>$group) {
			$targets=$group['targets'];
			uksort($targets,'strnatcasecmp');
			$groups[$shortcut]['targets']=$targets;
		}

		uasort($groups,function($a,$b) {
			return ($b['count']<=>$a['count'])?:strnatcasecmp($a['shortcut'],$b['shortcut']);
		});

		return $groups;
	}

	/**
	 * Счетчики прогона: classes, attributes, objects, texts, links, includes
	 * @return array
	 */
	public function getTotals(): array
	{
		return $this->totals;
	}

	/**
	 * Страницы wiki, которые не удалось получить: страница => откуда включена
	 * @return array
	 */
	public function getFailures(): array
	{
		return $this->failures;
	}
}
