<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 07.03.2018
 * Time: 23:02
 * @var \app\models\HwList $model отображаемый элемент
 */
use yii\bootstrap5\Modal;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
//echo '<pre>'; var_dump($item); echo '</pre>'; die(0);

?>
<table class="arm-hw-ttip">
    <thead>
        <td>Позиция</td>
        <td>Модель</td>
        <td>Сер. #</td>
        <td>Инв. #</td>
    </thead>
    <?php foreach ($model->items as $item) { ?>
    <tr>
        <td><?= $item->title ?></td>
        <td><?= $item->getName() ?></td>
        <td><?= $item->getSN() ?></td>
        <td><?= $item->inv_num ?></td>
    </tr>
<?php } ?>
</table>
