<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */
?>
<div class="materials-usages-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
</div>
