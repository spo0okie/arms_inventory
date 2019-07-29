<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 17.02.2018
 * Time: 0:47
 *
 * @var \app\models\Comps $model
 */

$classes=[];

if (in_array($product['id'],$model->soft_ids)) {
    $saved=true;

    $classes[]='saved-found';
} else {
    $saved=false;
}

?>

<tr class="software_item <?= implode(' ',$classes) ?>">
    <td class="os-name"><?= $model->name ?></td>
    <td class="manufacturer">
        <?= \yii\helpers\Html::a($manufacturers[$product['manufacturers_id']],
            ['manufacturers','id'=>$product['manufacturers_id']],
            ['title' => 'Перейти к производителю']
        ) ?>
        <?= \yii\helpers\Html::a(
            '<span class="glyphicon glyphicon-pencil"></span>',
            ['manufacturers/update','id'=>$product['manufacturers_id']],
            ['class'=>'passport_tools','title'=>'Редактировать производителя']
        ) ?>
    </td>
    <td class="product">
        <?= \yii\helpers\Html::a(
            $product['descr'],
            ['soft/view', 'id' => $product['id']],
            ['title' => 'Перейти к программному продукту']
        ) ?>
        <?= \yii\helpers\Html::a(
            '<span class="glyphicon glyphicon-pencil"></span>',
            ['soft/update', 'id' => $product['id'],'return'=>'previous'],
            ['class'=>'passport_tools','title'=>'Редактировать програмный продукт']
        ) ?>
    </td>
    <td class="passport_tools">
        <?php
            if(!is_null($model->arm_id)) {
                if (!$saved) {
                    echo \yii\helpers\Html::a(
                        '<span class="glyphicon glyphicon-plus-sign"></span>',
                        ['comps/addsw', 'id' => $model->id, 'items' => $product['id']],
                        ['title' => 'Вписать ПО в паспорт']
                    );
                } else {
                    echo \yii\helpers\Html::a(
                        '<span class="glyphicon glyphicon-minus-sign"></span>',
                        ['comps/rmsw', 'id' => $model->id, 'items' => $product['id']],
                        ['title' => 'Убрать из паспорта']
                    );
                }
            }
        ?>
    </td>
</tr>
