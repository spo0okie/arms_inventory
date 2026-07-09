<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)
\yii\web\YiiAsset::register($this);

?>
<div class="net-domains-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
