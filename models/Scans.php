<?php

namespace app\models;

use app\console\commands\SyncController;
use app\helpers\RestHelper;
use Imagick;
use ImagickException;
use Yii;
use yii\helpers\StringHelper;


/**
 * This is the model class for table "scans".
 *
 * @property int $id id
 * @property int $contracts_id contracts link
 * @property int places_id
 * @property int tech_models_id
 * @property int material_models_id
 * @property int lic_types_id
 * @property int lic_items_id
 * @property int arms_id
 * @property int techs_id
 * @property string $viewThumb
 * @property string $idxThumb
 * @property string $thumbExt
 * @property string $thumbUrl
 * @property string $shortFname
 * @property string $noidxFname
 * @property string $fullFname
 * @property string $fsFname
 * @property boolean $fileExists
 * @property int $fileSize
 * @property int $fileDate
 * @property int $humanFileSize
 * @property string $file		Имя файла без пути
 * @property string $name		Имя без префикса
 * @property string $format
 * @property yii\web\UploadedFile $scanFile
 * @property string $descr
 * @property array $contracts
 */
class Scans extends ArmsModel
{
	/*
	 * Ошибка потерянного изображения
	 */
	public static string $NO_ORIG_ERR='err_no_orig';
	public static string $RENDERING_ERR='err_rendering';
	//public static $PDF_ORIG_ERR='pdf_no_orig';
	public $scanFile;

	public static $viewThumbSizes=[512,512];
	public static $idxThumbSizes=[160,160];

	public static $title="Сканы документов";
	
	public static $syncTimestamp='fileDate';
	
	
	public function extraFields()
	{
		return ['name','fileSize','fileDate','fileExists'];
	}

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'scans';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			//[['contracts_id','places_id','tech_models_id','material_models_id','lic_types_id','lic_items_id','arms_id','techs_id'], 'default', 'value'=>null],
			[[
				'contracts_id',
				'places_id',
				'tech_models_id',
				'material_models_id',
				'lic_types_id',
				'lic_items_id',
				'arms_id',
				'techs_id',
				'soft_id',
			], 'integer'],
	        [['scanFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg, pdf, gif', 'on' => 'create'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
	        'file' => 'Место хранения загруженного файла',
	        //'scanFile' => 'Скан документа',
            //'descr' => 'Описание скана',
        ];
    }
	
	/**
	 * Сохраняет загруженный файл
	 * @return bool
	 */
	public function upload()
	{
		if ($this->validate()) {
			$prefix=($this->id)?$this->id:static::fetchNextId();
			$this->file=$prefix.'-'. StringHelper::truncate($this->scanFile->baseName,80);
			$this->format=$this->scanFile->extension;
			$this->scanFile->saveAs($_SERVER['DOCUMENT_ROOT'].$this->fullFname);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Синхронизирует удаленный файл
	 * @param array      $remote
	 * @param RestHelper $rest
	 * @return int|null
	 */
	public function syncFile(array $remote, RestHelper $rest)
	{
		$name=$remote[static::$syncKey];

		//ориентируемся на дату файла
		$timestamp=static::$syncTimestamp;
		//echo "Comparing $name [{$this->id}] $timestamp: {$this->fileDate} vs {$remote[$timestamp]}...";
		if ($this->$timestamp >= $remote[$timestamp]) return null;
		//echo "Downloading $name ...";
		
		$prefix=($this->id)?$this->id:(static::fetchNextId());
		$this->file=$prefix.'-'.pathinfo($name)['filename'];
		$this->format=pathinfo($name)['extension'];
		$this->save(false);
		$data=$rest->getFile('scans','download',['id'=>$remote['id']]);
		return file_put_contents(Yii::$app->basePath.$this->fullFname,$data);
	}
	
	
	public function getThumbUrl(){
		if (!$this->fileExists) {
			return static::noThumb();
		} else {
			return $this->idxThumb;
		}
	}
	
	public function getName() {
		$tokens=explode('-',$this->file);
		unset ($tokens[0]);
		return implode('-',$tokens).'.'.$this->format;
	}

	/**
	 * Возвращает короткое имя файла без пути
	 * @return string
	 */
	public function getShortFname(){
		return $this->file.'.'.$this->format;
	}

	/**
	 * Возвращает короткое имя файла без пути
	 * @return string
	 */
	public function getNoidxFname(){
		return $this->descr.'.'.$this->format;
	}

	/**
	 * Имя файла без индес-префикса
	 * @return string
	 */
	public function getDescr(){
		$pos=strpos($this->file,'-');
		if ($pos) {
			return substr($this->file,$pos+1);
		} else return $this->file;

	}

	/**
	 * Возвращает путь к оригиналу файла
	 * @return string
	 */
	public function getFullFname(){
		return '/web/scans/'.$this->file.'.'.$this->format;
	}

	/**
	 * Возвращает путь к оригиналу файла в фвйловой системе
	 * @return string
	 */
	public function getFsFname(){
		return Yii::$app->basePath.$this->fullFname;
	}

	/**
	 * Проверяет, что оригинал присутствует
	 * @return boolean
	 */
	public function getFileExists(){
		//при синхронизации может сначала создать объект а файл не указать/закачать
		if (!strlen($this->file)) return false;
		return file_exists($this->fsFname);
	}
	
	/**
	 * Размер файла оригинала в байтах
	 * @return string
	 */
	public function getFileSize(){
		if (!$this->fileExists) return 0;
		return filesize($this->fsFname);
	}

	/**
	 * Размер файла оригинала в байтах
	 * @return string
	 */
	public function getFileDate(){
		if (!$this->fileExists) return 0;
		return filemtime($this->fsFname);
	}

	/**
	 * Размер файла оригинала в байтах
	 * @return string
	 */
	public function getHumanFileSize(){
		if (!$this->fileExists) return 0;
		$decimals=2;
		$bytes=$this->fileSize;
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		/** @noinspection PhpIllegalStringOffsetInspection */
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
	
	/**
	 * Возвращает разрешение превью файла
	 * для разных форматов оригинала - разные превью (прозрачность/без)
	 * @return string
	 */
	public function getThumbExt(){
		$ext=strtolower($this->format);
		return $ext=='png'||$ext=='pdf'?'png':'jpeg';
	}
	
	/**
	 * Возвращает разрешение файла
	 * @param $origin
	 * @return string
	 */
	public static function cutExtension($origin){
		$parts=pathinfo(strtolower($origin));
		return isset($parts['extension'])?$parts['extension']:'';
	}
	
	
	/**
	 * Возвращает тип файла превью
	 * @param $ext
	 * @return string
	 */
	public static function formThumbFormat($ext){
		return $ext=='png'||$ext=='pdf'?'png':'jpeg';
	}
	
	/**
	 * Возвращает имя файла превью
	 * @param $origin
	 * @param $width
	 * @param $height
	 * @return string
	 */
	public static function formThumbFileName($origin,$width,$height){
		$parts=pathinfo($origin);
		//var_dump($parts);
		$w=$width?$width:'';
		$h=$height?$height:'';
		return '/web/scans/thumbs/'.$parts['basename']."_thumb_{$w}x{$h}.".self::formThumbFormat($parts['extension']);
	}
	
	/**
	 * Возвращает путь превью файла заданного размера
	 * @param $width
	 * @param $height
	 * @return string
	 */
	public function thumbFileName($width,$height){
		return self::formThumbFileName(
			StringHelper::truncate($this->file,80).'.'.$this->format,
			$width,
			$height
		);
	}
	
	
	/**
	 * Возвращает путь к превью файла вписанного в заданный размер
	 * генерирует превью при необходимости
	 * @param $width
	 * @param $height
	 * @return string
	 * @throws ImagickException
	 */
	public function thumb($width,$height){
		$thumbName=$this->thumbFileName($width,$height);
		//return $thumbName;
		$width=$width?$width:null;
		$height=$height?$height:null;
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumbName)) {
			if (!$this->fileExists)
				return static::$NO_ORIG_ERR;
			self::prepThumb($this->fullFname,$thumbName,$width,$height);
		}

		return $thumbName;
	}
	
	/**
	 * Возвращает путь к превью файла вписанного в заданный размер
	 * генерирует превью при необходимости
	 * @param $orig
	 * @param $thumb
	 * @param $width
	 * @param $height
	 * @return string
	 * @throws ImagickException
	 */
	public static function prepThumb($orig,$thumb,$width,$height){
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumb)) {
			if (!file_exists($_SERVER['DOCUMENT_ROOT'].$orig))
				return static::$NO_ORIG_ERR;
			
			$ext=self::cutExtension($orig);
			$format=self::cutExtension($thumb);
			try {
				$im=new Imagick($_SERVER['DOCUMENT_ROOT'] . $orig.($ext=='pdf'?'[0]':''));
			} catch (ImagickException $e) {
				return static::$RENDERING_ERR;
			}
			$im->setImageColorspace(255); // prevent image colors from inverting
			$im->setImageColorSpace(Imagick::COLORSPACE_SRGB); //иначе у белых JPG становился розовый фон
			$im->setimageformat($format);
			if ($ext=='pdf') {
				$bg=new Imagick();
				$bg->setResolution($im->getImageWidth(),$im->getImageHeight());
				$bg->newImage($im->getImageWidth(),$im->getImageHeight(),'white');
				$bg->compositeImage($im, Imagick::COMPOSITE_OVER,0,0);
				$bg->flattenImages();
				$im=$bg;
			}
			$im->resizeImage($width,$height, Imagick::FILTER_LANCZOS,1);
			$im->writeimage($_SERVER['DOCUMENT_ROOT'] . $thumb);
			$im->clear();
			$im->destroy();
		}
		return $thumb;
	}
	
	public function getImageSize() {
		if (isset($this->attrsCache['imageSize'])) return $this->attrsCache['imageSize'];
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $this->getFullFname())) return null;
		try {
			$im=new Imagick($_SERVER['DOCUMENT_ROOT'] . $this->getFullFname());
		} catch (ImagickException $e) {
			return [0,0];
		}
		$this->attrsCache['imageSize']=[$im->getImageWidth(),$im->getImageHeight()];
		$im->clear();
		$im->destroy();
		return $this->attrsCache['imageSize'];
	}
	
	public function getImageWidth() {
		$size=$this->getImageSize();
		if (is_null($size)) return null;
		return $size[0];
	}

	public function getImageHeight() {
		$size=$this->getImageSize();
		if (is_null($size)) return null;
		return $size[1];
	}

	public static function pdfThumb()
	{
		$w=static::$idxThumbSizes[0];
		$h=static::$idxThumbSizes[1];
		return static::prepThumb('/web/scans/pdf_file.png',"/web/scans/thumbs/pdf_file_thumb_${w}x${h}.png",$w,$h);
	}

	public static function noThumb()
	{
		$w=static::$idxThumbSizes[0];
		$h=static::$idxThumbSizes[1];
		return static::prepThumb('/web/scans/no_file.png',"/web/scans/thumbs/no_file_thumb_${w}x${h}.png",$w,$h);
	}
	
	/**
	 * Возвращает превью для странички view
	 * @return string
	 * @throws ImagickException
	 */
	public function getViewThumb(){
		return $this->thumb(static::$viewThumbSizes[0],static::$viewThumbSizes[1]);
	}
	
	/**
	 * Возвращает превью для странички index
	 * @return string
	 * @throws ImagickException
	 */
	public function getIdxThumb(){
		return $this->thumb(static::$idxThumbSizes[0],static::$idxThumbSizes[1]);
	}

	/**
	 * Следующий id
	 * @return integer
	 */
	public static function fetchNextId() {
		return static::find()->max("id")+1;
	}

	public static function fetchNames(){
		$list= static::find()
			->select(['id','file'])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'id', 'descr');
	}
	
	/**
	 * Возвращает договор, в котором этот скан используется
	 */
	public function getContract()
	{
		return $this->hasOne(Contracts::class, ['id' => 'contracts_id']);
	}
	
	/**
	 * Возвращает помещение, в котором этот скан используется
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id']);
	}
	
	/**
	 * Возвращает модель оборудования, в которой этот скан используется
	 */
	public function getTechModel()
	{
		return $this->hasOne(TechModels::class, ['id' => 'tech_models_id']);
	}
	
	/**
	 * Возвращает тип материалов, в котором этот скан используется
	 */
	public function getMaterialType()
	{
		return $this->hasOne(MaterialsTypes::class, ['id' => 'material_types_id']);
	}
	
	/**
	 * Возвращает тип лицензий, в котором этот скан используется
	 */

	public function getLicType()
	{
		return $this->hasOne(LicTypes::class, ['id' => 'lic_types_id']);
	}
	
	/**
	 * Возвращает лицензию, в которой этот скан используется
	 */
	public function getLicItem()
	{
		return $this->hasOne(LicItems::class, ['id' => 'lic_items_id']);
	}
	
	/**
	 * @inheritDoc
	 */
	public static function syncFindLocal($name) {
		$dbName='concat(file,\'.\',format)';		//как этот файл получить в БД
		$regexp='^[0-9]+\-'.preg_quote($name).'$';	//regexp для выражения "числовой_префикс-имяФайла.расширение"
		//echo "select * from scans where $dbName regexp '$regexp'\n";
		
		$query=static::find()->where(['regexp',$dbName,$regexp]);
		if (SyncController::$debug) {
			$class=SyncController::getClassName(static::class);
			echo "Searching local $class: ".$query->createCommand()->rawSql."\n";
		}
		return $query->all();
	}
	
	public static function syncCreate(array $remote, array $overrides, string &$log, RestHelper $rest)
	{
		/** @var $new Scans */
		$new=parent::syncCreate($remote, $overrides, $log, $rest);
		$new->syncFile($remote,$rest);
		return $new;
	}
	
	public function syncFields(array $remote, array $overrides, string &$log, RestHelper $rest)
	{
		$update=parent::syncFields($remote, $overrides, $log, $rest);
		$this->syncFile($remote,$rest);
		return $update;
	}
	
	
}
