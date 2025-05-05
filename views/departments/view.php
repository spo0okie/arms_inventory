<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Departments */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\Departments::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="departments-view">
	<?= $this->render('card',['model'=>$model]) ?>

</div>
