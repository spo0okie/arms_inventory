<?php
namespace app\components;

use app\components\assets\RackWidgetAsset;
use yii\base\Widget;

/**
 * Class RackWidget
 * Нам нужно нарисовать сетку/таблицу
 * @package app\components
 *
 * @property integer totalWidth
 * @property integer totalHeight
 *
 * @property bool isSimpleConfig
 * @property integer simpleCols
 * @property integer simpleRows
 * @property integer simpleLeftOffset
 * @property integer simpleRightOffset
 * @property integer simpleTopOffset
 * @property integer simpleBottomOffset
 */
class RackConstructorWidget extends Widget
{
	
	public $form=null;
	public $model=null;
	public $attr=null;
	public $layout=null;
	public $rack=null;
	public $rackDefault=null;
	
	
	public function init() {
		RackWidgetAsset::register($this->view);
		$layout=$this->layout=$this->attr.'_layout';
		
		if (is_object($this->model) && $this->model->$layout) {
			$this->rack=new \app\components\RackWidget(json_decode($this->model->$layout,true));
		}
		
		$this->rackDefault=new \app\components\RackWidget(json_decode('{
			"cols":[
				{"type":"void","size":"25"},
				{"type":"units","size":550,"count":"1"},
				{"type":"void","size":"25"}
			],
			"rows":[
				{"type":"title","size":"100"},
				{"type":"units","size":1865,"count":"42"},
				{"type":"void","size":"35"}
			],
			"hEnumeration":"1",
			"vEnumeration":"-1",
			"evenEnumeration":"1",
			"priorEnumeration":"h",
			"labelPre":1,
			"labelPost":1,
			"labelMode":"h",
			"labelWidth":"50"
		}',true));
		
	}
	
	public function run()
	{
		return $this->render('rack/form',[
			'form'		=>$this->form,
			'model'		=>$this->model,
			'attr'		=>$this->attr,
			'layout'	=>$this->layout,
			'rack'		=>$this->rack,
			'rackDefault'=>$this->rackDefault,
		]);
		
		
	}
}