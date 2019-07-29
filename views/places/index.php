<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $models app\models\Places[] */



$this->title = \app\models\Places::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="places-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>


    <?= $this->render('tree-list',
        [
            'models'=>$models,
            'parent_id'=>null,
            'tree_level'=>0
        ]
    ) ?>

</div>
