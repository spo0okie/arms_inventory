<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $models app\models\Places[] */

\yii\helpers\Url::remember();
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Places::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="places-view">

    <h1>
		<?= \app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'ttipUrl'=>false,
			//'static'=>true
		]) ?>    </h1>

    <?= $this->render('hdr_create_obj',['places_id'=>$model->id]) ?>
	<?= $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0]) ?>
</div>
