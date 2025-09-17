<?php
/**
 * Пустой ряд в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $height */
/* @var $rack \app\components\RackWidget */

use yii\helpers\Html;

?>
<tr style="height:<?= $height ?>%">
	<td colspan="<?= $rack->getTotalCols() ?>"></td>
</tr>
