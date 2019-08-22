<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

//$successors=[];
if (is_object($model) && $model->state_id) { ?>
    <span class="contract_state <?= $model->state->code?>" title="<?= $model->state->descr ?>"><?= $model->state->name?></span>
<?php }