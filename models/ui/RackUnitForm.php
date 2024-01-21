<?php

namespace app\models\ui;

use app\helpers\ArrayHelper;
use app\models\Techs;
use app\models\traits\AttributeDataModelTrait;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property int $tech_id Что ставить то?
 * @property int $tech_rack_id Куда ставить то? (в какой шкаф)
 * @property string $tech_installed_pos Куда ставить то? (в какие юниты)
 * @property int $pos Куда ставить то? (в какие юниты) //для label
 * @property bool $back Куда ставить то? (спереди?/сзади?)
 *
 */
class RackUnitForm extends Model
{
	use AttributeDataModelTrait;
	
	public $tech_id;
	public $tech_rack_id;
	public $tech_installed_pos;
	public $tech_installed_pos_end;
	public $tech_full_length;
    public $label;
	public $back;
	public $pos;
	public $insert_tech;
	public $insert_label;

    private $_tech = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['tech_rack_id'], 'required'],
			[['tech_id', 'tech_installed_pos'],'required','when'=>function($model){return (bool)$model->insert_tech;}],
			[['label'],'required','when'=>function($model){return (bool)$model->insert_label;}],
            [['back', 'insert_tech', 'insert_label'], 'boolean'],
        ];
    }
	
	
	/**
	 * Массив описания полей
	 */
	public function attributeData()
	{
		$tech=new Techs();
		$techAttr=$tech->attributeData();
		return [
			'insert_tech'=>['Установить оборудование'],
			'insert_label'=>['Установить заглушку'],
			'tech_id' => [
				'Оборудование',
			],
			'tech_installed_pos' => $techAttr['installed_pos'],
			'tech_installed_pos_end' => $techAttr['installed_pos_end'],
			'tech_full_length' => $techAttr['full_length'],
			'label'=>['Тип заглушки'],
		];
	}
	
	protected function setUnitLabel($rack) {
		
		$labels=$rack->getExternalItem(['rack-labels'],[]);
		
		//если метка с такими коорд есть то меняем, иначе добавляем
		ArrayHelper::setItemByFields($labels,[
			'pos'=>$this->pos,
			'back'=>$this->back,
		],[
			'pos'=>$this->pos,
			'back'=>$this->back,
			'label'=>$this->label
		]);
		
		$rack->setExternalItem(['rack-labels'],$labels);
	}
	
	protected function removeUnitLabel($rack) {
		
		$labels=ArrayHelper::removeItemsByFields(
			$rack->getExternalItem(['rack-labels'],[]),
			[
				'pos'=>$this->pos,
				'back'=>$this->back,
			]
		);
		
		$rack->setExternalItem(['rack-labels'],$labels);
		return $rack->save();
	}
	
	/**
	 * @param $tech Techs
	 */
	protected function setUnitTech(Techs $tech){
		$tech->installed_id=$this->tech_rack_id;
		$tech->installed_pos=$this->tech_installed_pos;
		$tech->installed_back=$this->back;
		$tech->full_length=$this->tech_full_length;
	}
	
	
	/**
     * Вставляем одно в другое
     */
    public function setUnit()
    {
		$rack=Techs::findOne($this->tech_rack_id);

		if (!$this->insert_label && !$this->insert_tech) {
			$this->removeUnitLabel($rack);
			return $rack->save();
		}
		
		if ($this->insert_label) {
			$this->setUnitLabel($rack);
			return $rack->save();
		}
		
		if ($this->insert_tech) {
			$this->removeUnitLabel($rack);
			$rackOk=$rack->save();
			$tech=Techs::findOne($this->tech_id);
			$this->setUnitTech($tech);
			$techOk=$tech->save();
			return $rackOk && $techOk;
		}
		return false;
    }


}
