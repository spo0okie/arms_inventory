<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 19.02.2018
 * Time: 12:43
 */
?>

<tr class="hardware_item <?= $item['excluded']?'excluded':'included' ?>">
    <td>
        <?= $item['title'] ?>
        <?php if (!is_null($item['manufacturer_id'])){ ?>
            <?= \yii\helpers\Html::a($manufacturers[$item['manufacturer_id']],
                ['manufacturers','id'=>$item['manufacturer_id']],
                ['title' => 'Перейти к производителю']
            ) ?>
            <?= \yii\helpers\Html::a('<span class="fas fa-pencil-alt"/>',
                ['manufacturers/view', 'id' => $item['manufacturer_id'],'return'=>'previous'],
                ['title'=>'Редактировать производителя','class'=>'show-on-hover-included show-on-hover-excluded']
            ) ?>
        <?php } else { ?>
            <?= $item['manufacturer'] ?>
            <?= \yii\helpers\Html::a('<span class="fas fa-wrench"/>',
                ['manufacturers-dict/create', 'word' => $item['manufacturer'],'return'=>'previous'],
                ['title'=>'Создать производителя','class'=>'show-on-hover-included show-on-hover-excluded']
            ) ?>
        <?php } ?>
    </td>
    <td>
        <?= $item['product'] ?>
        <?= \yii\helpers\Html::a('<span class="fas fa-eye-close"/>',
            ['comps/update', 'id'=>$model->id,'addExclusion' => $item['fingerprint']],
            ['title'=>'Убрать из паспорта этой машины','class'=>'show-on-hover-included']
        ) ?>
        <?= \yii\helpers\Html::a('<span class="fas fa-eye"/>',
            ['comps/update', 'id'=>$model->id,'subExclusion' => $item['fingerprint']],
            ['title'=>'Вернуть в паспорт этой машины','class'=>'show-on-hover-excluded']
        ) ?>

    </td>
    <td><?= $item['sn'] ?></td>
    <td></td>
</tr>