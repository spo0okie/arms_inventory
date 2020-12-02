<?php
/**
 * Элемент схемы лицензирования
 * User: spookie
 * Date: 02.12.2020
 * Time: 22:27
 */

/* @var \app\models\LicTypes $model */
/* @var $this yii\web\View */

use yii\helpers\Html;
if (!isset($static_view)) $static_view=false;

if (is_object($model)) { ?>
	
	<span class="lic_types-item">
	<?= Html::a($model->descr,
		['lic-types/view','id'=>$model->id],
		[
			'qtip_ajxhrf'=>$static_view?null:\yii\helpers\Url::to(['/lic-types/ttip','id'=>$model->id]),
			//'class'=>$active?"contract_active":"contract_inactive",
		]
	) ?>
	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['lic-types/update','id'=>$model->id])) ?>
</span>
<?php } else echo "Отсутствует";