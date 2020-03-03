<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 *
 */

/* @var \app\models\Techs $model */
?>
<tr class="tech tech_<?= $model->type->code ?>">
    <td class="tech_whitespace" colspan="2" ></td>
    <td class="arm_uname">
        <?= is_object($model->user)?$model->user->fullName:'' ?>
    </td>
    <td class="arm_uphone">
        <?= $this->render('/tech-types/item',['model'=>$model->model->type]) ?>
    </td>
    <td class="arm_model">
        <?= $this->render('/tech-models/item',['model'=>$model->model,'short'=>true]) ?>
    </td>
    <td class="hardware">
        <?= $this->render('/techs/item',['model'=>$model]) ?>
    </td>
    <td class="attachments">
	    <?= $this->render('/techs/att-contracts',['model'=>$model]) ?>
    </td>
    <td class="item_status <?= strlen($model->stateName)?$model->state->code:'' ?>">
        <?= $model->stateName ?>
    </td>
    <td class="item_ip">
        <?= $model->ip ?>
    </td>
    <td class="item_sn">
        <?= $model->sn ?>
    </td>
    <td class="item_invnum">
        <?= $model->inv_num ?>
    </td>
</tr>