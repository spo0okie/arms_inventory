<?php

/**
 * Рисует контейнер помещения со всеми вложенными. Для корневых рисует шапку
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $models[] app\models\Places */
?>

<div class="places-container">

    <?php if (!$depth) { ?>
        <h1>
            <?= Html::encode($model->name) ?>
            <?= Html::a('<span class="glyphicon glyphicon-plus-sign"></span>',['places/create','parent_id'=>$model->id]) ?>
        </h1>
    <?php } ?>

	<?= $this->render('header',compact(['model','depth'])) ?>
	<?= $this->render('arms-list',compact(['model','depth'])) ?>

	<?php foreach ($models as $cab)
		if ($cab->parent_id==$model->id) echo $this->render('container',['model'=>$cab,'models'=>$models,'depth'=>$depth+1]);
	?>

</div>
