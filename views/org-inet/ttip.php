<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

$static_view=true;

?>
<div class="org-inet-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'static_view'=>true,'content_only'=>true]) ?>
	<hr />
	<h4>Услуга связи</h4>
	<p>
		<?= ModelWidget::widget(['model'=>$model->service,'options'=>['static_view'=>$static_view]]) ?>
	</p>


	<h4>Провайдер</h4>
	<?= is_object($model->partner)?ModelWidget::widget(['model'=>$model->partner,'view'=>'card','options'=>['static_view'=>$static_view]]):'' ?>

</div>


