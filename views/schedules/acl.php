<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

?>
<div class="schedules-acls">
	<h2>Доступы</h2>
	<?php foreach ($model->acls as $acl) { ?>
		<?= $this->render('/acls/card',['model'=>$acl]) ?>
	<?php } ?>

	<?= Html::a('Добавить',['acls/create','schedules_id'=>$model->id],['class'=>'btn btn-success'])?>
</div>
