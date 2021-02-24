<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

if (!is_null($percent=$model->usedPercent)) {
	$style="info";
	if ($percent>15) $style="success";
	if ($percent>75) $style="warning";
	if ($percent>90) $style="danger";
	?>
	<div class='progress'>
  		<div class='progress-bar progress-bar-<?= $style ?>'
			 role='progressbar'
			 aria-valuenow='<?= $model->used ?>'
			 aria-valuemin='0'
			 aria-valuemax='<?= $model->capacity ?>'
			 style='min-width: 1.5em; width: <?= $percent ?>%;'
		>
    		<?= $model->used ?>
  		</div>
	</div>
<?php }
