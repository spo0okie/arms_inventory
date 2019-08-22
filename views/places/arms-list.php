<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $arms app\models\Arms[] */
/* @var $techs app\models\Techs[] */

$arms=$model->arms;
$techs=$model->techs;
$materials=$model->materials;
$attached=0; //техника прикрепленная к АРМ
foreach ($arms as $arm) foreach ($arm->techs as $tech)
    if (!$tech->isVoipPhone && !$tech->isUps) $attached++;


?>

<table class="places-cabinet-container">
    <tr>
        <td class="places-arms-cabinet">
	        <?= $this->render('item',['model'=>$model,'short'=>true]) ?>
        </td>
        <td class="places-arms-list">
            <table  class="places-arms-container">
	            <?php
                //АРМы
                foreach ($arms as $arm )
                    echo  $this->render('/arms/tdrow',['model'=>$arm]);

                //оборудование
                foreach ($techs as $tech )
                    echo $this->render('/techs/tdrow',['model'=>$tech]);

                if (count($materials)) echo $this->render('/places/materials-list',['models'=>$materials]);
                ?>

            </table>
        </td>
    </tr>
</table>

