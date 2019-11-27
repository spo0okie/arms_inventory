<?php

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = $model->Ename;
$this->params['breadcrumbs'][] = ['label' => \app\models\Users::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="users-view">
	<?= $this->render('card',['model'=>$model,'static_view'=>false]) ?>
</div>
