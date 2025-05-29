<?php

namespace app\models\ui;

use app\helpers\StringHelper;
use app\models\ArmsModel;
use yii\db\Exception;

/**
 * @property string $page
 * @property string $dependencies
 * @property boolean $valid
 * @property string $filePath
 * @property boolean $hasFile
 * @property string $data
 */
class WikiCache extends ArmsModel
{
	// префикс для пути к локальным полям моделей
	// (те которые не хранятся в wiki, а только рендерятся)
	const STATIC_ROOT='_internal.sys_:';
	
	//куда сохраняем в модели данные перед сбросом на диск (который делается уже на save())
	private $_data;
	
	public static function tableName()
	{
		return 'wiki_cache';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['page','dependencies'], 'string', 'max' => 255],
			['valid', 'boolean'],
			['page','unique'],
			[['page'], 'required'],
			[['valid'], 'default', 'value' => 1],
		];
	}
	
	/**
	 * Возвращает "внутренний" путь, по которому мы сохраняем кэш поля объекта
	 * @param $class
	 * @param $id
	 * @param $field
	 * @return string
	 */
	public static function internalPath($class, $id, $field) {
		return static::STATIC_ROOT
			.StringHelper::class2Id($class)
			.':'.$id
			.':'
			.$field;
	}
	
	/**
	 * Если текст содержит ссылку на родителя, то кэш сбрасывается
	 * @param ArmsModel $model
	 * @param string    $field
	 * @return void
	 * @throws Exception
	 */
	public static function invalidateParentReference($model, $field)
	{
		$cache=static::fetchCache(
			static::internalPath(get_class($model), $model->id, $field)
		);
		
		//если кэша нет или он не валидный, то ничего не делаем
		//(если кэша нет, то создается новая запись, по умолчанию не валидная)
		if (!$cache->valid) return;
		//если текста в поле нет, то ничего не делаем
		if (!$text=$model->$field) return;
		//если в тексте ссылки на предка нет, то ничего не делаем
		if (strpos($text, '{{PARENT}}')===false) return;
		//инвалидируем кэш
		$cache->valid=false;
		$cache->save();
	}
	
	/**
	 * Конвертирует путь doku -> путь в ФС
	 * @param $path
	 * @return string
	 */
	public static function filePath($path)
	{
		return $_SERVER['DOCUMENT_ROOT']
			.'/runtime/wiki_cache/'
			.str_replace(':', '/', $path)
			.'.html';
	}
	
	public static function fetchCache($path) {
		$cache=static::find()
			->where(['page'=>$path])
			->one();
		if (!$cache) {
			$cache=new WikiCache(['page'=>$path,'valid'=>0]);
		}
		return $cache;
	}
	
	/**
	 * Возвращает путь к файлу кэша странички
	 * @return string
	 */
	public function getFilePath() {
		return static::filePath($this->page);
	}
	
	/**
	 * Проверяет наличие файла кэша
	 * @return bool
	 */
	public function getHasFile()
	{
		return is_file($this->filePath);
	}
	
	/**
	 * Возвращает содержимое кэша
	 * @return string
	 */
	public function getData()
	{
		if (!$this->hasFile) {
			return '';
		}
		
		return file_get_contents($this->filePath)??'';
	}
	
	
	/**
	 * Преобразует путь ссылки в абсолютные пути с учетом относительных путей
	 * @param string $link Путь ссылки (может быть относительным)
	 * @param string $sourcePage Полный путь страницы-источника (например "namespace:page")
	 * @return string Абсолютный путь зависимости
	 */
	public static function absLinkPath(string $link, string $sourcePage): string {
		
		// Разбиваем путь ссылки на компоненты
		$parts = StringHelper::explode(
			$link,
			[':','..','.'],
			true,			//убираем пробелы по краям
			true,		//убираем пустые элементы
			true		//оставляем разделители в исходном массиве
		);
		
		// Если зависимость пустая, возвращаем пустую строку
		if (!count($parts)) return '';

		if (count($parts)===1 || $parts[0] === '.' || $parts[0] === '..') {
			// Если зависимость состоит из одного слова, начинается с точки или двух точек,
			// то она относительная.
			// Разбиваем исходный путь и путь зависимости на компоненты
			$result = StringHelper::explode(trim($sourcePage),':',true,true);
			if (!empty($result)) // если путь не пустой, то последний его токен - это страница с которой у нас ссылка
				array_pop($result); //убираем исходную страницу и оставляем путь до namespace
		} else {
			//иначе у нас абсолютный путь который надо привести к простому (без "." и "..")
			$result = [];
		}
		
		foreach ($parts as $part) {
			switch ($part) {
				case ':': 			//разделитель namespace
				case '.': break;	//тот же путь
				case '..': if (!empty($result))
					array_pop($result);
					break;	//тот же путь
				default:
					$result[] = $part;
			}
		}
		
		return implode(':', $result);
	}
	
	/**
	 * Извлекает ID зависимостей из синтаксиса плагина include DokuWiki
	 * Поддерживает:
	 * {{page>[id]&[flags]}}
	 * {{section>[id]#[section]&[flags]}}
	 *
	 * @param string $text Исходный текст страницы
	 * @param string $path Путь страницы для формирования полных путей зависимостей
	 * @return array Массив найденных ID зависимостей
	 */
	public static function extractDependencies(string $text, string $path='')
	{
		$dependencies = [];
		if (StringHelper::startsWith($path, static::STATIC_ROOT)) {
			// Если путь начинается с префикса внутренних записей,
			// то считаем что все пути идут от корня
			$path = '';
		}
		
		// Регулярка для {{page>id&flags}} и {{section>id#section&flags}}
		$pattern = '/\{\{(?:page>|section>)([^}#&]+)(?:#.*?)?(?:&.*?)?}}/';
		
		if (preg_match_all($pattern, $text, $matches)) {
			foreach ($matches[1] as $dependency) {
				$absDep = static::absLinkPath($dependency,$path);
				if (!empty($absDep)) {
					$dependencies[] = $absDep;
				}
			}
		}
		
		return array_unique($dependencies);
	}
	
	public function setData($value)
	{
		$this->_data=$value;
	}
	
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) return false;
		
		if ($this->_data) {
			if (!is_dir(dirname($this->filePath)))
				mkdir(dirname($this->filePath), 0777, true);
			file_put_contents($this->filePath, $this->_data);
		}
		
		return true;
	}
}