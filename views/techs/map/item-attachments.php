<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 03.10.2019
 * Time: 23:59
 * @var \app\models\Techs $model
 */



//количества через кэш количеств: иначе на каждый АРМ в списке грузятся 4 связи
$licCount=($model->loaderCount('licItems') ?? count($model->licItems))
	+($model->loaderCount('licGroups') ?? count($model->licGroups))
	+($model->loaderCount('licKeys') ?? count($model->licKeys));
$docCount=$model->loaderCount('contracts') ?? count($model->contracts);
if ($licCount||$docCount) { ?>
    <span class="arm-att-count">
	    <?php if ($licCount) echo '<span class="fas fa-award" title="Прикреплены лицензии"></span>×'.$licCount; ?>
	    <?php if ($docCount) echo '<span class="fas fa-paperclip" title="Прикреплены документы"></span>×'.$docCount; ?>
	</span>
<?php }