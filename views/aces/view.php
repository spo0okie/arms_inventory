<?php

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

//\yii\helpers\Url::remember();

use yii\web\YiiAsset;

$this->title = $model->sname;
if (is_object($model->acl))
	$this->render('/acls/breadcrumbs',['model'=>$model->acl,'static_view'=>false]);
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

?>
<div class="aces-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
