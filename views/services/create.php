<?php

use app\models\Services;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($modalParent)) $modalParent=null;
$this->title = 'Новый сервис';
$this->params['breadcrumbs'][] = ['label' => Services::$titles, 'url' => ['index']];
if (is_object($model->parentService)) {
	$model
		->parentService
		->recursiveBreadcrumbs($this,'nameWithoutParent');
	$this->title = 'Новый суб-сервис';
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="services-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
    ]) ?>

</div>
