<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 17.02.2018
 * Time: 0:47
 *
 * @var $this yii\web\View
 * @var $model app\models\SwList
 * @var array $item
 */

$classes=[];

if ($item['saved']) {
    if ($item['found']){
        $classes[]='saved-found';
    } else {
        $classes[]='saved-missed';
    }
} else {
    $classes[]='unsaved-found';
}

if (isset($item['hit'])&&is_object($item['hit'])) {
    $hitlist=$this->render('/soft-hits/item',['items'=>$item['hit']->hit_items]);
} else $hitlist=null;

$product=\app\models\Soft::fetchItem($item['id']);
$dev=\app\models\Manufacturers::fetchItem($product->manufacturers_id);

?>

<tr class="software_item <?= implode(' ',$classes) ?>">
    <td class="os-name"><?= $model->name ?></td>
    <td class="manufacturer">
        <?= $this->render('/manufacturers/item',['model'=>$dev]) ?>
    </td>
    <td class="product">
		<?= $this->render('/soft/item',['model'=>$product,'hitlist'=>$hitlist]) ?>
    </td>
    <td class="passport_tools">
        <?php
        if(!is_null($model->arm_id)) {
            if (!$item['saved']) {
	            if ($item['agreed']) echo \yii\helpers\Html::a(
                    '<span class="glyphicon glyphicon-plus-sign"></span>',
                    ['/comps/addsw', 'id' => $model->id, 'items' => $product->id],
                    ['title' => 'Вписать ПО в паспорт']
                );
            } else {
                echo \yii\helpers\Html::a(
                    '<span class="glyphicon glyphicon-minus-sign"></span>',
                    ['/comps/rmsw', 'id' => $model->id, 'items' => $product->id],
                    ['title' => 'Убрать из паспорта']
                );
            }
        }
        ?>
    </td>
</tr>