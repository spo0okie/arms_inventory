<?php

use app\models\Contracts;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

//тут разбиваем информацию в записной книжке документа на строки
$history=explode("\n",$model->comment);
if (is_object($model)) { ?>
    <span class="contract_state_cell">
    <?php if ($model->state_id) { ?>
        <span class="contract_state <?= $model->state->code?>" title="<?= $model->state->descr ?>"><?= $model->state->name?></span>
    <?php }
    switch ($model->deliveryState) {
		case Contracts::DELIVERY_COMPLETE:
			echo '<span class="contract_state bg-success">Поставлено</span>';
			break;
		case Contracts::DELIVERY_INCOMPLETE:
			echo '<span class="contract_state bg-danger">Ожидается '.implode(', ',$model->undeliveredDescription).'</span>';
			break;
	}
    if (strlen($history[0])) {
    	$preview=ltrim($history[0],"\t\n\r\0\x0b\\/");
    	?>
        <span class="contract_history_preview" title="<?= html_entity_decode($history[0]) ?>"><?= ($model->state_id)?('//'.$preview):$preview ?> </span>
    <?php } ?>
    </span>
<?php }