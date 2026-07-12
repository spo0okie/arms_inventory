<?php

use app\components\widgets\page\ModelWidget;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Acls $model */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;
if (!isset($groupMode)) $groupMode=false;

if (count ($model->aces)) foreach ($model->aces as $ace) {
	echo ModelWidget::widget(['model'=>$ace,'view'=>'card','static_view'=>$static_view, 'groupMode'=>$groupMode]);
} else { ?>
	<span class="text-center divider2-striped text-white">
		<span class="acl-card p-1">
				<span class="fas fa-exclamation-triangle"></span>
				НЕТ ЗАПИСЕЙ ДОСТУПА
				<span class="fas fa-exclamation-triangle"></span>
		</span>
	</span>
	<span class="row text-center text-white"><small >в этом списке доступа нет ни одной записи — добавьте запись (кому и какой доступ предоставляется)</small></span>
<?php }

if (!$static_view && $groupMode) {
	echo Html::a('<span class="fas fa-plus"></span> Добавить участников',
		['/acls/group-ace-add','id'=>$model->id],
		['class'=>'btn btn-success btn-sm mb-2']
	);
}
