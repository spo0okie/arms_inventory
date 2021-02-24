<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$this->title = $model->sname;
$this->params['breadcrumbs'][] = ['label' => app\models\NetIps::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="net-ips-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
