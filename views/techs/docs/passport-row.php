<?php
/**
 * Строчка в паспорте
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Techs $model
 */
?>
<td class="tech_type"><?= $this->render('/tech-types/item',['model'=>$model->model->type]) ?></td>
<td class="tech_model" ><?= $this->render('/tech-models/item',['model'=>$model->model]) ?></td>
<td class="tech_id"><?= $this->render('/techs/item',['model'=>$model]) ?></td>
<td class="tech_sn"><?= $model->sn ?></td>
<td class="tech_inv_num"><?= $model->inv_num ?></td>
