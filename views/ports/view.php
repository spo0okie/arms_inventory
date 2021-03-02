<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\Ports::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="ports-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
