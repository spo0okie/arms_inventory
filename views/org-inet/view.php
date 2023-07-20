<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */
\yii\helpers\Url::remember();

$static_view=false;
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\OrgInet::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-inet-view">
	<?= $this->render('/services/card',['model'=>$model->service ,'static_view'=>$static_view]) ?>
</div>
