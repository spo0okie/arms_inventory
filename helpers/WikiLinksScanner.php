<?php

namespace app\helpers;

use app\components\Forms\ActiveField;
use app\components\UrlListWidget;
use app\models\base\ArmsModel;
use app\models\ui\WikiCache;
use app\types\TextType;
use app\types\UrlsType;
use Yii;

/**
 * Сканер ссылок инвентаризации во внешнюю wiki (страница /web/wiki/links,
 * интеграция - docs/help/admin/integrations/dokuwiki.md).
 *
 * Задача: показать обратные ссылки, которых не видит сама wiki. Встроенный
 * механизм DokuWiki "Ссылки сюда" учитывает только ссылки внутри wiki, а
 * инвентаризация ссылается на ее страницы из своих данных - и об этих ссылках
 * wiki не знает.
 *
 * Где ищем:
 *  - поля с разметкой DokuWiki: атрибуты типа TextType, у которых включен
 *    рендер через dokuwiki (params['textFields'], разрешается через
 *    ActiveField::textFieldType, т.е. учитывается и 'default'=>'dokuwiki');
 *  - поля-ссылки: атрибуты типа UrlsType (списки URL, обычно links);
 *  - тексты страниц wiki, включенных в поле с разметкой директивами плагина
 *    include ({{page>...}} / {{section>...}}) - рекурсивно, до maxIncludeDepth
 *    уровней. Списки включений извлекает WikiCache::extractDependencies() -
 *    тот же код, что ведет учет зависимостей кэша.
 *
 * Что находим (вид ссылки - kind у каждого места использования):
 *  - KIND_LINK    - вики-ссылка [[namespace:страница]] в поле с разметкой;
 *  - KIND_INCLUDE - включение страницы {{page>namespace:страница}};
 *  - KIND_URL     - URL на страницу этой wiki (в поле-ссылках либо в тексте);
 *  - интервики-ссылки [[shortcut>страница]] ведут в ДРУГИЕ wiki, поэтому
 *    собираются отдельным списком (getInterwiki()).
 *
 * Использование:
 * ```php
 * $scanner=new WikiLinksScanner();
 * $scanner->scan();
 * $pages=$scanner->getWikiPages();      //страницы этой wiki + кто ссылается
 * $other=$scanner->getInterwiki();      //интервики-ссылки по shortcut
 * $totals=$scanner->getTotals();
 * $failures=$scanner->getFailures();    //страницы wiki, которые не отдались
 * ```
 *
 * Разбор ссылок, URL и группировка - чистые статические функции (без БД и без
 * wiki), покрыты юнит-тестами tests/unit/helpers/WikiLinksScannerTest.php.
 */
class WikiLinksScanner
{
	/** вики-ссылка [[namespace:страница]] в поле с разметкой */
	const KIND_LINK='link';
	/** включение страницы {{page>...}} / {{section>...}} */
	const KIND_INCLUDE='include';
	/** URL на страницу этой wiki (поле-ссылок либо внешняя ссылка в тексте) */
	const KIND_URL='url';

	/** @var int предельная глубина обхода включений {{page>...}} (защита от глубоких деревьев) */
	public $maxIncludeDepth=3;

	/** @var bool следовать ли за включениями страниц wiki (выключение экономит запросы к wiki) */
	public $followIncludes=true;

	/**
	 * @var bool учитывать ли находки ВНУТРИ включенных страниц. Это ссылки
	 * самой wiki (их видно и во встроенном "Ссылки сюда"), но через включение
	 * они попадают на страницу объекта инвентаризации, поэтому по умолчанию
	 * учитываются - с пометкой цепочки включений.
	 */
	public $includeNested=true;

	/** @var string|null база URL wiki (по умолчанию params['wikiUrl']) */
	public $wikiUrl=null;

	/**
	 * @var callable|null чем получать исходный текст страницы wiki: function(string $page): ?string
	 * (null - через JSON-RPC самой wiki; подменяется в тестах)
	 */
	public $pageFetcher=null;

	/** @var array кэш исходников страниц wiki за прогон: страница => текст|null */
	protected $pageTexts=[];

	/** @var array страницы wiki, которые не удалось получить: страница => откуда включена */
	protected $failures=[];

	/** @var array ссылки на страницы ЭТОЙ wiki: страница => ['page','count','kinds','usages'] */
	protected $refs=[];

	/** @var array плоский список интервики-ссылок (в другие wiki) */
	protected $interwiki=[];

	/** @var array объекты, в которых что-то нашлось: 'класс:id' => true */
	protected $objects=[];

	/** @var array сквозные счетчики (см. getTotals()) */
	protected $totals=[
		'classes'=>0,			//сущностей с просканированными полями
		'attributes'=>0,		//полей с разметкой DokuWiki просканировано
		'urlAttributes'=>0,		//полей-списков ссылок просканировано
		'texts'=>0,				//полей с разметкой, где есть ссылка или включение
		'urlFields'=>0,			//полей-ссылок, где есть адрес wiki
		'objects'=>0,			//объектов, из которых есть хоть одна ссылка в wiki
		'refs'=>0,				//всего ссылок на страницы этой wiki
		'pages'=>0,				//уникальных страниц этой wiki
		'nested'=>0,			//из них найдено внутри включенных страниц
		'interwiki'=>0,			//интервики-ссылок (в другие wiki)
		'fetched'=>0,			//загружено страниц wiki при обходе включений
	];

	/**
	 * Разбирает и классифицирует ссылки [[...]] в тексте DokuWiki.
	 * Порядок проверок повторяет парсер DokuWiki, поэтому виды ссылок
	 * определяются так же, как их увидит сама wiki.
	 *
	 * @param string $text текст с разметкой
	 * @return array список ['kind','link','shortcut','target','title','raw'], где kind:
	 *   interwiki - [[shortcut>страница]] (ссылка в другую wiki),
	 *   internal  - [[namespace:страница]] (страница этой wiki),
	 *   external  - [[http://...]], share - [[\\server\share]],
	 *   email     - [[user@example.com]], anchor - [[#секция]]
	 */
	public static function parseLinks(string $text): array
	{
		$links=[];
		if ($text==='') return $links;

		//[[ссылка]] и [[ссылка|подпись]]; в ссылке не может быть '|' и ']'
		if (!preg_match_all('/\[\[([^\]|]+)(?:\|([^\]]*))?]]/u', $text, $matches, PREG_SET_ORDER))
			return $links;

		foreach ($matches as $match) {
			$link=trim($match[1]);
			$entry=[
				'kind'=>'internal',
				'link'=>$link,
				'shortcut'=>'',
				'target'=>$link,
				'title'=>isset($match[2])?trim($match[2]):'',
				'raw'=>$match[0],
			];

			if (preg_match('/^([a-zA-Z0-9.]+)>(.*)$/us', $link, $tokens)) {
				//правило DokuWiki: shortcut - [a-zA-Z0-9.]+ перед '>'
				$entry['kind']='interwiki';
				$entry['shortcut']=$tokens[1];
				$entry['target']=trim($tokens[2]);
			} elseif (preg_match('#^\\\\\\\\[^\\\\]+?\\\\#u', $link)) {
				$entry['kind']='share';			//\\server\share
			} elseif (preg_match('#^([a-z0-9\-.+]+?)://#i', $link)) {
				$entry['kind']='external';		//http://, https://, ftp://...
			} elseif (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/u', $link)) {
				$entry['kind']='email';
			} elseif ($link==='' || $link[0]==='#') {
				$entry['kind']='anchor';		//секция этой же страницы
			}

			$links[]=$entry;
		}

		return $links;
	}

	/**
	 * Только интервики-ссылки [[shortcut>страница]] (см. parseLinks())
	 * @param string $text
	 * @return array список ['shortcut','target','title','raw']
	 */
	public static function parseInterwikiLinks(string $text): array
	{
		$links=[];
		foreach (static::parseLinks($text) as $link) {
			if ($link['kind']!=='interwiki') continue;
			$links[]=[
				'shortcut'=>$link['shortcut'],
				'target'=>$link['target'],
				'title'=>$link['title'],
				'raw'=>$link['raw'],
			];
		}
		return $links;
	}

	/**
	 * Превращает URL в идентификатор страницы wiki.
	 * Понимает и "?id=namespace:страница", и человекочитаемые пути
	 * ("/namespace:страница" и "/namespace/страница").
	 *
	 * @param string $url     проверяемый URL
	 * @param string $wikiUrl база URL wiki (params['wikiUrl'])
	 * @return string|null null - URL не ведет в эту wiki (или ведет не на страницу)
	 */
	public static function urlToWikiPage(string $url, string $wikiUrl): ?string
	{
		$url=trim($url);
		$wikiUrl=trim($wikiUrl);
		if ($url==='' || $wikiUrl==='') return null;

		$base=rtrim($wikiUrl,'/').'/';
		if (stripos($url,$base)!==0) return null;

		$rest=substr($url,strlen($base));
		$rest=preg_replace('/#.*$/','',$rest);		//якорь секции - не часть id

		if (preg_match('/(?:^|[?&])id=([^&]*)/',$rest,$tokens)) {
			//doku.php?id=namespace:страница (в т.ч. с do=edit и прочими параметрами)
			$page=urldecode($tokens[1]);
		} else {
			$rest=preg_replace('/\?.*$/','',$rest);
			//служебные адреса (медиа, скрипты) страницами не являются
			if (preg_match('#^(lib|_media|_detail|_export)(/|$)#',$rest)) return null;
			$page=urldecode(trim($rest,'/'));
			//doku.php без параметров - стартовая страница
			if ($page==='doku.php') $page='';
		}

		$page=trim(str_replace('/',':',$page),': ');
		//корень wiki - стартовая страница ('start' - имя по умолчанию в DokuWiki)
		if ($page==='') $page='start';

		return $page;
	}

	/**
	 * Возвращает атрибуты моделей, которые рендерятся через DokuWiki.
	 *
	 * Проверяются только реальные колонки таблицы (их можно прочитать запросом)
	 * ровно типа TextType: наследники (urls, json, macs...) рендерятся своим
	 * типом и разметку DokuWiki не отдают.
	 *
	 * @param array|null $modelClasses список классов (по умолчанию все модели ARMS)
	 * @return array FQCN модели => список атрибутов
	 */
	public static function dokuwikiAttributes(?array $modelClasses=null): array
	{
		return static::attributesOfType($modelClasses,function($class,$attribute,$type) {
			return get_class($type)===TextType::class
				&& ActiveField::textFieldType($class,$attribute)==='dokuwiki';
		});
	}

	/**
	 * Возвращает атрибуты-списки ссылок (UrlsType) всех моделей
	 * @param array|null $modelClasses список классов (по умолчанию все модели ARMS)
	 * @return array FQCN модели => список атрибутов
	 */
	public static function urlAttributes(?array $modelClasses=null): array
	{
		return static::attributesOfType($modelClasses,function($class,$attribute,$type) {
			return $type instanceof UrlsType;
		});
	}

	/**
	 * Перебирает колонки всех моделей и отбирает подходящие под фильтр
	 * @param array|null $modelClasses список классов (по умолчанию все модели ARMS)
	 * @param callable   $filter       function($class,$attribute,$type): bool
	 * @return array FQCN модели => список атрибутов
	 */
	protected static function attributesOfType(?array $modelClasses, callable $filter): array
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
					continue;	//тип не выводится - это вычисляемое поле
				}
				if (!$filter($class,$attribute,$type)) continue;
				$result[$class][]=$attribute;
			}
		}

		return $result;
	}

	/**
	 * Сканирует инвентаризацию. Результат забирается геттерами
	 * getWikiPages() / getInterwiki() / getTotals() / getFailures().
	 *
	 * @param array|null $attributes    поля с разметкой: FQCN => атрибуты (по умолчанию dokuwikiAttributes())
	 * @param array|null $urlAttributes поля-ссылки: FQCN => атрибуты (по умолчанию urlAttributes())
	 * @return void
	 */
	public function scan(?array $attributes=null, ?array $urlAttributes=null): void
	{
		if ($attributes===null) $attributes=static::dokuwikiAttributes();
		if ($urlAttributes===null) $urlAttributes=static::urlAttributes();

		$this->totals['classes']=count(array_unique(array_merge(
			array_keys($attributes),array_keys($urlAttributes)
		)));

		$this->scanTextAttributes($attributes);
		$this->scanUrlAttributes($urlAttributes);

		$this->totals['objects']=count($this->objects);
		$this->totals['pages']=count($this->refs);
		$this->totals['interwiki']=count($this->interwiki);
	}

	/**
	 * Поля с разметкой DokuWiki: вики-ссылки, включения и URL на wiki в тексте
	 * @param array $attributes FQCN => список атрибутов
	 */
	protected function scanTextAttributes(array $attributes): void
	{
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
					$this->scanText($text,[
						'class'=>$class,
						'id'=>$model->id,
						'model'=>$model,
						'attribute'=>$attribute,
					]);
				}
			}
		}
	}

	/**
	 * Поля-списки ссылок: адреса, ведущие на страницы этой wiki
	 * @param array $urlAttributes FQCN => список атрибутов
	 */
	protected function scanUrlAttributes(array $urlAttributes): void
	{
		$wikiUrl=$this->getWikiUrl();
		if ($wikiUrl==='') return;	//wiki не подключена - искать нечего

		foreach ($urlAttributes as $class=>$classAttributes) {
			/** @var ArmsModel $class */
			foreach ($classAttributes as $attribute) {
				$this->totals['urlAttributes']++;
				$models=$class::find()
					->where(['like',$attribute,$wikiUrl])
					->all();

				foreach ($models as $model) {
					$value=(string)$model->$attribute;
					if ($value==='') continue;
					$context=[
						'class'=>$class,
						'id'=>$model->id,
						'model'=>$model,
						'attribute'=>$attribute,
					];
					$found=0;
					foreach (explode("\n",$value) as $line) {
						$line=trim($line);
						if ($line==='') continue;
						$item=UrlListWidget::parseListItem($line);
						$page=static::urlToWikiPage($item['url'],$wikiUrl);
						if ($page===null) continue;
						$this->registerRef($page,static::KIND_URL,[],$context,[
							'title'=>$item['descr']==$item['url']?'':$item['descr'],
							'raw'=>$item['url'],
						]);
						$found++;
					}
					if ($found) $this->totals['urlFields']++;
				}
			}
		}
	}

	/**
	 * Разбирает один текст с разметкой: ссылки в нем самом и во всех страницах
	 * wiki, включенных в него через {{page>...}} / {{section>...}} (рекурсивно).
	 *
	 * @param string $text    текст поля
	 * @param array  $context данные источника (class,id,model,attribute)
	 * @return int сколько ссылок зарегистрировано
	 */
	public function scanText(string $text, array $context=[]): int
	{
		$visited=[];
		return $this->walk($text,'',[],$visited,$context);
	}

	/**
	 * Рекурсивный обход текста и его включений
	 * @param string $text     текст очередной страницы/поля
	 * @param string $page     путь страницы wiki (для относительных ссылок; '' - поле объекта)
	 * @param array  $via      цепочка включений, по которой мы сюда пришли
	 * @param array  $visited  уже посещенные в этой ветке страницы (защита от циклов)
	 * @param array  $context  данные источника
	 * @return int
	 */
	protected function walk(string $text, string $page, array $via, array &$visited, array $context): int
	{
		$count=0;

		foreach (static::parseLinks($text) as $link) {
			switch ($link['kind']) {
				case 'interwiki':
					//ссылка в чужую wiki - в отдельный список
					if (!$this->countable($via)) break;
					$this->interwiki[]=array_merge($context,[
						'shortcut'=>$link['shortcut'],
						'target'=>$link['target'],
						'title'=>$link['title'],
						'raw'=>$link['raw'],
						'via'=>$via,
					]);
					$count++;
					break;

				case 'internal':
					$target=static::stripAnchor($link['target']);
					if ($target==='') break;
					$id=WikiCache::absLinkPath($target,$page);
					if ($id==='') break;
					$count+=$this->registerRef($id,static::KIND_LINK,$via,$context,$link);
					break;

				case 'external':
					//внешняя ссылка может вести в эту же wiki (полным URL)
					$id=static::urlToWikiPage($link['target'],$this->getWikiUrl());
					if ($id===null) break;
					$count+=$this->registerRef($id,static::KIND_URL,$via,$context,$link);
					break;
			}
		}

		if (!$this->followIncludes) return $count;
		if (count($via)>=$this->maxIncludeDepth) return $count;

		foreach (WikiCache::extractDependencies($text,$page) as $included) {
			if (isset($visited[$included])) continue;	//цикл включений либо повтор
			$visited[$included]=true;

			//само включение - тоже ссылка инвентаризации на страницу wiki
			$count+=$this->registerRef($included,static::KIND_INCLUDE,$via,$context,[
				'raw'=>'{{page>'.$included.'}}',
			]);

			$includedText=$this->fetchPage($included);
			if ($includedText===null) {
				//страницу не отдали (нет доступа/нет страницы/wiki не настроена)
				$this->failures[$included]=$page===''?
					(($context['class']??'?').'#'.($context['id']??'?').' -> '.($context['attribute']??'?')):
					$page;
				continue;
			}

			$count+=$this->walk(
				$includedText,
				$included,
				array_merge($via,[$included]),
				$visited,
				$context
			);
		}

		return $count;
	}

	/**
	 * Запоминает ссылку инвентаризации на страницу этой wiki
	 * @param string $page    страница wiki
	 * @param string $kind    вид ссылки (KIND_*)
	 * @param array  $via     цепочка включений (пустая - ссылка в самом поле объекта)
	 * @param array  $context данные источника (class,id,model,attribute)
	 * @param array  $link    данные ссылки: title, raw
	 * @return int 1 - ссылка учтена, 0 - пропущена (находка внутри включения при includeNested=false)
	 */
	protected function registerRef(string $page, string $kind, array $via, array $context, array $link=[]): int
	{
		if (!$this->countable($via)) return 0;

		if (!isset($this->refs[$page]))
			$this->refs[$page]=['page'=>$page,'count'=>0,'kinds'=>[],'usages'=>[]];

		$this->refs[$page]['count']++;
		$this->refs[$page]['kinds'][$kind]=($this->refs[$page]['kinds'][$kind]??0)+1;
		$this->refs[$page]['usages'][]=array_merge($context,[
			'kind'=>$kind,
			'title'=>$link['title']??'',
			'raw'=>$link['raw']??'',
			'via'=>$via,
		]);

		$this->totals['refs']++;
		if (count($via)) $this->totals['nested']++;
		if (isset($context['class'],$context['id']))
			$this->objects[$context['class'].':'.$context['id']]=true;

		return 1;
	}

	/**
	 * Учитывать ли находку с такой цепочкой включений
	 * @param array $via
	 * @return bool
	 */
	protected function countable(array $via): bool
	{
		return $this->includeNested || !count($via);
	}

	/**
	 * Отрезает якорь секции и параметры от цели ссылки
	 * @param string $target
	 * @return string
	 */
	public static function stripAnchor(string $target): string
	{
		$target=preg_replace('/[#?].*$/u','',$target);
		return trim($target);
	}

	/**
	 * Возвращает исходный текст страницы wiki (с кэшированием в пределах прогона)
	 * @param string $page путь страницы
	 * @return string|null null - страницу получить не удалось
	 */
	protected function fetchPage(string $page): ?string
	{
		if (array_key_exists($page,$this->pageTexts)) return $this->pageTexts[$page];

		$fetcher=$this->pageFetcher;
		if (is_callable($fetcher)) {
			$text=$fetcher($page);
		} else {
			$text=WikiHelper::fetchJsonRpc('wiki.getPage',['id'=>$page]);
		}

		//JSON-RPC отдает false при ошибке и пустую строку для несуществующей страницы
		if (!is_string($text) || trim($text)==='') return $this->pageTexts[$page]=null;

		$this->totals['fetched']++;
		return $this->pageTexts[$page]=$text;
	}

	/**
	 * База URL подключенной wiki
	 * @return string
	 */
	public function getWikiUrl(): string
	{
		if ($this->wikiUrl!==null) return $this->wikiUrl;
		return (string)(Yii::$app->params['wikiUrl']??'');
	}

	/**
	 * Страницы этой wiki, на которые ссылается инвентаризация.
	 * Отсортировано по количеству ссылок (по убыванию), затем по имени.
	 * @return array страница => ['page','count','kinds','usages']
	 */
	public function getWikiPages(): array
	{
		$refs=$this->refs;
		uasort($refs,function($a,$b) {
			return ($b['count']<=>$a['count'])?:strnatcasecmp($a['page'],$b['page']);
		});
		return $refs;
	}

	/**
	 * Интервики-ссылки (в другие wiki), сгруппированные по shortcut
	 * @return array см. group()
	 */
	public function getInterwiki(): array
	{
		return static::group($this->interwiki);
	}

	/**
	 * Группирует плоский список интервики-ссылок:
	 * shortcut => ['shortcut','count','targets'=>[страница => ['target','count','usages']]]
	 *
	 * Группы отсортированы по количеству ссылок (по убыванию), страницы внутри
	 * группы - по алфавиту.
	 *
	 * @param array $usages плоский список
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
	 * Счетчики прогона (см. объявление $totals)
	 * @return array
	 */
	public function getTotals(): array
	{
		return $this->totals;
	}

	/**
	 * Страницы wiki, которые не удалось получить при обходе включений:
	 * страница => откуда включена
	 * @return array
	 */
	public function getFailures(): array
	{
		return $this->failures;
	}
}
