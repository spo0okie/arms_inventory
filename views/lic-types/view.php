<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

\yii\helpers\Url::remember();

$this->title = $model->descr;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="lic-types-view">
    <?= $this->render('card',compact('model')) ?>
</div>
<div class="wiki-render-area">
	<?= \app\components\WikiPageWidget::Widget(['list'=>$model->links]) ?>
</div>