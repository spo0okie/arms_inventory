<?php
/**
 * Таблица - макет расстановки оборудования в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $height */
/* @var $row */
/* @var $rack \app\components\RackWidget */
/* @var $this yii\web\View */


$labelWidth=$rack->getWidthPercent($rack->labelWidth);
$id=$rack->id;
if ($rack->front) {
	$currentCol=0; $x=0;
	$colShift=1;
} else {
	$currentCol=count($rack->cols)-1;
	$x=$rack->getUnitCols()-1;
	$colShift=-1;
}

use yii\helpers\Html;
?>

<tr height="<?= $height ?>%">
<?php
	//если рисуем переднюю сторону, то слева направо, а если зад, то наоборот
	for ($c=0; $c<count($rack->cols); $c++) {
		$col=$rack->cols[$currentCol];
		switch ($col['type']) {
			case 'units':
				for ($j=0;$j<$col['count']; $j++) {
					$width=$rack->getWidthPercent($col['size']/$col['count']-$rack->labelWidth*$rack->getLabelsCount());
					$sectorId=$rack->getSectorId($x,$row);
					
					//метка слева
					if ($rack->labelMode=='h' && ($rack->front&&$rack->labelPre || !$rack->front&&$rack->labelPost))
						echo $this->render('td-label',['rackId'=>$id,'unitId'=>$sectorId,'width'=>$labelWidth]);
					
					echo $this->render('td-unit',['rackId'=>$id,'unitId'=>$sectorId,'width'=>$width]);
								
					if  ($rack->labelMode=='h' && ($rack->front&&$rack->labelPost || !$rack->front&&$rack->labelPre))
						echo $this->render('td-label',['rackId'=>$id,'unitId'=>$sectorId,'width'=>$labelWidth]);

					$x+=$colShift;
				}
				break;
				
			default:
				if (!$row) {
					$width=$rack->getWidthPercent($col['size']);
					$rowspan=$rack->getTotalRows();
					echo "<td width=\"$width%\" rowspan=\"$rowspan\" >&nbsp;</td>";
				}
				break;
		}
		$currentCol+=$colShift;
	}
?>
</tr>