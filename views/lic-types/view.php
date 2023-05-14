<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

\yii\helpers\Url::remember();

$this->title = $model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicTypes::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lic-types-view">
    <?= $this->render('card',compact('model')) ?>
</div>
<div class="wiki-render-area">
	<?= \app\components\WikiPageWidget::Widget(['list'=>$model->links]) ?>
</div>