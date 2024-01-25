<?php

use app\models\Places;
use yii\helpers\Html;
use yii\helpers\Url;


/* @var $this yii\web\View */
/* @var $models app\models\Places[] */


Url::remember();

$this->title = Places::$titles;
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
