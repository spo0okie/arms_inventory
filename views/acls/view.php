<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

//\yii\helpers\Url::remember();

$this->title = $model->sname;

$this->render('breadcrumbs',['model'=>$model,'static_view'=>true]);

\yii\web\YiiAsset::register($this);

?>
<div class="acls-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
