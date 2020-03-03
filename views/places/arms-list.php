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

foreach ($arms as $arm ) {
	$content.=$this->render('/arms/tdrow',['model'=>$arm]);
	//убираем из рендера оборудования в помещении то, что прилипло к АРМ
	foreach ($arm->voipPhones as $phone)
		foreach ($techs as $i=>$tech) if ($tech['id']==$phone['id']) unset($techs[$i]);
	foreach ($arm->ups as $upsItem)
		foreach ($techs as $i=>$tech) if ($tech['id']==$upsItem['id']) unset($techs[$i]);
}

foreach ($techs as $tech )
	$content.=$this->render('/techs/tdrow',['model'=>$tech]);

if (count($materials))
	$content.=$this->render('/places/materials-list',['models'=>$materials]);

if (strlen($content)) {

?>


<table class="places-cabinet-container">
    <tr>
        <td class="places-arms-cabinet">
	        <?= $this->render('item',['model'=>$model,'short'=>true]) ?>
        </td>
        <td class="places-arms-list">
            <table  class="places-arms-container">
	            <?= $content ?>
            </table>
        </td>
    </tr>
</table>

<?php }
