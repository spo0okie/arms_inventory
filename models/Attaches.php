<?php

namespace app\models;

use Yii;
use app\models\base\ArmsModel;
use yii\db\ActiveRecord;
use app\helpers\StringHelper;
use yii\web\UploadedFile;

/**
 * This is the model class for table "attaches".
 *
 * @property int $id
 * @property int|null $techs_id
 * @property int|null $services_id
 * @property int|null $lic_types_id
 * @property int|null $lic_groups_id
 * @property int|null $lic_items_id
 * @property int|null $lic_keys_id
 * @property int|null $contracts_id
 * @property int|null $places_id
 * @property int|null $schedules_id
 * @property string|null $filename
 * @property string|null $fullFname
 * @property UploadedFile $uploadedFile
 */
class Attaches extends ArmsModel
{
	
	public $uploadedFile;
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'attaches';
    }

	public static $title='Вложение';
	public static $titles='Вложения';

	public static function modelDescription(): string
	{
		return 'Файлы-вложения (сканы, фотографии, документы), прикреплённые к объектам '
			.'системы: оборудованию, документам, лицензиям и т.д. '
			.'Служебная модель — файлы загружаются со страниц самих объектов.';
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{		
		return array_merge(parent::attributeData(), [
			'filename' => ['Файл', 'hint' => 'Имя загруженного файла в хранилище вложений (web/scans)'],
			'techs_id' => [Techs::$titles, 'hint' => 'Оборудование/АРМ, к которому прикреплено вложение'],
			'services_id' => [Services::$titles, 'hint' => 'Сервис, к которому прикреплено вложение'],
			'lic_types_id' => [LicTypes::$titles, 'hint' => 'Схема лицензирования, к которой прикреплено вложение'],
			'lic_groups_id' => [LicGroups::$titles, 'hint' => 'Тип лицензий, к которому прикреплено вложение'],
			'lic_items_id' => [LicItems::$titles, 'hint' => 'Закупка лицензий, к которой прикреплено вложение'],
			'lic_keys_id' => [LicKeys::$titles, 'hint' => 'Лицензионный ключ, к которому прикреплено вложение'],
			'contracts_id' => [Contracts::$titles, 'hint' => 'Документ, к которому прикреплено вложение'],
			'partners_id' => [Partners::$titles, 'hint' => 'Контрагент, к которому прикреплено вложение'],
			'places_id' => [Places::$titles, 'hint' => 'Помещение, к которому прикреплено вложение'],
			'schedules_id' => ['Расписание', 'hint' => 'Расписание/временной доступ, к которому прикреплено вложение'],
			'tech_models_id' => [TechModels::$titles, 'hint' => 'Модель оборудования, к которой прикреплено вложение'],
			'users_id' => [Users::$titles, 'hint' => 'Сотрудник, к которому прикреплено вложение'],
			'maintenance_reqs_id' => [MaintenanceReqs::$titles, 'hint' => 'Регламент обслуживания, к которому прикреплено вложение'],
			'maintenance_jobs_id' => [MaintenanceJobs::$titles, 'hint' => 'Работа обслуживания, к которой прикреплено вложение'],
		]);
	}

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
            	[
            		'techs_id',
					'services_id',
					'lic_types_id',
					'lic_groups_id',
					'lic_items_id',
					'lic_keys_id',
					'contracts_id',
					'partners_id',
					'places_id',
					'schedules_id',
					'tech_models_id',
					'users_id',
					'maintenance_reqs_id',
					'maintenance_jobs_id',
				],
				'integer'
			],
			[['filename'], 'string'],
			//[['uploadedFile'], 'file'],
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
			$this->filename=$prefix.'-'. StringHelper::truncate($this->uploadedFile->baseName,80).'.'.$this->uploadedFile->extension;
			return $this->uploadedFile->saveAs(Yii::getAlias('@app').$this->fullFname);
		} else {
			return false;
		}
	}
	
	/**
	 * Возвращает путь к оригиналу файла
	 * @return string
	 */
	public function getFullFname(){
		return '/web/scans/'.$this->filename;
	}
	
	public function getName() {
		$tokens=explode('-',$this->filename);
		unset($tokens[0]);
		return implode('-',$tokens);
	}
	/**
	 * Следующий id
	 * @return integer
	 */
	public static function fetchNextId() {
		return static::find()->max("id")+1;
	}

}
