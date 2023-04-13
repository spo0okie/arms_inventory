<?php
/**
 * Таблица - макет расстановки оборудования в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $height */
/* @var $totalHeight */
/* @var $row */
/* @var $sectionRow */
/* @var $sectionRowCount */
/* @var $rack \app\components\RackWidget */
/* @var $this yii\web\View */
/* @var $models \app\models\Techs[] */


$id=$rack->id;
if ($rack->front) {
	$currentCol=0; $x=0;
	$colShift=1;
} else {
	$currentCol=count($rack->cols)-1;
	$x=$rack->getUnitCols()-1;
	$colShift=-1;
}

$fontSize=($rack->smallestUnitHeight-2)*$totalHeight/$rack->getTotalHeight()*0.6;
use yii\helpers\Html;
?>

<tr height="<?= $height ?>%" style="font-size: <?= $fontSize ?>px">
<?php
	//если рисуем переднюю сторону, то слева направо, а если зад, то наоборот
	for ($c=0; $c<count($rack->cols); $c++) {
		$col=$rack->cols[$currentCol];
		switch ($col['type']) {
			case 'units':
				for ($j=0;$j<$col['count']; $j++) {
					$width=$rack->getWidthPercent($col['size']/$col['count']-$rack->labelWidth*$rack->getLabelsCount());
					
					echo $this->render('td-unit',[
						'width'=>$width,
						'models'=>$models,
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
					echo "<td width=\"$width%\" rowspan=\"$rowspan\" >&nbsp;</td>";
				}
				break;
		}
		$currentCol+=$colShift;
	}
?>
</tr>