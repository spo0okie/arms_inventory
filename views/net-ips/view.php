<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$this->title = $model->sname;
//крошки собираются автоматически в layout (views/layouts/main.php)
\yii\web\YiiAsset::register($this);

?>
<div class="net-ips-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
