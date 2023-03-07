<?php
/**
 * Таблица - макет расстановки оборудования в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $rack \app\components\RackWidget */
/* @var $this yii\web\View */

use yii\helpers\Html;
//print_r($rows);
//echo"<br>";
//print_r($rack->cols);
//echo"<br>";
$width=300;
$height=$width*$rack->getTotalHeight()/$rack->getTotalWidth();
$id=$rack->id;
$labelWidth=$rack->getWidthPercent($rack->labelWidth);
$y=0;
//echo $rack->getTotalWidth()."x".$rack->getTotalHeight().'<br />';
?>
<table class="rack-widget" id="rack-<?= $id ?>" width="<?= $width ?>" height="<?= $height ?>">
	<tbody>
		<?php foreach ($rack->rows as $row) {
			switch ($row['type']) {
				case 'units':
					for ($i=0; $i<$row['count']; $i++) {
						echo $this->render('tr-units',[
							'row'=>$y,
							'rack'=>$rack,
							'height'=>$rack->getHeightPercent($row['size']/$row['count']),
						]);
						$y++;
					}
					break;
				case 'title':
					echo $this->render('tr-title',[
						'height'=>$rack->getHeightPercent($row['size']),
						'rack'=>$rack,
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
