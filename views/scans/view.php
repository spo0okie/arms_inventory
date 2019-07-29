<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Scans */

$view_max_width=800;
//$view_max_height=800;

$this->title = $model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\Scans::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$thumbName=$model->viewThumb;
//$contracts=$model->contracts;

?>
<div class="scans-view">

    <p>
        <?= Html::a('Правка', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены, что нужно удалить этот скан документа? (Операция необратима!)',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <br />

    <h4>Описание</h4>
    <p>
	<?= Html::encode($this->title) ?>
    </p>

    <br />

    <h4>Документ</h4>
    <?= $this->render('preview',compact(['model'])); ?>

</div>
