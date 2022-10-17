<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

$phones=$model->phones;
$addr=$model->addr;
$inets=$model->inets;

if (!isset($show_archived)) $show_archived=true;

if (count($phones)||count($inets)||strlen($addr)) {
?>

<div class="places-container-hdr-depth<?= $depth ?>">

    <span class="org-phones">
        <?php if (count($phones)) {
            echo '<i class="fas fa-phone"></i>';
            foreach ($phones as $phone)
		        echo $this->render('/org-phones/item',['model'=>$phone,'show_archived'=>$show_archived]).' ';
        }?>
    </span>
    <span class="org-phones">
        <?php if (count($inets)) {
	        foreach ($inets as $inet) {
	        	echo '<i class="fas fa-globe '.($inet->archived?'archived-item':'').'" '.($inet->archived&&!$show_archived?'style="display:none"':'').'></i>';
				echo $this->render('/org-inet/item', ['model' => $inet, 'show_archived' => $show_archived]) . ' ';
			}
        }?>
    </span>
    <?php if(strlen($addr)) { ?>
        <span class="places-addr">
            <span class="fas fa-envelope"></span><?= Html::encode($addr) ?>
            <?= Html::a('<i class="fas fa-pencil-alt"></i>',['/places/update','id'=>$model->id],['title'=>'Редактировать помещение']) ?>
        </span>
    <?php } ?>
</div>
<?php } ?>