<?php
/**
 * Гориз заголовок в стойке/корзине
 * User: spookie
 * Date: 05.02.2023
 * Time: 15:00
 */

/* @var $width */
/* @var $rackId */
/* @var $unitId */

use yii\helpers\Html;

?>
	<td class="rack-unit-label rack-<?= $rackId ?>-unit-<?= $unitId ?>-label" width="<?= $width ?>%"><?= $unitId ?></td>