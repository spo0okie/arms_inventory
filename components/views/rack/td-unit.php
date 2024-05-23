<?php
/**
 * Ячейка - юнит
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $width */
/* @var $height */
/* @var $col */
/* @var $row */
/* @var $sectionRow */
/* @var $sectionRowCount */
/* @var $sectionCol */
/* @var $sectionColCount */
/* @var $rack RackWidget */
/* @var $models Techs[] */
/* @var $this yii\web\View */
/* @var $labels array */


use app\components\RackWidget;
use app\helpers\ArrayHelper;
use app\models\Techs;
use yii\helpers\Url;

/*
 * Спереди это либо спереди в передней корзине true && true
 * либо с обратной стороны задней корзины false && false
 */
$front=($rack->front && $rack->front_rack) || (!$rack->front && !$rack->front_rack);

$unitId=$rack->getSectorId($col,$row);
$labelWidth=$rack->getWidthPercent($rack->labelWidth);

$rowspan=1;
$colspan=1;
$skip=false;

$content='';
$contentClass='';
$techInstalled=false;
foreach ($models as $model) {
	if ($model->isInstalledAt($unitId,$front)) {
		$techInstalled=true;
		if (
			(!isset($model->renderedInFrontRack[$unitId]) && $rack->front)
			||
			(!isset($model->renderedInBackRack[$unitId]) && !$rack->front)
		) {
			$content = $this->render('/techs/item', ['model' => $model]);
			
			$contentClass = 'tech_' . $model->type->code;
			//Теперь пробуем увеличивать колонку таблицы и проверять входит ли она в это оборудование
			for ($x = $col + 1; $x < $sectionColCount; $x++) {
				if ($model->isInstalledAt($rack->getSectorId($x, $row), $rack->front))
					$colspan++;
				else
					break;
			}
			
			for ($y = $row + 1; $x < $sectionRowCount; $y++) {
				if ($model->isInstalledAt($rack->getSectorId($col, $y), $rack->front))
					$rowspan++;
				else
					break;
			}
			
			//теперь запоминаем в каких юнитах это оборудование отрендерится
			for ($x = $col; $x < $col + $colspan; $x++) {
				for ($y = $row; $y < $row + $rowspan; $y++) {
					$uid = $rack->getSectorId($x, $y);
					if ($front)
						$model->renderedInFrontRack[$uid] = true;
					else
						$model->renderedInBackRack[$uid] = true;
				}
			}
			
			
		} else {
			$skip = true;
		}
	}
}

if (!$techInstalled) {
	$label= ArrayHelper::getItemByFields($labels,[
		'pos'=>$unitId,
		'back'=>!$front,
	]);
	if (is_array($label)) $content=$label['label'];
}

//наличие метки перед юнитом
$labelPre=($rack->front&&$rack->labelPre || !$rack->front&&$rack->labelPost);
//наличие метки после юнита
$labelPost=($rack->front&&$rack->labelPost || !$rack->front&&$rack->labelPre);
//четная колонка
$even=($col % 2)==1;
//необходимость смещать метку в четных колонках
$evenShift=$rack->evenLabelShift && $even;



//метка слева
if ($rack->labelMode=='h') {
	//метку слева ставим если надо слева и смещения нет, или надо справа и смещение есть
	if ((!$evenShift && $labelPre) || ($evenShift && $labelPost))
		echo $this->render('td-label',['rackId'=>$rack->id,'unitId'=>$unitId,'width'=>$labelWidth]);
}

$installedClass='';
$style='';
if ($content) {
	$installedClass='rack-unit-installed';
	//если у нас высота больше ширины в 2 раза - поворачиваем текст набок
	if ($width<$height/2) $style='style="writing-mode:vertical-rl;';
}

if (!$skip) {

?>



<!--suppress HtmlDeprecatedAttribute -->
	<td
	class="
		rack-unit
		rack-<?= $rack->id ?>-unit-<?= $unitId ?>
		<?= $installedClass ?>
		<?= $contentClass ?>
		<?= $techInstalled?'':'open-in-modal-form' ?>
	"
	width="<?= $width ?>%"
	id="rack-<?= $rack->id ?>-unit-<?= $unitId ?>"
	colspan="<?= $colspan ?>"
	rowspan="<?= $rowspan ?>"
	data-rack-two-sided="<?= $rack->two_sided ?>"
	<?= $style ?>
	<?=
	is_object($rack->model)?
		('href="'. Url::to(['/techs/rack-unit',
			'id'=>$rack->model->id,
			'unit'=>$unitId,
			'front'=>$front
		]).'"'):''
	?>
>
<?= $content ?>
</td>


<?php
}
if  ($rack->labelMode=='h') {
	//метку слева ставим если надо слева и смещения нет, или надо справа и смещение есть
	if (($evenShift && $labelPre) || (!$evenShift && $labelPost))
		echo $this->render('td-label',['rackId'=>$rack->id,'unitId'=>$unitId,'width'=>$labelWidth]);
}

