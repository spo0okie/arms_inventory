<?php

use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\ServiceConnections */

Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\ServiceConnections::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

?>
<div class="service-connections-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
