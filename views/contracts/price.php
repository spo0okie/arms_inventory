<?php

//цена с НДС

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $total float */
/* @var $charge float */
/* @var $currency string */

if ($total) { ?>
<?= number_format($total,2,'.',' ' ).$currency ?>
<?php if ($charge){ ?>
	(в т.ч. НДС: <?= number_format($charge,2,'.',' ' ).$currency ?>)
<?php }
}
