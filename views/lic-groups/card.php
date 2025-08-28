<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\TextFieldWidget;
use app\models\LicGroupsSearch;
use kartik\markdown\Markdown;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel LicGroupsSearch */
/* @var $linksData ArrayDataProvider */

if (!isset($static_view)) $static_view=false;
echo
'<h1>'
.LinkObjectWidget::widget([
	'model'=>$model,
	'static'=>true
])
.'</h1>'
.TextFieldWidget::widget(['model'=>$model,'field'=>'comment'])
.ModelFieldWidget::widget([
	'model'=>$model,
	'field'=>'soft',
	'show_empty'=>true,
	'glue'=>'<br>',
	'message_on_empty'=>'<div class="alert-striped text-center w-100 p-2">
		<span class="fas fa-exclamation-triangle"></span>
			ОТСУТСТВУЮТ
		<span class="fas fa-exclamation-triangle"></span>
	</div>'
])
//.ModelFieldWidget::widget(['model'=>$model,'field'=>'includedBy'])
.$this->render('/attaches/model-list',compact(['model','static_view']))
.$this->render('usage',['model'=>$model])
;
