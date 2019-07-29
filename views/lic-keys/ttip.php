<?php
/**
 * тултип лицензионных ключей
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var \app\models\LicKeys $model */
/* @var $this yii\web\View */

use yii\helpers\Html;
?>
<div class="lic_keys-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
</div>
