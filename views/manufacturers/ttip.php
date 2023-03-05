<?php


/* @var $this yii\web\View */
/* @var $model app\models\Manufacturers */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Производители', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="manufacturers-ttip ttip-card">

    <?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>

</div>
