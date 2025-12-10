<?php

use yii\helpers\Html;

/**
 * Определение колонок для Grid таблицы тегов
 * 
 * @var yii\web\View $this
 * @var app\models\TagsSearch $searchModel
 */

return [
    'name',
    'slug',
	'color',
    'description',
    'usage_count'
];