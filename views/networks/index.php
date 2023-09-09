<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = app\models\Networks::$titles;
$this->params['breadcrumbs'][] = $this->title;
$panel=true;
?>
<div class="networks-index">
    <?= $this->render('table',compact('dataProvider','searchModel','panel')) ?>
</div>
