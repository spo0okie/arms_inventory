<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = app\models\Networks::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="networks-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= $this->render('table',compact('dataProvider','searchModel')) ?>
</div>
