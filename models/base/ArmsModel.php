<?php

namespace app\models\base;

use app\components\widgets\page\ModelWidget;
use app\console\commands\SyncController;
use app\helpers\ArrayHelper;
use app\helpers\RestHelper;
use app\helpers\StringHelper;
use app\models\Attaches;
use app\models\HistoryModel;
use app\models\base\traits\AttributeAnnotationModelTrait;
use app\models\base\traits\AttributeDataModelTrait;
use app\models\base\traits\AttributeLinksModelTrait;
use app\models\base\traits\ExternalDataModelTrait;
use app\models\base\traits\ValidationGenerationTrait;
use DateTime;
use DateTimeZone;
use Throwable;
use Yii;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\web\View;

/**
 * ArmsModel — базовый класс всех моделей ARMS.
 *
 * Компоненты вынесены в трейты:
 *  - AttributeDataModelTrait      - атрибуты модели и работа с ними
 *  - AttributeLinksModelTrait     - схема связей с другими моделями
 *  - ExternalDataModelTrait       - методы работы с JSON-like атрибутами-ссылками на объекты во внешних ИС
 *
 * @property int $id Идентификатор
 * @property string $name Имя экземпляра
 * @property string $sname Имя для поиска
 * @property string $comment Комментарий
 * @property string $history история
 * @property string $updatedAt Время обновления
 * @property string $updated_at Время обновления
 * @property string $updated_by Автор обновления
 * @property string $links Ссылки прикрепленные к объекту
 * @property Attaches $attaches Загруженные файлы
 * @property boolean $archived Статус архивирования элемента
 * @property boolean $canBeArchived может иметь статус архивировано
 * @property boolean $isArchived может иметь статус архивировано и он выставлен
 * @property string $external_links Внешние ссылки
 * @property string $historyClass Класс, который хранит журнал изменений моделей класса
 * @property string $controllerPath Путь до контроллера (для URI)
 * @property string $viewsPath Путь до папки Views (для рендера)
 * @property int $secondsSinceUpdate Секунды с момента обновления
 */
class ArmsModel extends ActiveRecord
{
	use ExternalDataModelTrait,
		AttributeDataModelTrait,
		AttributeLinksModelTrait,
		AttributeAnnotationModelTrait,
		ValidationGenerationTrait;

	/** @var string как называется один экземпляр модели (для страницы Create -> Новый объект) */
	public static $title='Объект';

	/** @var string как называется список моделей (для страницы Index) */
	public static $titles='Объекты';

	/**
	 * Короткое описание сущности (слой 1 документации, см. docs/help/README.md):
	 * 1-3 предложения — что это и зачем нужно. Показывается тултипом у иконки
	 * помощи (HintIconWidget) и на странице документации сущности (docs/model).
	 * Подробное описание (если нужно) — docs/help/models/<class-id>.md (слой 2).
	 * @return string
	 */
	public static function modelDescription(): string {return '';}

	/** @var string надпись на кнопке создания нового объекта в списке */
	public static $addButtonText='Добавить';

	/** @var null|string подсказка для кнопки создания нового объекта */
	public static $addButtonHint=null;

	/** @var string Префикс для страницы Create (Новый $title) */
	public static $newItemPrefix='Новый';

	/**
	 * @var string Атрибут, который считается именем модели.
	 * Его будет выводить ->renderItem,
	 * по нему будет искать search-by-name
	 */
	public static $nameAttr='name';

	//searchableOrHint удалён: общий синтаксис поиска (| & !) показывается
	//в search-тултипе автоматически (AttributeTooltip, ui-sources.md §0.1)


	/** @var string если заполнить, то будет сохранять историю в моделях этого класса */
	protected $historyClass;

	/**
	 * @var string|null Пользовательский комментарий к изменению (issue #205).
	 * Транзиентное поле — НЕ колонка мастер-таблицы. Заполняется из формы
	 * редактирования и переносится в updated_comment соответствующей
	 * History-записи при журналировании (см. HistoryModel::journal()).
	 */
	public $historyComment;

	/** @var array Кэш для рекурсивного поиска поля (Когда значение может быть в родителе и в его родителе или ...) */
	protected $recursiveCache=[];

	/** @var array Кэш для вычисляемых аттрибутов */
	protected $attrsCache=[];


	/** @var array Кэш для загрузки всех элементов через cacheAllItems().
	 * Ключ - имя класса: static-свойство базового класса ОБЩЕЕ для всех наследников,
	 * без ключа кэши разных классов затирали бы друг друга */
	private static $allItems=[];

	/** @var bool при сохранении не менять отметку времени и не менять время обновления */
	protected $doNotChangeAuthor=false;

	// (для функционала импорта/синхронизации)

	/** @var array поля которые у этой модели можно синхронизировать с удаленной системы */
	protected static $syncableFields=[];

	/** @var array ссылки других объектов на этот, которые надо синхронизировать */
	public static $syncableReverseLinks=[];

	/** @var array ссылки этого объекта на другие, которые надо синхронизировать */
	public static $syncableDirectLinks=[];

	/** @var array many-many ссылки этого объекта на другие, которые надо синхронизировать */
	public static $syncableMany2ManyLinks=[];

	/** @var string|null ключ, по которому можно найти такой объект на удаленной системе */
	public static $syncKey='name';

	/** @var string поле которое сравнивается  */
	public static $syncTimestamp='updated_at';

	const SCENARIO_VALIDATION = 'validation';


	/**
	 * Прикручиваем поведения для many-2-many ссылок из AttributeLinksModelTrait
	 * @return array
	 */
	public function behaviors()
	{
		return [
			$this->relationsBehaviour(),
		];
	}

	/**
	 * список ролей которые может реализовать модель (для генерации)
	 */
	public static function roles(): array
	{
		return[];
	}


	/**
	 * Сразу добавляем в набор дополнительных полей все ссылки на другие модели из AttributeLinksModelTrait
	 * @return array
	 */
	public function extraFields()
	{
		$links=[];
		foreach ($this->getLinksSchema() as $link=>$schema) {
			$links[]=$link;
			$links[]=$this->attributeLinkLoader($link,$schema);
		}
		$fields = array_unique(
			array_merge(
				array_keys(parent::extraFields()),
				$links //все ссылки many-2-many
			)
		);

		return array_combine($fields, $fields);
	}

	/**
	 * Базовая работа с вложениями
	 * @return ActiveQuery
	 */
	public function getAttaches() {
		return $this->hasMany(Attaches::class,[static::tableName().'_id'=>'id'	]);
	}

	/**
	 * Возвращает массив ['id'=>'имя'] моделей (регулярно используется для Select2)
	 * @return array
	 */
	public static function fetchNames(){
		$list= static::find()
			//->select(['id','name'])
			->all();
		return ArrayHelper::map($list, 'id', 'sname');
	}

	/**
	 * Геттер для атрибута $name
	 * для моделей у которых нет атрибута name в таблице
	 * @return string
	 */
	public function getName(){
		return $this->{static::$nameAttr};
	}

	/**
	 * Геттер для атрибута $sname (имя для поиска)
	 * по умолчанию возвращает $name
	 * в дочерних моделях может быть перекрыт более развернутой информацией о модели
	 * @return string
	 */
	public function getSname(){
		return $this->name;
	}

	/**
	 * Возвращает время прошедшее с обновления модели
	 * @return int
	 * @throws \Exception
	 */
	public function getSecondsSinceUpdate() {
		$updated = new	DateTime($this->updated_at,	new DateTimeZone('UTC') );
		return time()-$updated->format('U');
	}

	/**
	 * Признак того, что есть загруженный кэш всех моделей этого класса
	 * @return bool
	 */
	public static function allItemsLoaded() {
		return isset(self::$allItems[static::class]);
	}

	/**
	 * Положить готовый набор моделей в кэш всех элементов
	 * (для переопределенных cacheAllItems, например с жадной загрузкой связей)
	 * @param array $items [id => модель]
	 */
	protected static function setAllItems(array $items) {
		self::$allItems[static::class]=$items;
	}

	/**
	 * Загрузить все модели в кэш
	 * Полезно только для моделей без или с малым количеством связей, так как они не грузятся в кэш
	 * @return void
	 */
	public static function cacheAllItems() {
		if (!static::allItemsLoaded())
			static::setAllItems(ArrayHelper::index(static::find()->all(),'id'));
	}

	/**
	 * Вернуть кэш всех моделей
	 * если модели не загружены в кэш то в зависимости от значения $autoload
	 *  - вернет null (autoload=false, по умолчанию)
	 *  - загрузит в кэш и вернет все модели (autoload=true)
	 * @param boolean $autoload формировать ли кэш, если он не загружен (иначе вернет null)
	 * @return array|null
	 */
	public static function getAllItems($autoload=false) {
		if (!static::allItemsLoaded() && $autoload)
			static::cacheAllItems();
		return self::$allItems[static::class]??null;
	}

	/**
	 * Вернуть одну модель из кэша
	 * @param integer $id ID модели
	 * @param boolean $autoload формировать ли кэш, если он не загружен (иначе вернет null)
	 * @return mixed|null
	 */
	public static function getLoadedItem($id,$autoload=false) {
		if (!static::allItemsLoaded()) {
			if ($autoload)
				static::cacheAllItems();
			else
				return null;
		}
		return self::$allItems[static::class][$id]??null;
	}

	/**
	 * Сбросить кэш всех элементов этого класса
	 * (вызывается из afterSave/afterDelete, чтобы кэш не отдавал устаревшие данные
	 * при записи в том же процессе: тесты, конвейеры, POST-экшены)
	 */
	public static function invalidateAllItemsCache() {
		unset(self::$allItems[static::class]);
	}

	/**
	 * Сбросить кэши количеств и списков ID ВСЕХ классов.
	 * Сохранение любой модели может менять количества/привязки у чужих классов
	 * (junction-строки пишутся при сохранении владельца ссылок, например
	 * сохранение ОС меняет счетчики её ПО) - точечная инвалидация невозможна,
	 * а пересборка дешевая (один запрос на ключ при следующем обращении)
	 */
	public static function flushCountsCaches() {
		self::$countsCache=[];
		self::$idsCache=[];
	}

	/** @var array Identity map точечно загруженных моделей: [класс][id] => экземпляр.
	 * У ActiveRecord нет своего identity map: findOne каждый раз создает НОВЫЙ экземпляр,
	 * а разные ветки рендера одной страницы (findModel карточки, виджеты, колонки гридов)
	 * независимо резолвят одну и ту же запись - каждая своим SELECT.
	 * Намеренные повторные чтения write-path ($old/$fresh=static::findOne($this->id)
	 * перед сохранением) идут через findOne напрямую и этим кэшем не задеваются;
	 * afterSave/afterDelete точечно инвалидируют запись. */
	private static $identityMap=[];

	/**
	 * findOne с identity map: повторный запрос того же id в рамках запроса
	 * возвращает уже загруженный (разделяемый) экземпляр
	 * @param int $id
	 * @return static|null
	 */
	public static function findLoaded($id) {
		if (isset(self::$identityMap[static::class][$id]))
			return self::$identityMap[static::class][$id];
		if (is_null($model=static::findOne($id))) return null;
		return self::$identityMap[static::class][$id]=$model;
	}

	/**
	 * Посчитать одним запросом количество строк таблицы, сгруппированных по колонке
	 * (для кэшей количества обратных ссылок: сколько объектов ссылается на каждый ID)
	 * @param string $table таблица (обычная или junction)
	 * @param string $column колонка группировки (обычно ссылка на этот класс)
	 * @return array [значение колонки => количество строк]
	 */
	protected static function fetchGroupedCount(string $table, string $column) {
		return ArrayHelper::map(
			(new \yii\db\Query())
				->select([$column,'cnt'=>'COUNT(*)'])
				->from($table)
				->groupBy($column)
				->all(),
			$column,'cnt'
		);
	}

	/** @var array Кэш количеств [класс][ключ] => [id => количество] (живет в рамках запроса) */
	private static $countsCache=[];

	/** Признак что кэш количеств по ключу загружен */
	public static function countsCached(string $key) {
		return isset(self::$countsCache[static::class][$key]);
	}

	/** Положить готовую карту количеств в кэш */
	protected static function storeCounts(string $key, array $map) {
		self::$countsCache[static::class][$key]=$map;
	}

	/**
	 * Загрузить (однократно) кэш количеств строк $table, сгруппированных по $column
	 */
	public static function cacheCounts(string $key, string $table, string $column) {
		if (static::countsCached($key)) return;
		static::storeCounts($key,static::fetchGroupedCount($table,$column));
	}

	/**
	 * Количество из кэша по ключу и ID; null если кэш по этому ключу не загружен
	 * @return int|null
	 */
	public static function cachedCount(string $key, $id) {
		return static::countsCached($key) ? (self::$countsCache[static::class][$key][$id]??0) : null;
	}

	/**
	 * Количество связанных объектов relation-загрузчика $loader,
	 * посчитанное ОДНИМ GROUP BY запросом на класс+загрузчик за весь веб-запрос
	 * (вместо загрузки всех связанных объектов на каждую модель).
	 * null - если так посчитать нельзя (нестандартный загрузчик, составная ссылка) и
	 * вызывающий должен посчитать по-старому через загрузку relation.
	 * @param string $loader имя relation-геттера (comps, softLists, ...)
	 * @return int|null
	 */
	public function loaderCount(string $loader) {
		//несохраненная модель: ее связи могут быть заданы виртуально (сеттеры LinkerBehavior)
		//и в БД их еще нет - только фолбэк вызывающего умеет их прочитать
		if ($this->isNewRecord) return null;
		$key='loader:'.$loader;
		if (!static::countsCached($key)) {
			if (is_null($map=$this->buildLoaderCountMap($loader))) return null;
			static::storeCounts($key,$map);
		}
		return static::cachedCount($key,$this->id);
	}

	/** @var array Кэш списков ID связанных объектов [класс][загрузчик] => [id => [id-шники]] */
	private static $idsCache=[];

	/**
	 * ID связанных объектов relation-загрузчика $loader,
	 * собранные ОДНИМ запросом на класс+загрузчик за весь веб-запрос.
	 * Для мест, которым нужны только id-шники связей (сигнатуры, проверки вхождения):
	 * атрибуты `*_ids` (LinkerBehavior) для этого загружают модели связи - на каждую
	 * модель отдельными запросами.
	 * null - если собрать нельзя (нестандартный загрузчик) - фолбэк на `*_ids`.
	 * @param string $loader имя relation-геттера
	 * @return int[]|null
	 */
	public function loaderIds(string $loader) {
		//несохраненная модель: ее связи могут быть заданы виртуально (сеттеры LinkerBehavior)
		//и в БД их еще нет - только фолбэк вызывающего (`*_ids`) умеет их прочитать
		if ($this->isNewRecord) return null;
		if (!isset(self::$idsCache[static::class][$loader])) {
			if (is_null($map=$this->buildLoaderIdsMap($loader))) return null;
			self::$idsCache[static::class][$loader]=$map;
		}
		return self::$idsCache[static::class][$loader][$this->id]??[];
	}

	/**
	 * Карта [id владельца => [id связанных]] для relation-загрузчика
	 * (junction JOIN target для отсева осиротевших строк, условия связи сохраняются)
	 * @param string $loader
	 * @return array|null null если не собрать - нужен фолбэк
	 */
	protected function buildLoaderIdsMap(string $loader) {
		$rel=$this->getRelation($loader,false);
		if (!$rel instanceof ActiveQuery || !$rel->multiple) return null;
		try {
			if ($rel->via) {
				$via=is_array($rel->via)?$rel->via[1]:$rel->via;
				if (!empty($via->via)) return null;
				if (!is_array($via->link) || count($via->link)!=1) return null;
				if (!is_array($rel->link) || count($rel->link)!=1) return null;
				$groupCol=array_keys($via->link)[0];
				$targetPk=array_keys($rel->link)[0];
				$targetFk=reset($rel->link);
				$junction=is_array($via->from)?reset($via->from):$via->from;
				if (!$junction) {
					/** @var ActiveRecord $viaClass */
					$viaClass=$via->modelClass??null;
					if (!$viaClass) return null;
					$junction=$viaClass::tableName();
				}
				/** @var ActiveRecord $targetClass */
				$targetClass=$rel->modelClass;
				$target=$targetClass::tableName();
				if (trim($junction,'{}%')===trim($target,'{}%')) return null;	//self-join не алиасим
				$q=(new \yii\db\Query())
					->select(["$junction.$groupCol","$target.$targetPk"])
					->from($junction)
					->innerJoin($target,"$target.$targetPk=$junction.$targetFk");
				if (!empty($via->where)) $q->andWhere($via->where);
				if (!empty($via->on)) $q->andWhere($via->on);
				if (!empty($rel->where)) $q->andWhere($rel->where);
				if (!empty($rel->on)) $q->andWhere($rel->on);
				$map=[];
				foreach ($q->all() as $row) $map[$row[$groupCol]][]=$row[$targetPk];
				return $map;
			}

			//one-2-many: сам relation-запрос с его условиями
			if (!is_array($rel->link) || count($rel->link)!=1) return null;
			$col=array_keys($rel->link)[0];
			/** @var ActiveRecord $targetClass */
			$targetClass=$rel->modelClass;
			$pk=$targetClass::primaryKey()[0]??'id';
			$q=clone $rel;
			$q->primaryModel=null;
			if (!empty($q->on)) { $q->andWhere($q->on); $q->on=null; }
			$q->select([$col,$pk])->groupBy(null)
				->orderBy(null)->limit(null)->offset(null);
			$q->with=null;
			$q->indexBy=null;
			$map=[];
			foreach ($q->asArray()->all() as $row) $map[$row[$col]][]=$row[$pk];
			return $map;
		} catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * Построить карту [id => количество] для relation-загрузчика:
	 * many-2-many считается по junction-таблице (с JOIN на целевую - в junction бывают
	 * осиротевшие строки, а загрузка relation их отфильтровывает),
	 * one-2-many - по запросу самой связи (с сохранением ее условий),
	 * группировкой по колонке-ссылке.
	 * @param string $loader
	 * @return array|null null если сгруппировать нельзя - нужен фолбэк
	 */
	protected function buildLoaderCountMap(string $loader) {
		$rel=$this->getRelation($loader,false);
		if (!$rel instanceof ActiveQuery || !$rel->multiple) return null;

		try {
			if ($rel->via) {
				//many-2-many: junction JOIN target, группировка по ссылке на нас
				$via=is_array($rel->via)?$rel->via[1]:$rel->via;
				if (!empty($via->via)) return null;				//многозвенные via - фолбэк
				if (!is_array($via->link) || count($via->link)!=1) return null;	//составная ссылка
				if (!is_array($rel->link) || count($rel->link)!=1) return null;
				$groupCol=array_keys($via->link)[0];			//колонка junction со ссылкой на нас
				$targetPk=array_keys($rel->link)[0];			//первичный ключ целевой таблицы
				$targetFk=reset($rel->link);					//колонка junction со ссылкой на целевую
				$junction=is_array($via->from)?reset($via->from):$via->from;
				if (!$junction) {
					/** @var ActiveRecord $viaClass */
					$viaClass=$via->modelClass??null;			//via('relationName') - junction это модель
					if (!$viaClass) return null;
					$junction=$viaClass::tableName();
				}
				/** @var ActiveRecord $targetClass */
				$targetClass=$rel->modelClass;
				$target=$targetClass::tableName();
				//таблицы не алиасим, чтобы условия связей с явными именами таблиц продолжали работать;
				//self-join через junction на саму себя потому не сгруппировать - фолбэк
				if (trim($junction,'{}%')===trim($target,'{}%')) return null;
				$q=(new \yii\db\Query())
					->select(["$junction.$groupCol",'cnt'=>"COUNT(DISTINCT $target.$targetPk)"])
					->from($junction)
					->innerJoin($target,"$target.$targetPk=$junction.$targetFk")
					->groupBy("$junction.$groupCol");
				//условия junction-запроса и целевой связи (фильтры типа model_class или LIKE по имени)
				if (!empty($via->where)) $q->andWhere($via->where);
				if (!empty($via->on)) $q->andWhere($via->on);
				if (!empty($rel->where)) $q->andWhere($rel->where);
				if (!empty($rel->on)) $q->andWhere($rel->on);
				return ArrayHelper::map($q->all(),$groupCol,'cnt');
			}

			//one-2-many: считаем запросом самой связи с ее условиями
			if (!is_array($rel->link) || count($rel->link)!=1) return null;	//составная ссылка
			$col=array_keys($rel->link)[0];
			$q=clone $rel;
			$q->primaryModel=null;	//отвязываем от конкретной модели - иначе запрос отфильтруется по ее id
			if (!empty($q->on)) {	//on-условие при lazy-загрузке работает как where - сохраняем
				$q->andWhere($q->on);
				$q->on=null;
			}
			$q->select([$col,'cnt'=>'COUNT(*)'])->groupBy($col)
				->orderBy(null)->limit(null)->offset(null);
			$q->with=null;
			$q->indexBy=null;
			return ArrayHelper::map($q->asArray()->all(),$col,'cnt');
		} catch (Throwable $e) {
			return null;	//нестандартный запрос - фолбэк на загрузку relation
		}
	}

	/**
	 * Валидация отсутствия рекурсии при построении ссылок на родителей
	 * @param string $attribute - аттрибут с id другого объекта
	 * @param array $params
	 * в параметрах надо указать 'params'=>[
	 *     	'getLink'=>'parentService',    	//обязательно! - метод, которым получить не id, а объект
	 *     	'initialLink'=>ArmsModel[],    	//можно передать первую итерацию ссылок, т.к. LinkerBehaviour читает ссылки
	 * 										//из базы, а не из переменной, и несохраненные в БД ссылки через getter не получить
	 *		'origin'						//кому будем писать ошибки валидации (инициатор валидации)
	 * 		'object'						//чьи аттрибуты проверяем
	 * 		'attributeChain'				//накопленные значения поля за время движения по рекурсии
	 * ]
	 * @return bool
	 */
	public function validateRecursiveLink($attribute, $params=[])
	{
		$params=(array)$params;

		// кладем инициатора рекурсии в параметры
		if (!isset($params['origin'])) $params['origin']=$this;
		//если у нас нет цепочки связей - создаем пустую
		if (!isset($params['attributeChain']))	$params['attributeChain']=[];
		//кладем себя в цепочку
		$params['attributeChain'][]=$this->id;

		//если никакой другой объект не передан, то проверяем себя
		$object=isset($params['object'])?$params['object']:$this;

		//метод для получения связанного объекта
		$getLink=$params['getLink']??'parent';

		//если у нас есть ссылка
		if (!empty($object->$attribute)) {
			//предположим что у нас тут может быть и _id и _ids, т.к. _ids более общий - используем его
			if (!is_array($link_ids=$object->$attribute)) $link_ids=[$link_ids];
			//если она уже есть в цепочке id
			foreach ($link_ids as $link_id) {
				if (in_array($link_id, $params['attributeChain'])) {
					$error=($this->hasProperty('name')?$this->name:$this->getAttributeLabel($attribute))
						.' рекурсивно ссылается сам на себя';
					$params['origin']->addError($attribute, $error);
					return false; //нет смысла дальше проверять
				}
			}
			//иначе пробуем загрузить объекты на которые ссылаемся
			if (isset($params['initialLink'])) {//если ссылки передали через параметры
				$links=$params['initialLink'];	//то из и используем (нужно для links_ids до их записи в БД)
				unset($params['initialLink']);	//дальше будем использовать ссылки уже из БД
			} else {
				$links=$object->$getLink;
			}
			//links также может быть и getService и getServices, приводим в общем случае к массиву
			if (!is_array($links)) $links=[$links];
			foreach ($links as $link) {
				if (is_object($link)) {
					//кладем его в параметры для следующей проверки
					$params['object']=$link;
					//проверяем
					if (!$link->validateRecursiveLink($attribute, $params)) return false; //если нашли косяк, дальше не проверяем
				}
			}
		}
		return true;
	}


	/**
	 * Сохранить без обновления журнала
	 * @param bool $runValidation
	 * @return bool
	 * @throws Exception
	 */
	public function silentSave($runValidation = true) {
		$this->doNotChangeAuthor=true;
		return $this->save($runValidation);
	}

	/**
	 * Возвращает класс журнала этой модели. Либо он должен быть явно задан,
	 * либо должен существовать класс с суффиксом History
	 * @return false|string
	 */
	public function getHistoryClass() {
		//упростим себе задачу тем что класс не надо задавать всегда вручную, пусть будет {$MasterClass}History
		if (!isset($this->historyClass)) {
			//у моделей которые сами журналы истории такое не нужно
			if ($this instanceof HistoryModel) return false;
			$this->historyClass=static::class.'History';
		}
		if (!class_exists($this->historyClass)) return false;
		return $this->historyClass;
	}

	/**
	 * Записывает в журнал изменения (если они обнаружатся относительно предыдущей записи в журнале)
	 * @param null $initiator
	 */
	public function historyCommit($initiator=null) {
		$historyClass=$this->getHistoryClass();
		if (!$historyClass || $this->doNotChangeAuthor) return;

		//ну что ж, давайте попробуем залепить запись в журнал!
		/** @var HistoryModel $journal */
		$journal=new $historyClass();
		$journal->journal($this,$initiator);
	}

	/**
	 * Записывает в журнал отметку об удалении объекта
	 * @param null $initiator
	 */
	public function historyEnd($initiator=null) {
		if (!($historyClass=$this->getHistoryClass())) return;

		/** @var HistoryModel $journal */
		$journal=new $historyClass();
		$journal->journalDeletion($this,$initiator);
	}

	/**
	 * Кастомизируем afterSave, чтобы добавить запись о новом состоянии модели в журнал
	 * @param $insert
	 * @param $changedAttributes
	 * @return void
	 */
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);

		$this->historyCommit(); //журналирование изменений

		//кэши экземпляров/количеств должны пережить запись согласованно:
		//кэш всех элементов сбрасываем целиком (могли добавиться/измениться записи),
		//в identity map точечно убираем эту запись, кэши количеств сбрасываем все
		static::invalidateAllItemsCache();
		unset(self::$identityMap[static::class][$this->id]);
		static::flushCountsCaches();
	}

	/**
	 * Кастомизируем afterDelete, чтобы добавить в журнал запись об удалении модели
	 * @return void
	 */
	public function afterDelete()
	{
		parent::afterDelete();

		$this->historyEnd(); //журналирование удаления

		static::invalidateAllItemsCache();
		unset(self::$identityMap[static::class][$this->id]);
		static::flushCountsCaches();
	}

	/**
	 * Кастомизируем beforeSave, чтобы обработать стандартное поведение полей
	 * - updated_at
	 * - updated_by
	 * - external_links
	 * если они есть в этой модели
	 * @param $insert
	 * @return bool
	 */
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) return false;

		// created_at заполняется только на INSERT и только если не задан явно.
		// Требуется для REST-создания моделей (POST /api/...), где клиент
		// обычно не присылает created_at, а в БД колонка NOT NULL.
		if ($insert && $this->hasProperty('created_at') && empty($this->created_at)) {
			$this->created_at=gmdate('Y-m-d H:i:s');
		}

		if ($this->hasProperty('updated_at') && !$this->doNotChangeAuthor) {
			$this->updated_at=gmdate('Y-m-d H:i:s');
		}

		if ($this->hasProperty('updated_by') && !$this->doNotChangeAuthor) {
			if (Yii::$app->hasProperty('user') && is_object(Yii::$app->user) && is_object(Yii::$app->user->identity))
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$this->updated_by=Yii::$app->user->identity->Login;
		}

		if ($this->hasProperty('external_links')) {
			$this->externalDataBeforeSave();
		}


		return true;
	}


	/**
	 * Загрузить поля с объекта загруженного с другой системы (такой же инвентори)
	 * @param array      $remote сам удаленный объект
	 * @param array      $overrides поля которым надо задать кастомные значения (для ссылок, их нельзя взять из удаленного)
	 * @param string     $log
	 * @param RestHelper $rest
	 * @return bool|null
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function syncFields(array $remote, array $overrides, string &$log, RestHelper $rest) {
		$timestamp=static::$syncTimestamp;

		foreach ($overrides as $field=>$value) {
			if ($this->$field==$value) unset ($overrides[$field]);
		}

		if (SyncController::$debug) {
			$class=SyncController::getClassName(static::class);
			echo "Comparing $class $timestamp: Local={$this->$timestamp} vs Remote={$remote[$timestamp]}\n";
		}


		//если (удаленный объект имеет отметку времени и она больше) или надо поменять (а не синхронизировать) какие-то поля
		if (($timestamp && $remote[$timestamp] && $remote[$timestamp]>$this->$timestamp) || count($overrides)) {
			$needUpdate=false;
			foreach (static::$syncableFields as $field) {
				if ($this->$field != $remote[$field]) {
					$log.= "$field: [{$this->$field} != {$remote[$field]}]; ";
					$this->$field = $remote[$field];
					$needUpdate=true;
				}
			}
			foreach ($overrides as $field=>$value) {
				if (strpos($field,'_ids')==(strlen($field)-4)) {
					if (array_search($value,$this->$field)===false) {
						echo "$field: [+ $value]; ";
						$this->$field = array_merge($this->$field,[$value]);
						$needUpdate=true;
					}
				} elseif ($this->$field != $value) {
					$this->$field = $value;
					$log .= "$field: [{$this->$field} => $value]; ";
					$needUpdate=true;
				}
			}
			if (!$needUpdate) return null;
			return $this->silentSave(false);
		}
		//если менять не надо
		return null;
	}

	/**
	 * Создает объект в локальной системе на основании данных из удаленной
	 * @param array      $remote сам удаленный объект
	 * @param array      $overrides поля которым надо задать кастомные значения (для ссылок, их нельзя взять из удаленного)
	 * @param string     $log
	 * @param RestHelper $rest
	 * @return ArmsModel
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function syncCreate(array $remote, array $overrides, string &$log, RestHelper $rest) {

		$import=[];

		foreach (static::$syncableFields as $field) {
			$import[$field]=$remote[$field];
		}

		foreach ($overrides as $field=>$value) {
			if (strpos($field,'_ids')==(strlen($field)-4)) {
				$import[$field] = [$value];
			} else {
				$import[$field]=$value;
			}
		}

		foreach ($import as $p=>$v) {
			if (is_array($v)) $v=implode(',',$v);
			$log.= "[$p=>$v]; ";
		}

		return new static($import);
	}

	/**
	 * Как найти локальные объекты по ключу синхронизации
	 * (который на самом деле никакой не ключ с точки зрения БД)
	 * @param $name
	 * @return ArmsModel[]
	 */
	public static function syncFindLocal($name) {
		$query=static::find()->where([static::$syncKey=>$name]);
		if (SyncController::$debug) {
			$class=SyncController::getClassName(static::class);
			echo "Searching local $class: ".$query->createCommand()->rawSql."\n";
		}
		return $query->all();
	}

	/**
	 * Возвращает следующее значение integer атрибута из таблицы
	 * ищет максимальное значение атрибута в таблице и возвращает на 1 больше
	 * @param string $field
	 * @return integer
	 */
	public static function fetchNextValue($field) {
		$max=static::find()->select("MAX(CAST(`$field` as SIGNED))")->scalar();
		return ++$max;
	}

	/**
	 * Возвращает следующий id модели
	 * @return int
	 */
	public static function fetchNextId() {
		return static::fetchNextValue('id');
	}

	/**
	 * Поиск объекта по имени
	 * @param string $name
	 * @return ArmsModel|ActiveRecord|null
	 */
	public static function findByName(string $name)	{
		return static::find()
			->where(['LOWER(name)'=>strtolower($name)])
			->one();
	}

	/**
	 * @param string $name
	 * @return ArmsModel|ActiveRecord|null
	 */
	public static function findByAnyName(string $name)
	{
		return static::findByName($name);
	}

	/**
	 * Рекурсивный поиск аттрибута в цепочке родителей
	 * @param string 		$simpleAttr		как называется локальный аттрибут без учета рекурсии
	 * @param string|null	$recursiveAttr	как называется этот же аттрибут с учетом рекурсии
	 * 										(если не указать то рекурсивно будет вызвана эта же функция)
	 * @param string|null	$parent			как называется ссылка на батю
	 * @param null   		$empty			что вернуть если ничего не нашли
	 * @return mixed|null
	 */
	public function findRecursiveAttr(string $simpleAttr, string $recursiveAttr=null, $parent=null, $empty=null) {
		//ищем в кэше
		if (isset($this->recursiveCache[$simpleAttr]))
			return $this->recursiveCache[$simpleAttr];

		if (!is_null($recursiveAttr)&&isset($this->recursiveCache[$recursiveAttr]))
			return $this->recursiveCache[$recursiveAttr];

		//ищем у себя
		$value=$this->$simpleAttr;
		if (is_object($value)||(is_array($value)&&count($value))||!empty($value))
			return $this->recursiveCache[$recursiveAttr] = $value;

		//атрибут ссылка на предка
		if (is_null($parent)) $parent=$this->parentAttr;

		//ищем у родителя
		if (is_object($this->$parent)) {
			if (is_null($recursiveAttr)) {
				return $this->recursiveCache[$simpleAttr] = $this->$parent->findRecursiveAttr($simpleAttr,$recursiveAttr,$parent,$empty);
			} else {
				return $this->recursiveCache[$recursiveAttr] = $this->$parent->$recursiveAttr;
			}
		}

		//запоминаем, что ничего не нашли
		return $this->recursiveCache[$recursiveAttr] = $empty;
	}

	/**
	 * Возвращает узел в дереве предков в котором задан наследуемый атрибут
	 * @param string $attr
	 * @param string|null $parentAttr
	 * @return int|null
	 */
	public function findRecursiveAttrNode(string $attr, $parentAttr=null) {
		//проверяем для рекурсивных аттрибутов somethingRecursive -> something
		if (StringHelper::endsWith($attr,'Recursive')) {
			$attr=substr($attr,0,strlen($attr)-strlen('Recursive'));
		}

		if (isset($this->recursiveCache[$attr.'::node']))
			return $this->recursiveCache[$attr.'::node'];

		//атрибут ссылка на предка
		if (is_null($parentAttr)) $parentAttr=$this->parentAttr;

		$test=$this;
		while (is_object($test)) {
			$value=$test->$attr;
			if (is_object($value)||(is_array($value)&&count($value))||!empty($value)) {
				return $this->recursiveCache[$attr.'::node']=$test;
			}
			//переключаемся на родителя
			$test=$test->$parentAttr;
		}
		return $this->recursiveCache[$attr.'::node']=null;
	}

	/**
	 * Возвращает текстовое поле в котором может быть указано {{PARENT}} и оно будет заменено на значение
	 * этого поля в родителе модели (имеет смысл только в моделях с иерархией)
	 * @param string    $field	поле (например comment) в котором может быть {{PARENT}}
	 * @param string|null    $recursiveField рекурсивное поле (например commentRecursive) в котором {{PARENT}} будет уже заменено
	 * @return string|null
	 */
	public function textRecursiveField(string $field, $recursiveField=null)
	{
		$PARENT='{{PARENT}}';
		$text=$this->$field;
		if (strpos($text,$PARENT)===false) return $text;

		$parentAttr=$this->parentAttr;
		if (!$this->canGetProperty($parentAttr)) return $text;
		/** @var ArmsModel $parent */
		$parent=$this->$parentAttr;
		$parentText='';

		if (is_object($parent)) {
			$parentText=$recursiveField?
				$parent->$recursiveField:
				$parent->textRecursiveField($field);
		};

		return str_replace($PARENT, $parentText, $text);
	}

	public function externalData() {}

	/**
	 * Вытащить запись журнала на дату
	 * @param $id
	 * @param $timestamp
	 * @return ActiveRecord
	 */
	public static function fetchJournalRecord($id,$timestamp) {
		//достаем класс журнала
		$instance=new static();
		/** @var HistoryModel $historyClass */
		$historyClass=$instance->getHistoryClass();

		//если класс журнала есть, ищем запись в журнале
		if ($historyClass) {
			$record=$historyClass::findOnTimestamp($id,$timestamp);
			//нашли - молодцы!
			if (is_object($record)) return $record;
		}
		//ищем текущую запись в оперативной таблице (не в журнале)
		return static::findOne($id);
	}

	/**
	 * Построить путь от потомка к предку
	 * @param View   $view
	 * @param string $label	какой аттрибут использовать в качестве имени модели
	 */
	public function recursiveBreadcrumbs(View $view, $label='name') {
		$item=$this;
		$chain=[$this];
		$parent=$this->parentAttr;
		$viewPath='/'.StringHelper::class2Id(get_class($this)).'/view';
		while (is_object($item=$item->$parent)) {
			$chain[]=$item;
		}
		foreach (array_reverse($chain) as $item) {
			$view->params['breadcrumbs'][]=[
				'label'=>$item->$label,
				'url'=>[$viewPath,'id'=>$item->id],
			];
		}
	}

	/**
	 * Признак того, что эта модель может иметь статус "архивировано"
	 * @return bool
	 */
	public function getCanBeArchived() {
		if (isset($this->attrsCache['canBeArchived'])) return $this->attrsCache['canBeArchived'];
		return $this->attrsCache['canBeArchived']=$this->hasProperty('archived');
	}

	/**
	 * Признак того, что эта модель имеет статус "архивировано"
	 * @return bool
	 */
	public function getIsArchived() {
		return $this->canBeArchived && $this->archived;
	}

	/**
	 * Путь до контроллера
	 * @return mixed|string
	 */
	public function getControllerPath() {
		if (isset($this->attrsCache['controllerPath'])) return $this->attrsCache['controllerPath'];
		return $this->attrsCache['controllerPath']=StringHelper::class2Id(get_class($this));
	}

	/**
	 * Путь до папки views
	 * @return mixed|string
	 */
	public function getViewsPath() {
		if (isset($this->attrsCache['viewsPath'])) return $this->attrsCache['viewsPath'];
		$class=($this instanceof HistoryModel)?$this->masterClass:get_class($this);
		return $this->attrsCache['viewsPath']=StringHelper::class2ViewsPath($class);
	}

	/**
	 * Отрендерить элемент
	 * @param View  $view
	 * @param array $options
	 * @return string
	 */
	public function renderItem(View $view,$options=[]) {
		return ModelWidget::widget(['model'=>$this,'options'=>$options]);
	}

	/**
	 * Отобрать себе все абсорбируемые поля у другой модели этого же класса
	 * @param ArmsModel $model
	 * @param false     $delete
	 * @throws Throwable
	 * @throws Exception
	 * @throws StaleObjectException
	 */
	public function absorbModel(ArmsModel $model, $delete=false) {
		//перебираем аттрибуты-ссылки
		foreach ($this->linksSchema as $attribute=>$schema) {
			//если их нужно отбирать
			if ($this->attributeIsAbsorbable($attribute)) {
				//отбираем
				if ($this->attributeIsReverseLink($attribute))
					$model->attributeReverseLinkRedirect($attribute,$this->id);
				else
					$this->$attribute=$model->$attribute;
			}
		}

		//перебираем аттрибуты-значения
		foreach ($this->attributes as $attribute=>$value) {
			//если их нужно отбирать
			if ($this->attributeIsAbsorbable($attribute)) {
				//отбираем:
				//берем себе значение
				$this->$attribute=$model->$attribute;
				//чистим его у второй модели
				$model->attributeClear($attribute);
			}
		}

		if ($delete) {	//если надо удалить ограбленного - удаляем
			$model->delete();
		} else {		//иначе сохраняем его в обомжелом виде
			$model->save();
		}
	}

	/**
	 * Чтобы один массив можно было наполнить уникальными моделями разных классов индексируем его по uuid
	 * @param $model
	 * @return string
	 */
	public static function getUUID($model) {
		return get_class($model).'#'.$model->id;
	}

	/**
	 * @return string
	 */
	public function uuid() {
		return static::getUUID($this);
	}

	/**
	 * Построить ветку(граф) от модели до корня дерева
	 * (для моделей с древовидной связью через атрибут "родитель")
	 * @param object $model модель
	 * @param string $parentAttr аттрибут через который можно получить родителя
	 * @return array
	 */
	public static function buildTreeBranch(object $model, string $parentAttr) {
		$chain=[$model];
		while (is_object($model=$model->$parentAttr)) {
			$chain[]=$model;
		}
		return $chain;
	}

	/**
	 * Добавляем наш сценарий валидации с тем же набором атрибутов, что и для основного
	 * @return array|array[]
	 */
	public function scenarios()
	{
		$scenarios=parent::scenarios();
		$scenarios[static::SCENARIO_VALIDATION]=$scenarios[static::SCENARIO_DEFAULT];
		return $scenarios;
	}

	/**
	 * Загружает пользовательский комментарий к изменению (issue #205) из данных формы.
	 * historyComment намеренно НЕ делается safe-атрибутом (иначе он попал бы под
	 * проверку обязательной типизации и в JSON/REST), поэтому забираем его из POST
	 * отдельно — прямо перед сохранением в actionCreate()/actionUpdate().
	 * @param array $data данные запроса (напр. Yii::$app->request->post())
	 */
	public function loadHistoryComment($data)
	{
		$formName=$this->formName();
		if (isset($data[$formName]) && array_key_exists('historyComment',$data[$formName])) {
			$this->historyComment=$data[$formName]['historyComment'];
		}
	}

	public function __get($name)
	{
		//если мы в режиме валидации
		if ($this->getScenario()===static::SCENARIO_VALIDATION) {
			//это значит что в модель "предзагружены" все аттрибуты которые нужны для валидации,
			//но many-2-many геттеры не умеют работать с такими предзагруженными значениями
			//они работают прямо с таблицами из БД, и им пофиг на валидируемые значения
			//проверяем, не пытаемся ли мы открыть загрузчик many-2-many
			if ($link=$this->attributeIsLoader($name)) {
				if (StringHelper::endsWith($link,'_ids'))
					return $this->attributeFetchLinks($link,$this->$link);
			}
		}


		try {
			return parent::__get($name);
		} catch (UnknownPropertyException $e) {
			//если мы не нашли свойство
			//если это рекурсивный аттрибут, и у модели есть ссылка на родителя
			//КОНВЕНЦИЯ НАПРАВЛЕНИЯ: магический <attr>Recursive всегда ищет ВВЕРХ
			//к родителям (наследуемые is_inheritable поля); сбор значений ВНИЗ
			//с потомков магией не разрешается - таким атрибутам нужен явный геттер
			//и 'is_collectable'=>true в attributeData (сторож RecursiveAttrDirectionTest)
			if (StringHelper::endsWith($name,'Recursive') && $this->canGetProperty($this->parentAttr)) {
				//разбираемся к какому атрибуту у нас добавлена рекурсия
				$plain=StringHelper::removeSuffix($name,'Recursive');
				//и есть ли у нас такой атрибут
				if ($this->canGetProperty($plain)) {
					//выясняем какой у него тип
					$type=null;
					try { $type=$this->getAttributeTypeClass($plain)::name(); } catch (\Throwable $e) {}

					//для текста мы просто заменяем {{PARENT}} на родительское значение этого же поля
					if ($type==='text')
						return $this->textRecursiveField($plain,$name);

					//если аттрибут наследуемый, то вытаскиваем его значение рекурсивно
					if ($this->attributeIsInheritable($plain))
						return $this->findRecursiveAttr($plain,$name);
				}
			}
			throw $e;
		}
	}

	/**
	 * Загрузить связанные модели для вывода гридом: с жадной загрузкой связей,
	 * нужных отображаемым колонкам (join-аннотации attributeData, как в prepareSearch).
	 * Для гридов, которые кормятся relation-ом (ArrayDataProvider(['allModels'=>...])),
	 * а не поисковой моделью - иначе каждая строка грузит свои связи отдельными запросами.
	 * Результат кладется в relation модели (populateRelation), так что последующие
	 * обращения к $this->$relation (бейджи вкладок и т.п.) переиспользуют его.
	 * @param string $relation имя relation-загрузчика ('techs','comps',...)
	 * @param string|null $gridId id DynaGrid-а - для жадной загрузки только видимых колонок
	 *   (null - все связи с join-аннотацией)
	 * @return static[]
	 */
	public function relationForGrid(string $relation, string $gridId=null) {
		$rel=$this->getRelation($relation);
		$class=$rel->modelClass;
		/** @var ArmsModel $prototype */
		$prototype=new $class();
		$columns=$gridId?\app\components\DynaGridWidget::fetchVisibleAttributes($prototype,$gridId):null;
		if (count($joins=$prototype->attributesJoins($columns))) $rel->with($joins);
		$models=$rel->all();
		$this->populateRelation($relation,$models);
		return $models;
	}

	/**
	 * Подготавливает запросы для поиска и загрузки списка моделей
	 * @param $columns
	 * @return array
	 */
	public function prepareSearch($columns)
	{
		//для загрузки данных (с пагинацией)
		$query = static::find();
		//для поиска/фильтра (без пагинации, но с возможностью фильтровать по связанным объектам)
		$filter = static::find()->select('DISTINCT('.static::tableName().'.id)');
		if (count($joins=$this->attributesJoins($columns))) {
			$query->with($joins);
			$filter->joinWith($joins);
		};
		return [$query,$filter,$joins];
	}
}
