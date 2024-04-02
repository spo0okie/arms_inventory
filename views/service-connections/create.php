<?php

use app\helpers\StringHelper;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ServiceConnections */

if (!isset($modalParent)) $modalParent=null;

$this->title = "Новое ".StringHelper::mb_lcfirst(app\models\ServiceConnections::$title);
$this->params['breadcrumbs'][] = ['label' => app\models\ServiceConnections::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="service-connections-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
