<?php

namespace app\models\ui;

use app\models\Places;
use app\models\traits\AttributeDataModelTrait;
use stdClass;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 * @property string $phone номер куда отправлять
 * @property string $text что отправлять
 */
class MapItemForm extends Model
{
	use AttributeDataModelTrait;
	
	public $place_id;	//куда добавляем
	public $item_type;	//тип объекта на карте
	public $techs_id;	//ID объекта добавленного на карту
	public $places_id;	//ID объекта добавленного на карту
	public $x;
	public $y;
	public $width;
	public $height;
	

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
			[['x','y', 'width', 'height', 'place_id',],'required'],
			[['x','y', 'techs_id', 'places_id', 'width', 'height', 'place_id',],'integer'],
			['item_type','string'],
        ];
    }

    public function attributeData()
	{
		return [
			'item_id'=>'Объект',
			'place_id'=>'Помещение',
		];
	}
	
	public function itemSet() {
    	$model=Places::findOne($this->place_id);
    	$mapStruct=json_decode($model->map);
    	$type=$this->item_type;
    	
    	$item=new stdClass();
		$item->x=(int)($this->x);
		$item->y=(int)($this->y);
		$item->width=(int)($this->width);
		$item->height=(int)($this->height);
  
		if (!property_exists($mapStruct,$type))
			$mapStruct->$type=new stdClass();
    	$item_id_name=$type.'_id';
    	$item_id=(string)($this->$item_id_name);
    	$mapStruct->$type->$item_id=$item;
    	$model->map=json_encode($mapStruct,JSON_UNESCAPED_UNICODE);
    	$model->save();
	}
}
