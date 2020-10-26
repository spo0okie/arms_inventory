<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$static_view=true;

?>

<div class="arms-ttip ttip-card">
    <?= $this->render('hw',['model'=>$model,'manufacturers'=>\app\models\Manufacturers::fetchNames(),'static_view'=>$static_view]) ?>
</div>