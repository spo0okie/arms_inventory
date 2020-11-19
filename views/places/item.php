<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

//qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])
//qtip_class="qtip-wide"
//	<?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/places/update','id'=>$model->id])

if (!isset($static_view)) $static_view=true;

if (is_object($model)) {

?>
<span class="places-item" >
    <?php if (isset($full)) {
        $tokens=[];
        $item=$model;
        do {
            $tokens[]=Html::a(
                $item->short,
                ['/places/view','id'=>$item->id],
                ['qtip_ttip'=>$item->name,]
            );
            $item=\app\models\Places::fetchItem($item->parent_id);
        } while (is_object($item));
        echo implode('/',array_reverse($tokens));
    } else {
	    echo Html::a(
		    isset($short)?$model->short:$model->name,
            ['/places/view','id'=>$model->id],
            ['qtip_ttip'=>$model->name,]
        );
		if (!$static_view) echo Html::a('<span class="glyphicon glyphicon-pencil"/>',['/places/update','id'=>$model->id]);
    } ?>
</span>

<?php } else echo "Отсутствует";