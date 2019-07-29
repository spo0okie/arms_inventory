<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

$this->title = $model->sname;
$this->params['breadcrumbs'][] = ['label' => \app\models\OrgPhones::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-phones-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
