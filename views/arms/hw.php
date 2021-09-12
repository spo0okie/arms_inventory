<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */
/* @var $manufacturers app\models\Manufacturers[] */

if (!isset($manufacturers)) $manufacturers=\app\models\Manufacturers::fetchNames();

if (!isset($static_view)) $static_view=false;
?>

<table>
	<?php if (!$static_view) { ?>
        <thead>
            <th>Производитель</th>
            <th>Продукт</th>
            <th>Серийный номер</th>
            <th>Инвентарный номер</th>
            <th class="passport_tools"></th>
        </thead>
	<?php
	}
	foreach ($model->hwList->items as $item) {
		if (!$static_view || !$item->hidden) echo $this->render('/hwlist/item',
			compact('model','item','manufacturers','static_view')
		);
	}

	if (!$static_view) {
        $item=new \app\models\HwListItem();
        $item->type='custom';

        echo $this->render('/hwlist/item',
            [
                'model'=>$model,
                'item'=>$item,
                'manufacturers'=>$manufacturers,
                'addItem'=>true ,
            ]
        );
	}
	?>
</table>
