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
        <?= implode('<br/>',array_filter([
            \app\components\ModelFieldWidget::renderFieldRow($model,'totalCount'),
            \app\components\ModelFieldWidget::renderFieldRow($model,'activeCount'),
            \app\components\ModelFieldWidget::renderFieldRow($model,'directUsedCount'),
            \app\components\ModelFieldWidget::renderFieldRow($model,'itemsUsedCount'),
            \app\components\ModelFieldWidget::renderFieldRow($model,'freeCount'),
        ])) ?>
    </p>

<?php }