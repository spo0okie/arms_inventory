<?php


/* @var $this yii\web\View */
/* @var $model app\models\Materials */

use app\components\HistoryWidget;
use app\models\Materials;
use yii\helpers\Url;
use yii\web\YiiAsset;

Url::remember();
$this->title =  $model->type->name.': '. $model->model;

//крошки собираются автоматически в layout (views/layouts/main.php)

YiiAsset::register($this);

?>
<div class="materials-view">
	<small class="opacity-75 float-end"><?= HistoryWidget::widget(['model'=>$model]) ?></small>

	<?= $this->render('card',['model'=>$model]) ?>


</div>
