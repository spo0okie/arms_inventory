<?php

use app\components\widgets\page\ModelWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

$this->title = 'Правка записи доступа: ' . $model->sname;

ModelWidget::widget(['model'=>$model->acl,'view'=>'breadcrumbs', 'options'=>['static_view'=>false]]);

$this->params['breadcrumbs'][] = ['label' => $model->sname, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Правка';

?>
<div class="aces-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
