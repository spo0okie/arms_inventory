<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

?>
<div class="acls-ttip ttip-card">
	<h1>
		<?= Html::encode($model->sname) ?>
	</h1>

	<?= $this->render('notepad',['model'=>$model,'static_view'=>true]) ?>
</div>
