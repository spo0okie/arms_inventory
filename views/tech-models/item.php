<?php
/**
 * Элемент модели оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\TechModels $model */

use yii\helpers\Html;
if (is_object($model)) {
	if (isset($short)&&$short)
		$name=(strlen($model->short))?$model->short:$model->name;
	elseif (isset($long)&&$long)
		$name=$model->manufacturer->name.' '.$model->name;
	elseif (isset($compact)&&$compact)
		$name=$model->manufacturer->name.' '.(strlen($model->short)?$model->short:$model->name);
    else
		$name=$model->name;
    ?>

<span class="tech_model-item"
      qtip_ajxhrf="<?= \yii\helpers\Url::to(['/tech-models/ttip','id'=>$model->id])?>"
>
	<?= Html::a($name,['tech-models/view','id'=>$model->id]) ?>
	<?= Html::a('<span class="glyphicon glyphicon-pencil"></span>',['tech-models/update','id'=>$model->id]) ?>
</span>

<?php }