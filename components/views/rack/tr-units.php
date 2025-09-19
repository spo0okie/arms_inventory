<?php
/**
 * Таблица - макет расстановки оборудования в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $height */
/* @var $cssHeight */
/* @var $totalHeight */
/* @var $row */
/* @var $sectionRow */
/* @var $sectionRowCount */
/* @var $rack RackWidget */
/* @var $this yii\web\View */
/* @var $models Techs[] */
/* @var $labels array */


$id=$rack->id;
if ($rack->front) {
	$currentCol=0; $x=0;
	$colShift=1;
} else {
	$currentCol=count($rack->cols)-1;
	$x=$rack->getUnitCols()-1;
	$colShift=-1;
}

$fontSize=min (($rack->smallestUnitHeight-2)*$totalHeight/$rack->getTotalHeight()*0.9,16);

use app\components\RackWidget;
use app\models\Techs;
?>

<tr style="height:<?= $cssHeight ?>" style="font-size: <?= $fontSize ?>px">
<?php
	//если рисуем переднюю сторону, то слева направо, а если зад, то наоборот
	for ($c=0; $c<count($rack->cols); $c++) {
		$col=$rack->cols[$currentCol];
		switch ($col['type']) {
			case 'units':
				for ($j=0;$j<$col['count']; $j++) {
					$absWidth=$col['size']/$col['count'];
					//если у нас горизонтально раскидываются метки, то убираем их ширину из ширины ячейки
					if ($rack->labelMode=='h') $absWidth-=$rack->labelWidth*$rack->getLabelsCount();
					$width=$rack->getWidthPercent($absWidth);
					
					echo $this->render('td-unit',[
						'width'=>$width,
						'height'=>$height,
						'cssHeight'=>$cssHeight,
						'models'=>$models,
						'labels'=>$labels,
						'col'=>$x,
						'row'=>$row,
						'rack'=>$rack,
						'sectionCol'=>$j,
						'sectionColCount'=>$col['count'],
						'sectionRow'=>$sectionRow,
						'sectionRowCount'=>$sectionRowCount,
					]);
								
					$x+=$colShift;
				}
				break;
				
			default:
				if (!$row) {
					$width=$rack->getWidthPercent($col['size']);
					$rowspan=$rack->getTotalRows();
					echo "<td style=\"width:$width%\" rowspan=\"$rowspan\" >&nbsp;</td>";
				}
				break;
		}
		$currentCol+=$colShift;
	}
?>
</tr>