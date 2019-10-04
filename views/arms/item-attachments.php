<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 03.10.2019
 * Time: 23:59
 * @var \app\models\Arms $model
 */



$licCount=count($model->licItems)+count($model->licGroups)+count($model->licKeys);
$docCount=count($model->contracts);
if ($licCount||$docCount) { ?>
    <span class="arm-att-count">
	    <?php if ($licCount) echo '<span class="fas fa-award" title="Прикреплены лицензии"></span>×'.$licCount; ?>
	    <?php if ($docCount) echo '<span class="glyphicon glyphicon-paperclip" title="Прикреплены документы"></span>×'.$docCount; ?>
	</span>
<?php }