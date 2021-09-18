<?php
/**
 * Элемент оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\Techs $model */
/* @var string $name */

use yii\helpers\Html;
if (!isset($static_view)) $static_view=false;

if (!empty($model)) {
    if (!isset($name)) $name=$model->num;
    ?>

<span class="techs-item"
      qtip_ajxhrf="<?= \yii\helpers\Url::to(['/techs/ttip','id'=>$model->id])?>"
>
	<?=
		Html::a($name,['techs/view','id'=>$model->id])
	?><?=
		$static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['techs/update','id'=>$model->id,'return'=>'previous'])
	?>

</span>
<?php }