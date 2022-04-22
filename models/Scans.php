<?php

namespace app\models;

use Imagine\Imagick\Imagine;
use Yii;
use yii\imagine\Image;


/**
 * This is the model class for table "scans".
 *
 * @property int $id id
 * @property int $contracts_id contracts link
 * @property string $viewThumb
 * @property string $idxThumb
 * @property string $shortFname
 * @property string $fullFname
 * @property string $fsFname
 * @property boolean $fileExists
 * @property int $fileSize
 * @property int $humanFileSize
 * @property string $file
 * @property string $format
 * @property yii\web\UploadedFile $scanFile
 * @property string $descr
 * @property array $contracts
 */
class Scans extends \yii\db\ActiveRecord
{
	/*
	 * Ошибка потерянного изображения
	 */
	public static $NO_ORIG_ERR='err_no_orig';
	public static $PDF_ORIG_ERR='pdf_no_orig';
	public $scanFile;

	public static $viewThumbSizes=[512,512];
	public static $idxThumbSizes=[160,160];

	public static $title="Сканы документов";



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
            [['contracts_id','places_id','tech_models_id','material_models_id','lic_types_id','lic_items_id','arms_id','techs_id'], 'integer'],
	        [['scanFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg, pdf, gif', 'maxSize' => 1024*1024*30],
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
	 * Сохраняет файл
	 * @return bool
	 */
	public function upload()
	{
		if ($this->validate()) {
			$prefix=($this->id)?$this->id:static::fetchNextId();
			$this->file=$prefix.'-'. $this->scanFile->baseName;
			$this->format=$this->scanFile->extension;
			$this->scanFile->saveAs($_SERVER['DOCUMENT_ROOT'].$this->fullFname);
			return true;
		} else {
			return false;
		}
	}


	public function getThumbUrl(){
		if (!$this->fileExists) {
			return static::noThumb();
		} else {
			$this->idxThumb;
		}
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
		return $_SERVER['DOCUMENT_ROOT'].$this->fullFname;
	}

	/**
	 * Проверяет, что оригинал присутствует
	 * @return boolean
	 */
	public function getFileExists(){
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
	public function getHumanFileSize(){
		if (!$this->fileExists) return 0;
		$decimals=2;
		$bytes=$this->fileSize;
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];	}

	/**
	 * Возвращает путь превью файла заданного размера
	 * @return string
	 */
	public function thumbFname($width,$height){
		$w=$width?$width:'';
		$h=$height?$height:'';
		$x='x';
		$ext=strtolower($this->format)=='png'?'png':'jpg';
		return '/web/scans/thumbs/'.$this->file."_thumb_$w$x$h.$ext";
	}

	/**
	 * Возвращает путь к превью файла вписанного в заданный размер
	 * генерирует превью при необходимости
	 * @return string
	 */
	public function thumb($width,$height){
		$thumbName=$this->thumbFname($width,$height);
		$width=$width?$width:null;
		$height=$height?$height:null;
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumbName)) {
			if (!$this->fileExists)
				return static::$NO_ORIG_ERR;
			$imagine=new Imagine();
			$image=$imagine->open($_SERVER['DOCUMENT_ROOT'] . $this->fullFname);
			
			Image::resize($image, $width, $height)
				->save($_SERVER['DOCUMENT_ROOT'] . $thumbName,['quality'=>80]);
		}

		return $thumbName;
	}

	/**
	 * Возвращает путь к превью файла вписанного в заданный размер
	 * генерирует превью при необходимости
	 * @return string
	 */
	public static function prepThumb($orig,$thumb,$width,$height){
		$width=$width?$width:null;
		$height=$height?$height:null;
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].$thumb)) {
			if (!file_exists($_SERVER['DOCUMENT_ROOT'].$orig))
				return static::$NO_ORIG_ERR;
			$imagine=new Imagine();
			$image=$imagine->open($_SERVER['DOCUMENT_ROOT'] . $orig);
			
			Image::resize($image, $width, $height)
				->save($_SERVER['DOCUMENT_ROOT'].$thumb,['quality'=>80]);
		}
		return $thumb;
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
	 */
	public function getViewThumb(){
		return $this->thumb(static::$viewThumbSizes[0],static::$viewThumbSizes[1]);
	}

	/**
	 * Возвращает превью для странички index
	 * @return string
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
		return static::hasOne(Contracts::className(), ['id' => 'contracts_id']);
	}
	
	/**
	 * Возвращает помещение, в котором этот скан используется
	 */
	public function getPlace()
	{
		return static::hasOne(Places::className(), ['id' => 'places_id']);
	}
	
	/**
	 * Возвращает модель оборудования, в которой этот скан используется
	 */
	public function getTechModel()
	{
		return static::hasOne(TechModels::className(), ['id' => 'tech_models_id']);
	}
	
	/**
	 * Возвращает тип материалов, в котором этот скан используется
	 */
	public function getMaterialType()
	{
		return static::hasOne(MaterialsTypes::className(), ['id' => 'material_types_id']);
	}
	
	/**
	 * Возвращает тип лицензий, в котором этот скан используется
	 */

	public function getLicType()
	{
		return static::hasOne(LicTypes::className(), ['id' => 'lic_types_id']);
	}
	
	/**
	 * Возвращает лицензию, в которой этот скан используется
	 */
	public function getLicItem()
	{
		return static::hasOne(LicItems::className(), ['id' => 'lic_items_id']);
	}
}
