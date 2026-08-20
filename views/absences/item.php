<?php

use app\components\ItemObjectWidget;

/**
 * Элемент списка отсутствий.
 *
 * @var yii\web\View $this
 * @var app\models\Absences $model
 * @var bool $no_user не выводить имя сотрудника: на его собственной карточке
 *                    подпись «Иванов И.И.:» в каждой строке избыточна
 * @var bool $static_view
 * @var bool $show_delete
 * @var bool $show_archived
 * @var string $class
 * @var string $suffix
 */

if (empty($model)) return;

$options = [
	'model' => $model,
	'name' => ($no_user ?? false) ? $model->shortName : $model->name,
	'nameSuffix' => $suffix ?? '',
	//конвенция item-вью: удаление делается со страницы объекта, а не из чужого списка
	'noDelete' => !($show_delete ?? false),
	'static' => $static_view ?? false,
];
if (isset($class)) $options['item_class'] = $class;
if (isset($show_archived)) $options['show_archived'] = $show_archived;

echo ItemObjectWidget::widget($options);
