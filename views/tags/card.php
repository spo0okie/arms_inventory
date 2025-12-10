<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;

/**
 * Карточка тега для view и tooltip
 * 
 * @var yii\web\View $this
 * @var app\models\Tags $model
 * @var $static_view
 */


$textColor = $model->getTextColor();
?>

<div class="tags-card">
    
	<h1>
		<?= $model->renderItem($this,[
			'static_view'=>$static_view,
			'noDelete'=>$static_view,
			'hideUndeletable'=>false,
		]) ?>
	</h1>

	<?= ModelFieldWidget::widget([
		'model' => $model,
		'field' => 'description',
		'title' => false
	]) ?>
    
</div>