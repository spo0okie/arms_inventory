<?php
/**
 * Строчка в паспорте
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Techs $model
 */
?>
<td class="tech_type"><?= $model->model->type->renderItem($this) ?></td>
<td class="tech_model" ><?= $model->model->renderItem($this) ?></td>
<td class="tech_id"><?= $model->renderItem($this) ?></td>
<td class="tech_sn"><?= $model->sn ?></td>
<td class="tech_inv_num"><?= $model->inv_num ?></td>
