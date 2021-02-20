<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

$this->title = "Новый ".\app\models\NetDomains::$title;
$this->params['breadcrumbs'][] = ['label' => \app\models\NetDomains::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="net-domains-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
