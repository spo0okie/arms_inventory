<?php


/* @var $this yii\web\View */
/* @var $model app\models\Manufacturers */

$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)

?>
<div class="manufacturers-view">

    <?= $this->render('card',['model'=>$model]) ?>

</div>
