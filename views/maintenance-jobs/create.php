<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */

if (!isset($modalParent)) $modalParent=null;

$this->title = "Новое ".mb_strtolower(app\models\MaintenanceJobs::$title);
$this->params['breadcrumbs'][] = ['label' => app\models\MaintenanceJobs::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="maintenance-jobs-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
