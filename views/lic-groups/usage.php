<?php
/** Список закупленных и использованных лицензий
 * Элемент групп лицензий
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var \app\models\LicGroups $model */

if (is_object($model)) {
	if (!isset($static_view)) $static_view=false;
?>

    <h4>Распределение:</h4>
    <p>
        Всего закуплено лицензий: <?= $model->totalCount ?><br/>
        Из них действительных: <?= $model->activeCount ?><br/>
        Привязано к группе: <?= count($model->arms) ?><br/>
        Привязано к закупкам: <?= $model->usedCount - count($model->arms) ?><br/>
        Свободно: <?= $model->freeCount ?><br/>
    </p>

<?php }