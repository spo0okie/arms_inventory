<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $models app\models\Places[] */
if (!isset($show_archived)) $show_archived=true;
if (!isset($static_view)) $static_view=false;

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
	<?= $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0,'show_archived'=>$show_archived]) ?>
	<br />
	<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
</div>
