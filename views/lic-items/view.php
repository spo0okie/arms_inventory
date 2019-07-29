<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

if (!isset($keys)) $keys=null;

$this->title = $model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$title, 'url' => ['lic-groups/index']];
$this->params['breadcrumbs'][] = ['label' => $model->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->lic_group_id]];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="lic-items-view">
    <?= $this->render('card',['model'=>$model,'keys'=>$keys]) ?>

</div>
