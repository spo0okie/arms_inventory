<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = $model->Ename;
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$deleteable=!(bool)(count($model->arms) || count($model->armsHead) || count($model->armsIt) || count($model->armsResponsible) || count($model->techs) || count($model->techsIt));
?>
<div class="users-view">
	<?= $this->render('card',['model'=>$model,'static_view'=>false]) ?>
</div>
