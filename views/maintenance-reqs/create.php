<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */

if (!isset($modalParent)) $modalParent=null;

$this->title = "Новый ".app\models\MaintenanceReqs::$title;
$this->params['breadcrumbs'][] = ['label' => app\models\MaintenanceReqs::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="maintenance-reqs-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
