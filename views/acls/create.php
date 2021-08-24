<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$this->title = "Новый ".\app\models\Acls::$title;

$this->render('breadcrumbs',['model'=>$model,'show_item'=>false]);
$this->params['breadcrumbs'][] = 'Новый ACL';
\yii\web\YiiAsset::register($this);


?>
<div class="acls-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
