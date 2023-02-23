<?php
/**
 * Гориз заголовок в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $height */
/* @var $title */
/* @var $rack \app\components\RackWidget */

use yii\helpers\Html;

?>
<tr height="<?= $height ?>%">
	<td class="rack-title" colspan="<?= $rack->getTotalCols() ?>"><?= $rack->title ?></td>
</tr>
