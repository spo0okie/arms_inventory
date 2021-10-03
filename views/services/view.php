<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Services::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="services-view">

    <?= $this->render('card',['model'=>$model]) ?>
	
</div>
<div class="wiki-render-area">
	<?= \app\components\WikiPageWidget::Widget(['list'=>$model->links]) ?>
</div>