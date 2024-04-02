<?php


/* @var $this yii\web\View */
/* @var $model app\models\ServiceConnections */

if (!isset($source)) $source='initiator';
if (!isset($glue)) $glue=': ';

if (!isset($self)) $self=false;
if (!isset($nodes)) $nodes=true;
if (!isset($details)) $details=true;
if (!isset($service)) $service=true;

if (!isset($static_view)) $static_view=true;

$details_attr=$source.'_details';

$Data=[];

$Nodes=[];

//узлы
if ($nodes) {
	foreach ($model->getNodesEffective($source,'comps') as $comp)
		$Nodes[]=$this->render('/comps/item',['model'=>$comp]);
	
	foreach ($model->getNodesEffective($source,'techs') as $tech)
		$Nodes[]=$this->render('/techs/item',['model'=>$tech]);
}

//детали
if ($details && strlen($model->$details_attr)) $Nodes[]=$model->$details_attr;

//сервис
if ($service && is_object($model->$source))
	$Data[]=$this->render('/services/item',['model'=>$model->$source,'static_view'=>true]);

if (count($Nodes)) $Data[]=implode(', ',$Nodes);

if ($self)
	$Data[]=$this->render('item',['model'=>$model,'static_view'=>$static_view]);

echo '<div class="d-flex flex-row flex-wrap">'.implode($glue, $Data).'</div>';
