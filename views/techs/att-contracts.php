<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 03.10.2019
 * Time: 23:09
 */

/* @var \app\models\Techs $model */

if ($docCount=count($model->contracts)) { ?>
	<span class="arm-att-count">
	    <span class="glyphicon glyphicon-paperclip" title="Прикреплены документы"></span>×<?= $docCount ?>
	</span>
<?php }
