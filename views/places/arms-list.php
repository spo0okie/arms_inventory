<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $arms app\models\Arms[] */
/* @var $techs app\models\Techs[] */

$arms=$model->arms;
$techs=$model->techs;
$materials=$model->materials;
$attached=0; //техника прикрепленная к АРМ
//foreach ($arms as $arm) foreach ($arm->techs as $tech)
    //if (!$tech->isVoipPhone && !$tech->isUps) $attached++;

$content='';

$cabinet_col='<td class="places-arms-cabinet" rowspan="0">'.$this->render('item',['model'=>$model,'short'=>true]).'</td>';

foreach ($arms as $arm ) {
	$content.=$this->render(
		'/arms/tdrow',[
			'model'=>$arm,
			'cabinet_col'=>strlen($content)?null:$cabinet_col
		]
	);
	//убираем из рендера оборудования в помещении то, что прилипло к АРМ
	foreach ($arm->voipPhones as $phone)
		foreach ($techs as $i=>$tech) if ($tech['id']==$phone['id']) unset($techs[$i]);
	foreach ($arm->ups as $upsItem)
		foreach ($techs as $i=>$tech) if ($tech['id']==$upsItem['id']) unset($techs[$i]);
}

foreach ($techs as $tech )
	$content.=$this->render(
		'/techs/tdrow',[
			'model'=>$tech,
			'cabinet_col'=>strlen($content)?null:$cabinet_col
		]
	);

if (count($materials))
	$content.=$this->render(
		'/places/materials-list',[
			'models'=>$materials,
			'cabinet_col'=>strlen($content)?null:$cabinet_col
		]
	);

if (strlen($content)) {

?>


<table class="places-arms-container">
	<colgroup>
		<col class="arms_cabinet" />
		<col class="arm_id" />
		<col class="arm_hostname" />
		<col class="arm_uname" />
		<col class="arm_uphone" />
		<col class="arm_model" />
		<col class="hardware" />
		<col class="attachments" />
		<col class="item_status" />
		<col class="item_ip" />
		<col class="item_invnum" />
	</colgroup>
	
	

	<?= $content ?>
</table>

<?php }
