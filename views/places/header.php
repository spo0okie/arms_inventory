<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

$phones=$model->phones;
$addr=$model->addr;
$inets=$model->inets;

if (count($phones)||count($inets)||strlen($addr)) {
?>

<div class="places-container-hdr-depth<?= $depth ?>">

    <span class="org-phones">
        <?php if (count($phones)) {
            echo '<span class="glyphicon glyphicon-phone-alt"></span>';
            foreach ($phones as $phone)
		        echo $this->render('/org-phones/item',['model'=>$phone]);
        }?>
    </span>
    <span class="org-phones">
        <?php if (count($inets)) {
	        echo '<span class="glyphicon glyphicon-globe"></span>';
	        foreach ($inets as $inet)
		        echo $this->render('/org-inet/item',['model'=>$inet]);
        }?>
    </span>
    <?php if(strlen($addr)) { ?>
        <span class="places-addr">
            <span class="glyphicon glyphicon-envelope"></span><?= Html::encode($addr) ?>
            <?= Html::a('<span class="glyphicon glyphicon-pencil"></span>',['/places/update','id'=>$model->id],['title'=>'Редактировать помещение']) ?>
        </span>
    <?php } ?>
</div>
<?php } ?>