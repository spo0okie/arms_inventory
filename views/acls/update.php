<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$this->title = 'Редактирование списка доступа';

$this->render('breadcrumbs',['model'=>$model]);

$this->params['breadcrumbs'][] = $this->title;
\yii\helpers\Url::remember();

?>
<div class="acls-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
