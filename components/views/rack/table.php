<?php
/**
 * Таблица - макет расстановки оборудования в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $rack RackWidget */
/* @var $this yii\web\View */
/* @var $models Techs[] */

use app\components\RackWidget;
use app\models\Techs;

//print_r($rack->rows);
//echo"<br>";
//print_r($rack->cols);
//echo"<br>";
$width=300;
$height=$width*$rack->getTotalHeight()/$rack->getTotalWidth();
$id=$rack->id;
$labelWidth=$rack->getWidthPercent($rack->labelWidth);
$y=0;
$evenLabelShift=$rack->evenLabelShift;

if (is_object($rack) && is_object($rack->model)) {
	$labels=$rack->model->getExternalItem(['rack-labels'],[]);
	$models=$rack->model->installedTechs;
} else {
	$models=[];
	$labels=[];
}

$fontHeight=min (($rack->smallestUnitHeight-2)*$height/$rack->getTotalHeight()*0.8,16);

//echo $rack->getTotalWidth()."x".$rack->getTotalHeight().'<br />';
?>
<table class="rack-widget" id="rack-<?= $id ?>" style="width:<?= $width ?>px; height:<?= $height ?>px; font-size: <?= $fontHeight ?>px;">
	<tbody>
		<?php foreach ($rack->rows as $row) {
			switch ($row['type']) {
				case 'units':
					for ($i=0; $i<$row['count']; $i++) {
						$stackHeightPx=$rack->getHeightPercent($row['size'])*($height-2); //высота одного юнита в процентах от всей высоты шкафа
						$trHeight="calc({$stackHeightPx}px / {$row['count']})";	//высота строки таблицы в процентах от всей высоты шкафа (минус бордеры по 1px каждый)
						echo $this->render('tr-units',[
							'row'=>$y,
							'sectionRow'=>$i,
							'sectionRowCount'=>$row['count'],
							'rack'=>$rack,
							'models'=>$models,
							'labels'=>$labels,
							'evenLabelShift'=>$evenLabelShift,
							'height'=>$trHeight,
							'totalHeight'=>$height
						]);
						$y++;
					}
					break;
				case 'title':
					echo $this->render('tr-title',[
						'height'=>$rack->getHeightPercent($row['size']),
						'rack'=>$rack,
						'titleHeight'=>$row['size']*$height/$rack->totalHeight
					]);
					break;
				default:
				//пропуск по высоте
					echo $this->render('tr-empty',[
						'height'=>$rack->getHeightPercent($row['size']),
						'rack'=>$rack,
					]);
					break;
			}
 		} ?>
	</tbody>
</table>

