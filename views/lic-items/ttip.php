<?php
/**
 * тултип закупок лицензий
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var \app\models\LicItems $model */
/* @var $this yii\web\View */

?>
<div class="lics-ttip ttip-card">
<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
<?= $this->render('stat',['model'=>$model]) ?>
</div>
