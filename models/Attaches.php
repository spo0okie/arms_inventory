<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\StringHelper;
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
			return $this->uploadedFile->saveAs($_SERVER['DOCUMENT_ROOT'].$this->fullFname);
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
