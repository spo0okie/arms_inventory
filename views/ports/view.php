<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

\yii\helpers\Url::remember();

$this->title = \app\models\Ports::$port_prefix.$model->name;
if (is_object($model->tech)){
	$this->params['breadcrumbs'][] = ['label' => app\models\Techs::$title, 'url' => ['/techs/index']];
	$this->params['breadcrumbs'][] = ['label' => $model->tech->num, 'url' => ['/techs/view','id'=>$model->techs_id]];
}

$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="ports-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
