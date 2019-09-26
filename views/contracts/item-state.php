<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

//тут разбиваем инфрмацию в записной книжке документа на строки
$history=explode("\n",$model->comment);
if (is_object($model)) { ?>
    <span class="contract_state_cell">
    <?php if ($model->state_id) { ?>
        <span class="contract_state <?= $model->state->code?>" title="<?= $model->state->descr ?>"><?= $model->state->name?></span>
    <?php }
    if (strlen($history[0])) { ?>
        <span class="contract_history_preview" title="<?= html_entity_decode($history[0]) ?>"><?= ($model->state_id)?('//'.$history[0]):$history[0] ?> </span>
    <?php } ?>
    </span>
<?php }