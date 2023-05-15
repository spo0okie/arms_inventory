<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 07.03.2018
 * Time: 23:02
 * @var \app\models\HwListItem $item отображаемый элемент
 * @var \app\models\OldArms    $model объект компьютера или АРМа, из которого вызвано
 * @var array                  $manufacturers список производителей
 * @var bool                   $addItem признак того, что это не настоящий элемент а пустышка для добавления элемента в паспорт
 */

if (!isset($pos)) $pos=null;

use yii\bootstrap5\Modal;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
//echo '<pre>'; var_dump($item); echo '</pre>'; die(0);
if (!isset($static_view)) $static_view=false;

$classes=[];    //определим классы элемента
if (isset($addItem)&&($addItem===true)) {   //если это пустышка
    $classes[]='passport_tools';                  //, то и ок
    $hint='Добавление нового элемента в паспорт';
} else {
    if ($item->hidden) {                    //если скрытый
        $classes[]='excluded';
        $hint='Элемент скрыт из паспорта';
    } else {                                //иначе
        $classes[]='included';              //показываем
        if (strlen($item->uid)) {                                       //если сохранен
            if ($item->found) {
                $classes[] = 'saved-found';
                $hint = 'Элемент сохранен в паспорте и обнаружен мониторингом';
            } else {
                $classes[] = 'saved-missing';
                $hint = 'Элемент сохранен в паспорте, но не обнаружен мониторингом';
            }
      //то сохранен-найден или сохранен-не-найден
        } else {
            if ($item->found) $classes[]='unsaved-found';               //иначе просто не сохранен
            $hint = 'Элемент отсутствует в паспорте, но обнаружен мониторингом';
        }
    }
}

?>

<tr class="hardware_item <?= implode(' ',$classes) ?>" title="<?= $hint ?>">
    <td>
        <?= $item->title ?>
        <?php if (!is_null($item->manufacturer_id)){ ?>
            <?= \yii\helpers\Html::a($manufacturers[$item->manufacturer_id],
                ['/manufacturers/view','id'=>$item->manufacturer_id],
                ['title' => 'Перейти к производителю']
            ) ?>
            <?= $static_view?'':\yii\helpers\Html::a('<span class="fas fa-pencil-alt"/>',
                ['/manufacturers/view', 'id' => $item->manufacturer_id,'return'=>'previous'],
                ['title'=>'Редактировать производителя','class'=>'passport_tools']
            ) ?>
        <?php } else { ?>
            <?= $item->manufacturer ?>
            <?= $static_view?'':(strlen($item->manufacturer)?\yii\helpers\Html::a('<span class="fas fa-wrench"/>',
                ['/manufacturers-dict/create', 'word' => $item->manufacturer,'return'=>'previous'],
                ['title'=>'Создать производителя','class'=>'passport_tools']
            ):'') ?>
        <?php } ?>
    </td>
    <td><?= $item->getName() ?></td>
	<?php if (!$static_view) { ?>
    <td><?= \yii\helpers\Html::a($item->getSN(),['comps/index','CompsSearch[os]'=>$item->getSN()]) ?></td>
    <td><?= $item->inv_num ?></td>
    <td class="passport_tools">
        <?php if(get_class($model) == \app\models\Techs::className()){ //если передан паспорт АРМ, то можем поредактировать железо
            //проверяем есть ли uid у железки, если есть, то она уже сохранена, если нету, то можно сохранить
            if (strlen($item->uid)) {
                //УИД есть, значит работаем как с загруженной железкой
				echo \yii\helpers\Html::a('<span class="fas fa-pencil-alt"/>',
					['techs/edithw', 'id'=>$model->id,'uid' => $item->uid],
					['title'=>'Редактировать этот элемент','class'=>'passport_tools open-in-modal-form']
				);
                //кнопочка редактирования
                
                echo \yii\helpers\Html::a('<span class="fas fa-minus-circle"/>',
                    ['techs/rmhw', 'id'=>$model->id,'uid' => $item->uid],
                    ['title'=>'Убрать из паспорта этот элемент','class'=>'passport_tools']
                );

            } else {
                echo \yii\helpers\Html::a('<span class="fas fa-plus-circle"/>',
                    array_merge(['techs/updhw', 'id'=>$model->id],$item->toSave()),
                    [
                        'title'=>(isset($addItem)&&($addItem===true))?'Добавить новый элемент в паспорт':'Сохранить в паспорт этот элемент',
                        'class'=>'passport_tools',
                    ]
                );
                /*if (!(isset($addItem)&&($addItem===true)))
                    echo \yii\helpers\Html::a('<span class="fas fa-exclamation-sign"/>',
                    ['hw-ignore/create', 'fingerprint'=>$item->fingerprint],
                    [
                        'title'=>'Добавить позицию в список глобального скрытия',
                        'class'=>'passport_tools',
                    ]
                );*/
            }
        } ?>
    </td>
	<?php } ?>
</tr>
