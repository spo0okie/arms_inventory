<?php
/**
 * Гориз заголовок в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $height */
/* @var $titleHeight */
/* @var $title */
/* @var $rack \app\components\RackWidget */

use yii\helpers\Html;
$fontSize=min($titleHeight*0.6,22);
?>
<tr height="<?= $height ?>%" style="font-size: <?= $fontSize ?>px">
	<td class="rack-title" colspan="<?= $rack->getTotalCols() ?>"><?= $rack->title ?></td>
</tr>
