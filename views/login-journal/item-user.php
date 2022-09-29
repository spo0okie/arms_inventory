<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LoginJournal */
/* @var $name string */


if (is_object($model))
	echo $this->render('/users/item',['model'=>$model->user,'name'=>$model->userDescr.' ('.$model->age.')']);

