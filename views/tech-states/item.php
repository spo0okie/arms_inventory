<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 03.10.2019
 * Time: 21:55
 */
/* @var \app\models\TechStates $model */

if (is_object($model)) { ?>

<span class="item_status <?= strlen($model->name)?$model->code:'' ?>" ><?= $model->name ?></span>

<?php }