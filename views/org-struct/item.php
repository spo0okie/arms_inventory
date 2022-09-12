<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

if (!isset($static_view)) $static_view=true;
if (!isset($items_glue)) $items_glue=' &rarr; ';


if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	?>

	<span class="org-struct-item">
		<?php if (isset($chain) && !empty($chain)) {
			$tokens=[];
			$item=$model;
			do {
				$tokens[]=Html::a(
					$item->name,
					['/org-struct/view','id'=>$item->id,'org_id'=>$item->org_id],
					['qtip_ajxhrf'=>\yii\helpers\Url::to(['org-struct/ttip','id'=>$item->id,'org_id'=>$item->org_id]),]
				);
				$item=$item->parent;
			} while (is_object($item));
			echo implode($items_glue,array_reverse($tokens));
		} else {
			echo Html::a(
				$name,
				['/org-struct/view','id'=>$model->id,'org_id'=>$model->org_id],
				['qtip_ajxhrf'=>\yii\helpers\Url::to(['org-struct/ttip','id'=>$model->id,'org_id'=>$model->org_id])]
			);
			if (!$static_view) echo Html::a('<span class="fas fa-pencil-alt"/>',['/places/update','id'=>$model->id]);
		} ?>	</span>
<?php }

