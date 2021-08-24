<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

//\yii\helpers\Url::remember();

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => \app\models\Aces::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="aces-view">
	<?= $this->render('card',['model'=>$model]) ?>
	<?= $this->render('notebook',['model'=>$model]) ?>
</div>
