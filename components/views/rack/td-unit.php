<?php
/**
 * Ячейка - юнит
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $width */
/* @var $col */
/* @var $row */
/* @var $sectionRow */
/* @var $sectionRowCount */
/* @var $sectionCol */
/* @var $sectionColCount */
/* @var $rack \app\components\RackWidget */
/* @var $models \app\models\Techs[] */
/* @var $this yii\web\View */

use yii\helpers\Html;


$unitId=$rack->getSectorId($col,$row);
$labelWidth=$rack->getWidthPercent($rack->labelWidth);

$rowspan=1;
$colspan=1;
$skip=false;

$content='';
$contentClass='';
foreach ($models as $model) {
	if ($model->isInstalledAt($unitId,$rack->front)) {
		if (
			(!$model->renderedInFrontRack && $rack->front)
			||
			(!$model->renderedInBackRack && !$rack->front)
		) {
			$content=$this->render('/techs/item',['model'=>$model]);
			if ($rack->front)
				$model->renderedInFrontRack=true;
			else
				$model->renderedInBackRack=true;
			$contentClass='tech_'.$model->type->code;
			//Теперь пробуем увеличивать колонку таблицы и проверять входит ли она в это оборудование
			for ($x=$col+1;$x<$sectionColCount; $x++) {
				if ($model->isInstalledAt($rack->getSectorId($x,$row)))
					$colspan++;
				else
					break;
			}
			
			for ($y=$row+1;$x<$sectionRowCount; $y++) {
				if ($model->isInstalledAt($rack->getSectorId($col,$y)))
					$rowspan++;
				else
					break;
			}
		} else {
			$skip=true;
		}
	}
}


//метка слева
if ($rack->labelMode=='h' && ($rack->front&&$rack->labelPre || !$rack->front&&$rack->labelPost))
	echo $this->render('td-label',['rackId'=>$rack->id,'unitId'=>$unitId,'width'=>$labelWidth]);

if (!$skip) {
?>



<td
	class="rack-unit rack-<?= $rack->id ?>-unit-<?= $unitId ?> <?= $contentClass ?>"
	width="<?= $width ?>%"
	id="rack-<?= $rack->id ?>-unit-<?= $unitId ?>"
	colspan="<?= $colspan ?>"
	rowspan="<?= $rowspan ?>"
>
<?= $content ?>
</td>


<?php
}
if  ($rack->labelMode=='h' && ($rack->front&&$rack->labelPost || !$rack->front&&$rack->labelPre))
	echo $this->render('td-label',['rackId'=>$rack->id,'unitId'=>$unitId,'width'=>$labelWidth]);
	
