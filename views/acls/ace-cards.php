<?php

use app\components\widgets\page\ModelWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;
if (count ($model->aces)) foreach ($model->aces as $ace) {
	echo ModelWidget::widget(['model'=>$ace,'view'=>'card']);
} else { ?>
	<span class="text-center divider2-striped text-white">
		<span class="acl-card p-1">
				<span class="fas fa-exclamation-triangle"></span>
				НЕТ ЭЛЕМЕНТОВ
				<span class="fas fa-exclamation-triangle"></span>
		</span>
	</span>
	<span class="row text-center text-white"><small >добавьте записи в этот элемент списка доступа</small></span>
<?php }

