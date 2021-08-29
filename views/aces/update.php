<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$this->title = 'Правка: ' . $model->sname;
$this->render('/acls/breadcrumbs',['model'=>$model->acl,'static_view'=>false]);
$this->params['breadcrumbs'][] = ['label' => $model->sname, 'url' => ['view', 'id' => $model->id]];
//$this->params['breadcrumbs'][] = $this->title;

//$this->params['breadcrumbs'][] = ['label' => \app\models\Aces::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Правка';
?>
<div class="aces-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
