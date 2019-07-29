<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\OrgInet::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-inet-view">
    <div class="row">
        <div class="col-md-6">
	        <?= $this->render('card',['model'=>$model]) ?>
        </div>
        <div class="col-md-6">
            <h3>Заметки:</h3>
            <?= \Yii::$app->formatter->asNtext($model->history) ?>
        </div>
    </div>
</div>
