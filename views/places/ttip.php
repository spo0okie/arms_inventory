<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

?>
<div class="ttip-card places-ttip">

    <h1>
        <?= Html::encode($model->name) ?>
    </h1>
	
	<?php if(strlen($model->addr)) { ?>
		<div class="places-addr mb-2">
            <span class="fas fa-envelope"></span><?= Html::encode($model->addr) ?>
        </div>
	<?php } ?>
	
	<?php if (count($model->phones)) { ?>
	<div class="org-phones mb-2">
        <?php
			foreach ($model->phones as $phone)
				echo $phone->renderItem($this,['icon'=>true,'show_archived'=>false,'static_view'=>true]);
		?>
    </div>
	<?php } ?>

	
	<?php if (count($model->inets)) { ?>
	<div class="org-phones mb-2">
		<?php
			foreach ($model->inets as $inet)
				echo $inet->renderItem($this,['icon'=>true,'show_archived'=>false,'static_view'=>true]);

		?>
    </div>
	<?php } ?>

	
	<?php if(strlen($model->comment)) { ?>
		<div class="places-comment mb-2">
            <span class="fas fa-info-circle"></span><?= Html::encode($model->comment) ?>
        </div>
	<?php } ?>
	
</div>
