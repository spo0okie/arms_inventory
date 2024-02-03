<?php

use app\models\Materials;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

$this->title = 'Изображения: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Materials::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изображения';
?>
<div class="techs-update">

    <h1><?= Html::encode($this->title) ?></h1>

	<?= $this->render('/scans/_form', [
		'model' => $model,
		'link' => 'material_models_id',
	]) ?>
</div>
