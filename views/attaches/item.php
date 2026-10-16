<?php

use app\components\ItemObjectWidget;

/** @var yii\web\View $this */
/** @var app\models\Attaches $model */

if (!isset($static_view)) $static_view = false;

echo ItemObjectWidget::widget([
	'show_archived'=>$show_archived??null, // Учитываем явный режим списка.
	'model'=>$model,
	'url'=>$model->url,
	'hrefOptions'=>['target'=>'_blank'],
	'namePrefix'=>'<i class="fas fa-file-download"></i> ',
	'noUpdate'=>true,
	'static'=>$static_view,
	'confirmMessage'=>'Удалить приложенный файл? (действие необратимо)',
]);
