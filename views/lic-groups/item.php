<?php
/**
 * Элемент групп лицензий
 * User: spookie
 * Date: 05.11.2018
 * Time: 21:55
 */

/* @var \app\models\LicGroups $model */

use yii\helpers\Html;
if (is_object($model)) {
	if (!isset($static_view)) $static_view=false;
?>

<span class="lic_groups-item">
	<?= Html::a($model->descr,
        ['lic-groups/view','id'=>$model->id],
        [
            'qtip_ajxhrf'=>$static_view?null:\yii\helpers\Url::to(['/lic-groups/ttip','id'=>$model->id]),
		    //'class'=>$active?"contract_active":"contract_inactive",
        ]
    ) ?>
	<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['lic-groups/update','id'=>$model->id])) ?>
</span>
<?php } else echo "Отсутствует";