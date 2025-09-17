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
$fontSize=min($titleHeight*0.7,22);
?>
<tr style="font-size: <?= $fontSize ?>px; height:<?= $height ?>%">
	<td class="rack-title" colspan="<?= $rack->getTotalCols() ?>"><?= $rack->title ?></td>
</tr>
