<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

//\yii\helpers\Url::remember();

$this->title = $model->sname;
$this->render('/acls/breadcrumbs',['model'=>$model->acl,'static_view'=>false]);
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="aces-view">
	<?= $this->render('card',['model'=>$model]) ?>
	<?= $this->render('notepad',['model'=>$model]) ?>
</div>
