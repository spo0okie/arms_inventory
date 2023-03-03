<?php
/**
 * Элемент списка урлов
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:25
 */

/* @var $item array */

use yii\helpers\Html;
use yii\widgets\DetailView;

if (!is_array($item)) $item=\app\components\UrlListWidget::parseListItem($item);

try {
	echo Html::a($item['descr'],$item['url']);
} catch (Exception $e) {
	echo $item['descr'].' (неверный URL)';
} ?><br/>
