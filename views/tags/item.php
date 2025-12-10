<?php
/** Тег
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.11.2018
 * Time: 15:03
 */

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\Tags;

/* @var $model Tags */


if (is_object($model)) {
	$textColor = $model->getTextColor();
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'link'=> LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view??false,
			'noDelete'=>$noDelete??true,
			"hideUndeletable"=>$hideUndeletable??true,
			'hrefOptions'=>['style'=>"color: {$textColor}"]
		]),
		'item_class' => 'badge',
		'style' => "background-color: {$model->color}; ",
	]);
}
