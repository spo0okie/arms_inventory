<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Attaches[] $models */

if (!isset($static_view)) $static_view = false;
if (!isset($glue)) $glue='<br />';

$items=[];
foreach ($models as $model)
	$items[]=$this->render('item',compact(['model','static_view']));
	echo implode($glue,$items);
