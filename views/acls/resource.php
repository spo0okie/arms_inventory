<?php

use kartik\markdown\Markdown;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;


if (strlen($model->comment))
	echo $model->comment;

elseif (($model->comps_id) and is_object($model->comp))
	echo $this->render('/comps/item',['model'=>$model->comp,'static_view'=>true,'icon'=>true]);

elseif (($model->techs_id) and is_object($model->tech))
	echo $this->render('/techs/item',['model'=>$model->tech,'static_view'=>true,'icon'=>true]);

elseif (($model->services_id) and is_object($model->service))
	echo $this->render('/services/item',['model'=>$model->service,'static_view'=>true,'icon'=>true]);

elseif (($model->ips_id) and is_object($model->ip))
	echo $this->render('/net-ips/item',['model'=>$model->ip,'static_view'=>true,'icon'=>true,'no_class'=>true]);
else
	echo \app\models\Acls::$emptyComment;
