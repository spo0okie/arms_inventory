<?php
/**
 * Элемент закупок лицензий
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var \app\models\LicItems $model */
/* @var $this yii\web\View */

use yii\helpers\Html;

if (is_object($model)) {
	if (!isset($static_view)) $static_view=false;
	if (!isset($name)) $name=$model->licGroup->descr.' / '.$model->descr;
?>

<span class="lic_item-item">
	<?= Html::a($name,
        ['lic-items/view','id'=>$model->id],
        [
            'qtip_ajxhrf'=>$static_view?null:\yii\helpers\Url::to(['/lic-items/ttip','id'=>$model->id]),
		    //'class'=>$active?"contract_active":"contract_inactive",
        ]
    )
	?><?=
	$static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['lic-items/update','id'=>$model->id])) ?>
</span>
<?php } else echo "Отсутствует";